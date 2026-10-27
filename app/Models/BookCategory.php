<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookCategory extends Model
{
    use BelongsToSchool;
    protected $guarded = ['id'];
    public function books(): HasMany { return $this->hasMany(Book::class, 'category_id'); }
}
