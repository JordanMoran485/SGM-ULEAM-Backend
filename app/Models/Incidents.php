<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incidents extends Model
{
    //
    protected $fillable = [
        'title',
        'description',
        'image',
        'location',
        'status',
    ];
}
