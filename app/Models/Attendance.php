<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $guarded = [];
    protected $hidden = ['photo_path'];
    protected $casts = ['recorded_at' => 'datetime'];
}
