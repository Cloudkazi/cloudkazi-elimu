<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAttendance extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
