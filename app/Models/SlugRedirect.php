<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlugRedirect extends Model
{
    protected $fillable = [
        'from_path',
        'to_path',
        'source',
    ];
}
