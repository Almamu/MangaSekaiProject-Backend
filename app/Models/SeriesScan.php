<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperSeriesScan
 */
class SeriesScan extends Model
{
    protected $fillable = ['library_id', 'basepath', 'serie_id'];

    public $timestamps = false;
    //
}
