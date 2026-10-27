<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransport extends Model
{
    use BelongsToSchool;
    protected $guarded = ['id'];
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function route(): BelongsTo { return $this->belongsTo(TransportRoute::class, 'transport_route_id'); }
}
