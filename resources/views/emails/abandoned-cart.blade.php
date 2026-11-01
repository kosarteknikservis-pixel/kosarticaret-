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
    @if(!empty($cart->items))
        <div style="margin-top:26px;border:1px solid #e5edf5;border-radius:18px;overflow:hidden;">
            <div style="background:#f7fafc;padding:13px 16px;font-weight:700;color:#142033;">Sepet özeti</div>
            @foreach($cart->items as $item)
                <div style="padding:13px 16px;border-top:1px solid #e5edf5;font-size:14px;color:#46566b;">
                    <strong style="color:#142033;">{{ $item['name'] ?? 'Ürün' }}</strong><br>
                    {{ $item['quantity'] ?? 1 }} adet · {{ number_format((float) ($item['line_total'] ?? 0), 2, ',', '.') }} ₺
                </div>
            @endforeach
            <div style="background:#f7fafc;padding:13px 16px;text-align:right;font-weight:800;color:#14345a;">
                Ara toplam: {{ number_format((float) $cart->subtotal, 2, ',', '.') }} ₺
            </div>
        </div>
    @endif
@endsection
