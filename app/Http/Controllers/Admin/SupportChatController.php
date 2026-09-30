<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportChatConversation;
use App\Models\SupportChatMessage;
use App\Support\SupportAssistantConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportChatController extends Controller
{
    public function index(Request $request): View
    {
        $filter = (string) $request->query('filtre', '');
        $query = SupportChatConversation::query()
            ->select('support_chat_conversations.*')
            ->addSelect(['first_question' => SupportChatMessage::query()
                ->select('content')
                ->whereColumn('conversation_id', 'support_chat_conversations.id')
                ->where('role', 'user')
                ->orderBy('id')
                ->limit(1)])
            ->orderByDesc('last_message_at');

        if ($filter === 'cevapsiz') {
            $query->where('unanswered_count', '>', 0);
        } elseif ($filter === 'aktarilan') {
            $query->whereNotNull('handed_off_at');
        }

        $unansweredQuestions = SupportChatMessage::query()
            ->where('unanswered', true)
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(function (SupportChatMessage $answer) {
                $question = SupportChatMessage::query()
                    ->where('conversation_id', $answer->conversation_id)
                    ->where('role', 'user')
                    ->where('id', '<', $answer->id)
                    ->latest('id')
                    ->first();

                return [
                    'question' => $question?->content,
                    'conversation_id' => $answer->conversation_id,
                    'created_at' => $answer->created_at,
                ];
            })
            ->filter(fn (array $row) => filled($row['question']))
            ->values();

        return view('admin.support-chats.index', [
            'conversations' => $query->paginate(30)->withQueryString(),
            'filter' => $filter,
            'enabled' => SupportAssistantConfig::isEnabled(),
            'stats' => [
                'today' => SupportChatConversation::query()->whereDate('created_at', today())->count(),
                'week' => SupportChatConversation::query()->where('created_at', '>=', now()->subDays(7))->count(),
                'unanswered' => SupportChatConversation::query()->where('unanswered_count', '>', 0)->where('created_at', '>=', now()->subDays(7))->count(),
                'handoff' => SupportChatConversation::query()->whereNotNull('handed_off_at')->where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'unansweredQuestions' => $unansweredQuestions,
        ]);
    }

    public function show(SupportChatConversation $conversation): View
    {
        $conversation->markRead();
        $conversation->load('messages');

        return view('admin.support-chats.show', ['conversation' => $conversation]);
    }

    public function destroy(SupportChatConversation $conversation): RedirectResponse
    {
        $conversation->delete();

        return redirect()->route('admin.support-chats.index')->with('success', 'Sohbet silindi.');
    }
}
