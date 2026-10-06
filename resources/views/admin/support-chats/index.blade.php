@extends('layouts.admin')
@section('title', 'AI sohbetler')

@section('content')
    <x-admin.page-header title="AI sohbetler" subtitle="Destek asistanının müşterilerle yaptığı görüşmeler, cevaplayamadığı sorular ve WhatsApp aktarımları">
        <x-slot:actions>
            <a href="{{ route('admin.settings.edit', ['tab' => 'integrations']) }}" class="admin-btn admin-btn-secondary">Asistan ayarları</a>
        </x-slot:actions>
    </x-admin.page-header>

    @unless($enabled)
        <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-6">
            Asistan şu an sitede görünmüyor. OpenAI API anahtarını girip «Destek asistanını sitede göster» seçeneğini açın.
        </p>
    @endunless

    <div class="admin-dashboard-stats mb-6">
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__label">Bugün</span>
            <strong>{{ number_format($stats['today']) }}</strong>
            <small>yeni sohbet</small>
        </div>
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__label">Son 7 gün</span>
            <strong>{{ number_format($stats['week']) }}</strong>
            <small>toplam sohbet</small>
        </div>
        <div class="admin-metric-card admin-analytics-metric">
            <span class="admin-metric-card__label">Cevapsız kalan</span>
            <strong>{{ number_format($stats['unanswered']) }}</strong>
            <small>son 7 gün, bilgi eksiği olan sohbet</small>
        </div>
        <div class="admin-metric-card admin-metric-card--primary admin-analytics-metric">
            <span class="admin-metric-card__label">WhatsApp'a yönlenen</span>
            <strong>{{ number_format($stats['handoff']) }}</strong>
            <small>son 7 gün</small>
        </div>
    </div>

    @if($unansweredQuestions->isNotEmpty())
        <section class="admin-card p-5 sm:p-6 mb-6">
            <h2 class="text-lg font-semibold text-slate-900">Asistanın cevaplayamadığı son sorular</h2>
            <p class="text-sm text-slate-500 mt-1 mb-4">Bu sorular ürün açıklaması, SSS veya kargo-iade sayfasına eklenirse asistan bir dahaki sefere yanıt verebilir.</p>
            <ul class="divide-y divide-slate-100">
                @foreach($unansweredQuestions as $row)
                    <li class="py-3 flex flex-wrap items-start justify-between gap-2">
                        <p class="text-sm text-slate-800 min-w-0 flex-1 break-words">{{ \Illuminate\Support\Str::limit($row['question'], 220) }}</p>
                        <span class="flex items-center gap-3 shrink-0">
                            <span class="text-xs text-slate-400 whitespace-nowrap">{{ $row['created_at']->format('d.m.Y H:i') }}</span>
                            <a href="{{ route('admin.support-chats.show', $row['conversation_id']) }}" class="text-sm font-semibold text-teal-700 hover:text-teal-900">Sohbet</a>
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="flex flex-wrap gap-2 mb-4">
        @foreach(['' => 'Tümü', 'cevapsiz' => 'Cevapsız kalanlar', 'aktarilan' => 'WhatsApp\'a yönlenenler'] as $key => $label)
            <a href="{{ route('admin.support-chats.index', $key !== '' ? ['filtre' => $key] : []) }}"
               class="admin-btn {{ $filter === $key ? 'admin-btn-primary' : 'admin-btn-secondary' }}">{{ $label }}</a>
        @endforeach
    </div>

    <section class="admin-card overflow-hidden">
        @if($conversations->isEmpty())
            <p class="p-10 text-center text-slate-500">Henüz sohbet yok.</p>
        @else
            <table class="admin-table admin-table--stack">
                <thead>
                    <tr>
                        <th>İlk soru</th>
                        <th>Durum</th>
                        <th>Mesaj</th>
                        <th>Son mesaj</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($conversations as $conversation)
                        <tr class="{{ $conversation->read_at === null && $conversation->needsAttention() ? 'bg-amber-50/50' : '' }}">
                            <td data-label="İlk soru" class="max-w-md">
                                <p class="text-sm text-slate-800 break-words">{{ \Illuminate\Support\Str::limit($conversation->first_question ?: '—', 140) }}</p>
                                @if($conversation->page_url)
                                    <p class="text-xs text-slate-400 mt-0.5 truncate max-w-xs">{{ $conversation->page_url }}</p>
                                @endif
                            </td>
                            <td data-label="Durum">
                                <span class="flex flex-wrap gap-1">
                                    @if($conversation->unanswered_count > 0)
                                        <span class="admin-badge admin-badge-warning">Cevapsız {{ $conversation->unanswered_count }}</span>
                                    @endif
                                    @if($conversation->handed_off_at)
                                        <span class="admin-badge admin-badge-success">WhatsApp</span>
                                    @endif
                                    @if($conversation->last_agent_reply_at)
                                        <span class="admin-badge bg-sky-50 text-sky-800">Temsilci yanıtı</span>
                                    @endif
                                    @if($conversation->unanswered_count === 0 && ! $conversation->handed_off_at && ! $conversation->last_agent_reply_at)
                                        <span class="admin-badge admin-badge-muted">Yanıtlandı</span>
                                    @endif
                                </span>
                            </td>
                            <td data-label="Mesaj" class="text-sm text-slate-600">{{ $conversation->message_count }}</td>
                            <td data-label="Son mesaj" class="text-xs text-slate-500 whitespace-nowrap">{{ $conversation->last_message_at?->format('d.m.Y H:i') }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.support-chats.show', $conversation) }}" class="text-sm font-semibold text-teal-700 hover:text-teal-900">Görüntüle</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        @if($conversations->hasPages())<div class="p-4 border-t">{{ $conversations->links() }}</div>@endif
    </section>
@endsection
