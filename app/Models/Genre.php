<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $hidden = ['pivot', 'created_at', 'updated_at'];
}
