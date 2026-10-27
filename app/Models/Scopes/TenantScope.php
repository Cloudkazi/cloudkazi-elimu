<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $schoolId = null;

        if (Auth::check()) {
            if (Auth::user()->isSuperAdmin()) {
                // If super admin has not selected a specific tenant school, do not restrict globally
                if (!Session::has('active_school_id')) {
                    return;
                }
                $schoolId = Session::get('active_school_id');
            } else {
                // For non-superadmin school users, their school_id is strictly authoritative
                $schoolId = Auth::user()->school_id;
            }
        } elseif (Session::has('active_school_id')) {
            $schoolId = Session::get('active_school_id');
        }

        // If in console with no session and no authenticated school user, skip scope
        if (app()->runningInConsole() && !$schoolId) {
            return;
        }

        if ($schoolId) {
            $builder->where($model->getTable() . '.school_id', $schoolId);
        }
    }
}
