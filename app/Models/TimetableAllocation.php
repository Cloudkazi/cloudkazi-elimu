<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableAllocation extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(TimetableSlot::class, 'timetable_slot_id');
    }

    public function timetableSlot(): BelongsTo
    {
        return $this->belongsTo(TimetableSlot::class, 'timetable_slot_id');
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function schoolClass()
    {
        return $this->hasOneThrough(
            SchoolClass::class,
            Stream::class,
            'id',               // Foreign key on streams table (stream.id)
            'id',               // Foreign key on school_classes table (school_classes.id)
            'stream_id',        // Local key on timetable_allocations table
            'school_class_id'   // Local key on streams table
        );
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }
}
