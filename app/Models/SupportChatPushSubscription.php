<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportChatPushSubscription extends Model
{
    protected $fillable = [
        'visitor_token', 'endpoint', 'endpoint_hash', 'public_key', 'auth_token', 'content_encoding', 'last_used_at',
    ];

    protected $hidden = ['visitor_token', 'endpoint', 'public_key', 'auth_token'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }
}
