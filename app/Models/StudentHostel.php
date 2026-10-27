<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentHostel extends Model
{
    use BelongsToSchool;
    protected $guarded = ['id'];
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function dormitory(): BelongsTo { return $this->belongsTo(Dormitory::class); }
    public function room(): BelongsTo { return $this->belongsTo(HostelRoom::class, 'hostel_room_id'); }
}
