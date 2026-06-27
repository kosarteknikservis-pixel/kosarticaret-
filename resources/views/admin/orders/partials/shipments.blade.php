@php
    $shipments = $order->shipments ?? collect();
    $hasSubmitted = $shipments->contains(fn ($s) => ! $s->isDraft());
@endphp

<section class="admin-card overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-bold text-slate-900">DHL Kargo</h2>
            <p class="text-xs text-slate-500 mt-1">Koli planı oluşturun, DHL'e bildirin ve durumları otomatik senkronize edin.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="post" action="{{ route('admin.orders.shipments.plan', $order) }}">
                @csrf
                <button type="submit" class="admin-btn admin-btn-secondary text-xs py-1.5" @disabled($hasSubmitted)>Otomatik plan</button>
            </form>
            @if($shipments->where('status', 'draft')->isNotEmpty())
                <form method="post" action="{{ route('admin.orders.shipments.submit-all', $order) }}" onsubmit="return confirm('Tüm taslak koliler DHL\'e bildirilsin mi?');">
                    @csrf
                    <button type="submit" class="admin-btn admin-btn-primary text-xs py-1.5">Tümünü DHL'e gönder</button>
                </form>
            @endif
        </div>
    </div>

    @if($shipments->isEmpty())
        <div class="p-5 sm:p-6">
            <p class="text-sm text-slate-600">Henüz koli planı yok. Ürünlerin koli/adet dağılımına göre <strong>Otomatik plan</strong> ile başlayın.</p>
            @if($order->items->contains(fn ($item) => $item->product && $item->product->units_per_carton))
                <p class="text-xs text-slate-500 mt-2">Ürünlerde tanımlı koli adedi (units_per_carton) kullanılır.</p>
            @endif
        </div>
    @else
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Koli</th>
                        <th>İçerik</th>
                        <th>Ağırlık</th>
                        <th>Durum</th>
                        <th>Takip</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shipments as $shipment)
                        <tr>
                            <td data-label="Koli" class="font-semibold">#{{ $shipment->package_number }}</td>
                            <td data-label="İçerik" class="text-sm text-slate-700">
                                @foreach($shipment->itemLines() as $line)
                                    <div>{{ $line['quantity'] ?? 1 }}× {{ $line['product_name'] ?? 'Ürün' }}</div>
                                @endforeach
                            </td>
                            <td data-label="Ağırlık" class="text-sm">{{ number_format((float) $shipment->weight_kg, 2, ',', '.') }} kg</td>
                            <td data-label="Durum">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ \App\Support\ShipmentStatus::badgeClasses($shipment->status) }}">
                                    {{ $shipment->statusLabel() }}
                                </span>
                            </td>
                            <td data-label="Takip" class="font-mono text-xs">{{ $shipment->tracking_number ?: '—' }}</td>
                            <td data-label="İşlem" class="text-right">
                                <div class="admin-row-actions justify-end">
                                    @if($shipment->canSubmit())
                                        <form method="post" action="{{ route('admin.orders.shipments.submit', [$order, $shipment]) }}">
                                            @csrf
                                            <button class="admin-btn admin-btn-primary text-xs py-1.5">DHL'e gönder</button>
                                        </form>
                                    @endif
                                    @if($shipment->label_path)
                                        <a href="{{ route('admin.orders.shipments.label', [$order, $shipment]) }}" target="_blank" class="admin-btn admin-btn-secondary text-xs py-1.5">Etiket</a>
                                    @endif
                                    @if($shipment->tracking_number)
                                        <form method="post" action="{{ route('admin.orders.shipments.sync', [$order, $shipment]) }}">
                                            @csrf
                                            <button class="admin-btn admin-btn-secondary text-xs py-1.5">Durum sync</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if($shipment->events->isNotEmpty())
                            <tr>
                                <td colspan="6" class="bg-slate-50/80 px-4 py-3">
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-2">Hareketler</p>
                                    <ul class="space-y-1 text-xs text-slate-600">
                                        @foreach($shipment->events->take(5) as $event)
                                            <li>
                                                <span class="font-semibold">{{ $event->occurred_at?->format('d.m.Y H:i') }}</span>
                                                — {{ $event->description ?: $event->status }}
                                                @if($event->location)
                                                    <span class="text-slate-400">({{ $event->location }})</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
