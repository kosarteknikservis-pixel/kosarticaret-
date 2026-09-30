<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportChatConversation extends Model
{
    protected $fillable = [
        'uuid', 'ip_hash', 'page_url', 'message_count', 'unanswered_count', 'total_tokens',
        'handed_off_at', 'read_at', 'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'handed_off_at' => 'datetime',
            'read_at' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportChatMessage::class, 'conversation_id')->orderBy('id');
    }

    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->whereNull('read_at')
            ->where(fn (Builder $q) => $q->where('unanswered_count', '>', 0)->orWhereNotNull('handed_off_at'));
    }

    public function needsAttention(): bool
    {
        return $this->unanswered_count > 0 || $this->handed_off_at !== null;
    }

    public function markRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }
}
