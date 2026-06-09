@extends('layouts.admin')
@section('title', 'Mesaj: '.$message->subject)

@section('content')
    <x-admin.page-header :title="$message->subject" :subtitle="$message->name.' · '.$message->created_at->format('d.m.Y H:i')">
        @if($message->isQuote())
            <x-slot:actions>
                <span class="admin-badge admin-badge-success">Teklif / proforma</span>
            </x-slot:actions>
        @endif
    </x-admin.page-header>
    <div class="max-w-3xl admin-card p-6 sm:p-8 space-y-4">
        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">Ad soyad</dt><dd class="font-medium">{{ $message->name }}</dd></div>
            <div><dt class="text-slate-500">E-posta</dt><dd><a href="mailto:{{ $message->email }}" class="text-teal-700 font-medium">{{ $message->email }}</a></dd></div>
            @if($message->phone)
                <div><dt class="text-slate-500">Telefon</dt><dd>{{ $message->phone }}</dd></div>
            @endif
            @if($message->isQuote() && is_array($message->meta))
                @if(!empty($message->meta['company']))
                    <div><dt class="text-slate-500">Firma</dt><dd>{{ $message->meta['company'] }}</dd></div>
                @endif
                @if(!empty($message->meta['tax_no']))
                    <div><dt class="text-slate-500">Vergi no</dt><dd>{{ $message->meta['tax_no'] }}</dd></div>
                @endif
            @endif
        </dl>

        @if($message->isQuote() && !empty($message->meta['items']))
            <div class="border-t pt-4 overflow-x-auto">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Sepet kalemleri</h3>
                <table class="admin-table text-sm">
                    <thead><tr><th>Ürün</th><th>Adet</th><th>Birim</th><th>Satır</th></tr></thead>
                    <tbody>
                        @foreach($message->meta['items'] as $item)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $item['name'] ?? '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $item['sku'] ?? $item['slug'] ?? '' }}</p>
                                </td>
                                <td>{{ $item['quantity'] ?? 0 }}</td>
                                <td>{{ isset($item['unit_price']) ? number_format($item['unit_price'], 2, ',', '.').' ₺' : '—' }}</td>
                                <td>{{ isset($item['line_total']) ? number_format($item['line_total'], 2, ',', '.').' ₺' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(isset($message->meta['subtotal']))
                    <p class="mt-3 text-sm font-semibold text-slate-900">Ara toplam: {{ number_format($message->meta['subtotal'], 2, ',', '.') }} ₺</p>
                @endif
            </div>
        @endif

        <div class="border-t pt-4">
            <p class="text-slate-700 whitespace-pre-wrap leading-relaxed">{{ $message->body }}</p>
        </div>
        <div class="admin-form-actions border-t pt-4">
            <a href="{{ route('admin.contact-messages.index') }}" class="admin-btn admin-btn-secondary">Listeye dön</a>
            <form method="post" action="{{ route('admin.contact-messages.destroy', $message) }}" onsubmit="return confirm('Mesaj silinsin mi?')">
                @csrf @method('DELETE')
                <button type="submit" class="admin-btn text-rose-700 border-rose-200 hover:bg-rose-50">Sil</button>
            </form>
        </div>
    </div>
@endsection
