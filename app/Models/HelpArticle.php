<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpArticle extends Model
{
    use HasFactory;

    protected $table = 'help_articles';

    public const CATEGORIES = [
        'Admissions'       => 'Admissions & Student Registration',
        'Finance'          => 'Finance & Fee Management',
        'Exams'            => 'Examinations & Grading',
        'Timetable'        => 'Timetable & Scheduling',
        'Teachers'         => 'Teachers & Staff',
        'Parents'          => 'Parent Portal & Communication',
        'Students'         => 'Student Portal & Academics',
        'Reports'          => 'Analytics & Reports',
        'System Settings'  => 'System Configuration & Roles',
        'Library'          => 'Library & Book Catalog',
        'Operations'       => 'Transport & Dormitories',
    ];

    protected $fillable = [
        'category',
        'question',
        'answer',
        'keywords',
        'is_active',
        'view_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'view_count' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function chatbotLogs(): HasMany
    {
        return $this->hasMany(ChatbotLog::class, 'matched_article_id');
    }

    /**
     * Search active articles by keywords, question similarity, and full text.
     */
    public function scopeSearch($query, string $term)
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';

        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'mysql') {
            return $query->where(function ($q) use ($term, $like) {
                $q->whereFullText(['question', 'answer', 'keywords'], $term)
                  ->orWhere('question', 'LIKE', $like)
                  ->orWhere('keywords', 'LIKE', $like)
                  ->orWhere('answer', 'LIKE', $like)
                  ->orWhere('category', 'LIKE', $like);
            });
        }

        return $query->where(function ($q) use ($like) {
            $q->where('question', 'LIKE', $like)
              ->orWhere('keywords', 'LIKE', $like)
              ->orWhere('answer', 'LIKE', $like)
              ->orWhere('category', 'LIKE', $like);
        });
    }
}
