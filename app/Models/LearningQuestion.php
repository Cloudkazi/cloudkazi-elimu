<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningQuestion extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'options' => 'array',
        'is_published' => 'boolean',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(LearningChapter::class, 'learning_chapter_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(LearningAttempt::class);
    }
}
