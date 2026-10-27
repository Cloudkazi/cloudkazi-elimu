<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookIssue extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'return_date' => 'date',
        'fine_amount' => 'decimal:2',
        'fine_paid' => 'boolean',
    ];

    public function book(): BelongsTo { return $this->belongsTo(Book::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function isOverdue(): bool
    {
        if ($this->status === 'returned') {
            return false;
        }

        if ($this->status === 'overdue') {
            return true;
        }

        return $this->status === 'issued' && $this->due_date && \Carbon\Carbon::parse($this->due_date)->isPast();
    }

    public function getComputedStatusAttribute(): string
    {
        if ($this->status === 'returned') {
            return 'Returned';
        }

        if ($this->isOverdue()) {
            return 'Overdue';
        }

        return ucfirst($this->status ?? 'Issued');
    }
}
