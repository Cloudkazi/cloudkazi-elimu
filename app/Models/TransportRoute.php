<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportRoute extends Model
{
    use BelongsToSchool;
    protected $guarded = ['id'];
    public function students(): HasMany { return $this->hasMany(StudentTransport::class); }
}
