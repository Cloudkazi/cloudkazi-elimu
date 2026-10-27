<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolSetting extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'enabled_payment_methods' => 'array',
        'preferred_payment_methods' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(SchoolBankAccount::class, 'school_id', 'school_id');
    }

    /**
     * Check if a specific payment method is enabled for this school
     */
    public function isMethodEnabled(string $method): bool
    {
        // If not configured, default to true for all standard methods
        if (empty($this->enabled_payment_methods)) {
            return true;
        }

        return in_array($method, $this->enabled_payment_methods, true);
    }

    /**
     * Get active M-Pesa environment (sandbox or live/production)
     */
    public function getDarajaEnv(): string
    {
        return $this->daraja_environment === 'production' ? 'production' : 'sandbox';
    }

    /**
     * Get Daraja base API URL
     */
    public function getDarajaBaseUrl(): string
    {
        return $this->getDarajaEnv() === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Get primary bank account
     */
    public function getPrimaryBankAccount(): ?SchoolBankAccount
    {
        return $this->bankAccounts()
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->first();
    }
}
