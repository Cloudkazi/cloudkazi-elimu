<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    public function terms(): HasMany
    {
        return $this->hasMany(AcademicTerm::class);
    }

    public function currentTerm()
    {
        return $this->terms()->where('is_current', true)->first();
    }
}
