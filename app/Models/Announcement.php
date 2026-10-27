<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use BelongsToSchool;
    protected $guarded = ['id'];
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
