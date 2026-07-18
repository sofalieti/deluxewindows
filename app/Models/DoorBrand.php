<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoorBrand extends Model
{
    protected $table = 'door_brands';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'doors_title',
    ];
}
