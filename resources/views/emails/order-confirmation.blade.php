@php
    $settings = $template->settings ?? [];
    $subject = $template->render('subject', $params);
    $preheader = $template->render('preheader', $params);
    $title = $template->render('title', $params);
    $body = \App\Support\SafeMailHtml::render($template->render('body', $params), $template->body_is_html);
    $buttonLabel = $template->render('button_label', $params);
    $buttonUrl = $template->render('button_url', $params);
    $footerNote = $template->render('footer_note', $params);
    $kurumsalFatura = $order->shipping_address['teslimat']['kurumsalFatura'] ?? null;
@endphp

@extends('emails.layouts.kosar')

@section('details')
    @if(($settings['show_items'] ?? true) && $order->items->isNotEmpty())
        <div style="margin-top:26px;border:1px solid #e5edf5;border-radius:18px;overflow:hidden;">
            <div style="background:#f7fafc;padding:13px 16px;font-weight:700;color:#142033;">Sipariş özeti</div>
            @foreach($order->items as $item)
                <div style="padding:13px 16px;border-top:1px solid #e5edf5;font-size:14px;color:#46566b;">
                    <strong style="color:#142033;">{{ $item->product_name }}</strong><br>
                    {{ $item->quantity }} adet · {{ number_format($item->line_total, 2, ',', '.') }} ₺
                </div>
            @endforeach
            <div style="background:#f7fafc;padding:13px 16px;text-align:right;font-weight:800;color:#14345a;">
                Toplam: {{ number_format($order->total, 2, ',', '.') }} ₺
            </div>
        </div>
    @endif
    @if($kurumsalFatura)
        <p style="margin:18px 0 0;font-size:13px;color:#718096;"><strong>Kurumsal fatura:</strong> {{ $kurumsalFatura['firmaAdi'] ?? '' }} · Vergi No: {{ $kurumsalFatura['vergiNumarasi'] ?? '' }} · Vergi Dairesi: {{ $kurumsalFatura['vergiDairesi'] ?? '' }}</p>
    @endif
    @if(($settings['show_review_cta'] ?? true) && $order->items->isNotEmpty())
        @php
            $order->loadMissing('items.product');
            $reviewItems = $order->items->filter(fn ($item) => $item->product !== null);
        @endphp
        @if($reviewItems->isNotEmpty())
            <div style="margin-top:24px;padding:18px 16px;background:#f0f7ff;border:1px solid #cfe0f5;border-radius:14px;">
                <p style="margin:0 0 10px;font-weight:700;color:#142033;">Deneyiminizi paylaşın</p>
                <p style="margin:0 0 12px;font-size:14px;line-height:1.5;color:#46566b;">Satın aldığınız ürünler hakkında yorum bırakarak diğer müşterilere yardımcı olabilirsiniz.</p>
                @foreach($reviewItems->take(3) as $item)
                    <a href="{{ route('products.show', $item->product) }}#pdp-panel-reviews" style="display:block;margin-top:8px;font-size:14px;color:#1a4d8f;text-decoration:none;">{{ $item->product_name }} — yorum yaz</a>
                @endforeach
            </div>
        @endif
    @endif
@endsection
