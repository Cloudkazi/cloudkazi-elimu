<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelRoom extends Model
{
    use BelongsToSchool;
    protected $guarded = ['id'];
    public function dormitory(): BelongsTo { return $this->belongsTo(Dormitory::class); }
}
