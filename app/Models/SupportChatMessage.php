<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportChatMessage extends Model
{
    protected $fillable = [
        'conversation_id', 'role', 'content', 'tools', 'products', 'unanswered', 'tokens',
    ];

    protected function casts(): array
    {
        return [
            'tools' => 'array',
            'products' => 'array',
            'unanswered' => 'boolean',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportChatConversation::class, 'conversation_id');
    }
}
