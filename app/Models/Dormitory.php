<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dormitory extends Model
{
    use BelongsToSchool;
    protected $guarded = ['id'];
    public function rooms(): HasMany { return $this->hasMany(HostelRoom::class); }
    public function students(): HasMany { return $this->hasMany(StudentHostel::class); }
}
