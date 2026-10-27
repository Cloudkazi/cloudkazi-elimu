<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotLog extends Model
{
    use HasFactory;

    protected $table = 'chatbot_logs';

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'question',
        'answer',
        'matched_article_id',
        'source',
        'was_helpful',
    ];

    protected $casts = [
        'was_helpful' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matchedArticle(): BelongsTo
    {
        return $this->belongsTo(HelpArticle::class, 'matched_article_id');
    }
}
