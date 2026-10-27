<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    public function hod(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hod_id');
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }
}
