<?php

namespace App\Models;

use App\Support\Utf8Mojibake;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportChatMessage extends Model
{
    protected $fillable = [
        'conversation_id', 'role', 'author_name', 'content', 'tools', 'products', 'unanswered', 'tokens',
    ];

    protected function casts(): array
    {
        return [
            'tools' => 'array',
            'products' => 'array',
            'unanswered' => 'boolean',
        ];
    }

    protected function content(): Attribute
    {
        return Attribute::get(fn (?string $value) => Utf8Mojibake::repair($value));
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportChatConversation::class, 'conversation_id');
    }
}
