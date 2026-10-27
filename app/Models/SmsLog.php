<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    use BelongsToSchool;
    protected $guarded = ['id'];
}
