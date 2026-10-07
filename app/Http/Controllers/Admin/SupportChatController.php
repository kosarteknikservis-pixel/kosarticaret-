<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportChatConversation;
use App\Models\SupportChatMessage;
use App\Services\SupportAssistant\SupportChatPushService;
use App\Support\SupportAssistantConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function show(SupportChatConversation $conversation, SupportChatPushService $push): View
    {
        $conversation->markRead();
        $conversation->load('messages');

        return view('admin.support-chats.show', [
            'conversation' => $conversation,
            'pushSubscribed' => $push->hasSubscription($conversation->visitor_token),
        ]);
    }

    public function reply(Request $request, SupportChatConversation $conversation, SupportChatPushService $push): RedirectResponse
    {
        $data = $request->validate([
            'reply' => ['required', 'string', 'max:1500'],
        ], [], ['reply' => 'yanıt']);

        $text = trim(strip_tags($data['reply']));
        if ($text === '') {
            return back()->withInput()->withErrors(['reply' => 'Yanıt boş olamaz.']);
        }

        $message = SupportChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'agent',
            'author_name' => Str::limit((string) ($request->user()?->name ?: 'Mağaza ekibi'), 110, ''),
            'content' => $text,
        ]);
        $conversation->forceFill([
            'message_count' => $conversation->message_count + 1,
            'unanswered_count' => 0,
            'last_agent_reply_at' => now(),
            'last_message_at' => now(),
            'read_at' => now(),
        ])->save();

        $result = $push->notify($conversation, $message);
        $status = match (true) {
            $result['sent'] > 0 => 'Yanıt gönderildi; müşterinin cihazına bildirim gitti.',
            $result['subscribers'] > 0 => 'Yanıt kaydedildi ancak bildirim iletilemedi. Müşteri siteye döndüğünde yanıtı asistan penceresinde görür.',
            ! $conversation->canReceiveAgentReply() => 'Yanıt kaydedildi. Müşteri aynı tarayıcıdan siteye dönerse asistan penceresinde görür. Bu sohbet bağlantıdan önce açıldığı için telefona bildirim gidemez.',
            default => 'Yanıt kaydedildi. Müşteri bildirim izni vermediği için yanıtı siteye döndüğünde asistan penceresinde görür.',
        };

        return redirect()->route('admin.support-chats.show', $conversation)->with('success', $status);
    }

    public function destroy(SupportChatConversation $conversation): RedirectResponse
    {
        $conversation->delete();

        return redirect()->route('admin.support-chats.index')->with('success', 'Sohbet silindi.');
    }
}
