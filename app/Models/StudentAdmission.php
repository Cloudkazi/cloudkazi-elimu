<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAdmission extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    public function targetClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'target_class_id');
    }
}
