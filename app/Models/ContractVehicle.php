<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractVehicle extends Model
{
    protected $fillable = [
        'name',
        'full_name',
        'agency',
        'type',
        'status',
        'description',
        'alqimi_use',
        'url',
    ];
}
