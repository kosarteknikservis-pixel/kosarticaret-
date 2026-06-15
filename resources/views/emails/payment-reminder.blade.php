@php
    $settings = $template->settings ?? [];
    $subject = $template->render('subject', $params);
    $preheader = $template->render('preheader', $params);
    $title = $template->render('title', $params);
    $body = \App\Support\SafeMailHtml::render($template->render('body', $params), $template->body_is_html);
    $buttonLabel = $template->render('button_label', $params);
    $buttonUrl = $template->render('button_url', $params);
    $footerNote = $template->render('footer_note', $params);
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
@endsection
