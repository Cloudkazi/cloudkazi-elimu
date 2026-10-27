<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date'         => 'date',
        'end_date'           => 'date',
        'grace_period_ends_at' => 'datetime',
        'activated_at'       => 'datetime',
        'suspended_at'       => 'datetime',
        'cancelled_at'       => 'datetime',
        'activation_fee'     => 'decimal:2',
        'annual_fee'         => 'decimal:2',
        'amount'             => 'decimal:2',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return in_array($this->status, ['expired', 'cancelled', 'suspended']);
    }

    public function daysRemaining(): int
    {
        if (!$this->end_date) {
            return 0;
        }
        return max(0, (int) now()->diffInDays($this->end_date, false));
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->daysRemaining() <= $days && $this->isActive();
    }
}

