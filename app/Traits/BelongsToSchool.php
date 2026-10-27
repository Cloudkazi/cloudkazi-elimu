<?php

namespace App\Traits;

use App\Models\School;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            // For regular school users, strictly force their assigned school_id
            if (Auth::check() && !Auth::user()->isSuperAdmin() && Auth::user()->school_id) {
                $model->school_id = Auth::user()->school_id;
            } elseif (empty($model->school_id)) {
                if (Auth::check() && Auth::user()->school_id) {
                    $model->school_id = Auth::user()->school_id;
                } elseif (Session::has('active_school_id')) {
                    $model->school_id = Session::get('active_school_id');
                }
            }
        });

        static::saving(function ($model) {
            // Prevent non-superadmins from tampering with or reassigning an existing model's school_id
            if (Auth::check() && !Auth::user()->isSuperAdmin() && Auth::user()->school_id) {
                if ($model->exists && $model->isDirty('school_id')) {
                    $model->school_id = $model->getOriginal('school_id');
                }
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
