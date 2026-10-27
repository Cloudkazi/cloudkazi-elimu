<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalDocument extends Model
{
    use HasFactory;

public const TYPE_PRIVACY_POLICY = 'privacy-policy';
public const TYPE_TERMS_CONDITIONS = 'terms-conditions';

    protected $table = 'legal_documents';

    protected $fillable = [
        'type',
        'title',
        'content',
        'version',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the active Privacy Policy document.
     */
    public static function getPrivacyPolicy(): ?self
    {
        return static::active()
            ->ofType(self::TYPE_PRIVACY_POLICY)
            ->latest('updated_at')
            ->first();
    }

    /**
     * Get the active Terms & Conditions document.
     */
    public static function getTermsAndConditions(): ?self
    {
        return static::active()
            ->ofType(self::TYPE_TERMS_CONDITIONS)
            ->latest('updated_at')
            ->first();
    }
}
