<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bovine extends Model
{
    protected $fillable = [
        'tag_number',
        'breed',
        'estimated_weight',
        'calculated_at',
    ];
}
