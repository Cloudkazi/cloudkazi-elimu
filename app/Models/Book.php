<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use BelongsToSchool;
    protected $guarded = ['id'];
    public function category(): BelongsTo { return $this->belongsTo(BookCategory::class, 'category_id'); }
    public function issues(): HasMany { return $this->hasMany(BookIssue::class); }
}
