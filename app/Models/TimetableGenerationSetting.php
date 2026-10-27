<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TimetableGenerationSetting extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'school_days' => 'array',
        'activity_blocks' => 'array',
        'custom_activities' => 'array',
        'requires_double_lessons' => 'boolean',
        'practical_subjects_consecutive' => 'boolean',
        'core_subjects_distributed' => 'boolean',
        'enforce_teacher_availability' => 'boolean',
        'enforce_room_availability' => 'boolean',
        'conflict_free_generation' => 'boolean',
        'strict_validation' => 'boolean',
        'allow_incomplete_timetable' => 'boolean',
        'generate_draft_only' => 'boolean',
    ];
}
