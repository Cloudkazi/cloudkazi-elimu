<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningChapterProgress extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'mastery_percent' => 'decimal:2',
        'completion_percent' => 'decimal:2',
        'last_attempted_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(LearningChapter::class, 'learning_chapter_id');
    }
}
