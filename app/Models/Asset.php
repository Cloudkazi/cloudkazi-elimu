<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use BelongsToSchool;
    protected $guarded = ['id'];
}
