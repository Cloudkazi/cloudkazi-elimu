<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplineIncident extends Model
{
    use BelongsToSchool;
    protected $guarded = ['id'];
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
}
