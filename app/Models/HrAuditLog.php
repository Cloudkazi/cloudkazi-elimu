<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class HrAuditLog extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function log(string $action, Model $auditable, ?array $oldValues = null, ?array $newValues = null, ?string $description = null): self
    {
        $schoolId = $auditable->school_id ?? auth()->user()?->school_id ?? session('active_school_id');

        return self::create([
            'school_id' => $schoolId,
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }
}
