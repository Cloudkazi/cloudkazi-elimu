<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolBankAccount extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class, 'bank_account_id');
    }
}
