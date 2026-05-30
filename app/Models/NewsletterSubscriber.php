<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = ['email', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
