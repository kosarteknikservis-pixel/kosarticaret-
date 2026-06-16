<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kargo etiketi · {{ $label->orderNumber() }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        @page {
            size: 100mm 70mm;
            margin: 0;
        }

        body {
            background: #f1f5f9;
        }

        .preview {
            width: min(100%, 480px);
            margin: 0 auto;
            padding: 20px 16px 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }

        .preview-card {
            width: 100%;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        }

        .preview-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .preview-head__title {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.3;
        }

        .preview-head__meta {
            margin-top: 4px;
            font-size: 12px;
            color: #64748b;
            line-height: 1.4;
        }

        .preview-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .preview-btn {
            appearance: none;
            border: 0;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .preview-btn--primary {
            background: #0f172a;
            color: #fff;
        }

        .preview-btn--secondary {
            background: #fff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
        }

        .preview-tip {
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 11px;
            color: #64748b;
            line-height: 1.45;
        }

        .label-sheet {
            width: 100mm;
            height: 70mm;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            overflow: hidden;
        }

        .label {
            width: 100mm;
            height: 70mm;
            padding: 2mm 2.4mm 1.8mm;
            display: grid;
            grid-template-rows: auto auto 1fr auto auto;
            gap: 0;
            overflow: hidden;
        }

        /* —— Üst: gönderici + sipariş —— */
        .label-head {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2mm;
            align-items: start;
            padding-bottom: 1.2mm;
            border-bottom: 0.3mm solid #0f172a;
        }

        .label-head__brand {
            display: flex;
            align-items: center;
            gap: 1.4mm;
            min-width: 0;
        }

        .label-head__logo {
            width: 16mm;
            height: 6.5mm;
            object-fit: contain;
            object-position: left center;
            flex-shrink: 0;
        }

        .label-head__brand-text {
            min-width: 0;
        }

        .label-head__brand-name {
            font-size: 7pt;
            font-weight: 800;
            letter-spacing: 0.03em;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .label-head__brand-phone {
            margin-top: 0.3mm;
            font-size: 5.6pt;
            color: #475569;
            line-height: 1.1;
        }

        .label-head__order {
            text-align: right;
            padding: 0.8mm 1.2mm;
            border: 0.25mm solid #0f172a;
            border-radius: 0.8mm;
            flex-shrink: 0;
        }

        .label-head__order-no {
            font-size: 7.6pt;
            font-weight: 800;
            letter-spacing: 0.04em;
            line-height: 1;
            white-space: nowrap;
        }

        .label-head__order-date {
            margin-top: 0.4mm;
            font-size: 5.2pt;
            color: #475569;
            line-height: 1;
            white-space: nowrap;
        }

        /* —— Barkod —— */
        .label-code {
            padding: 1mm 0 0.6mm;
        }

        .label-code__bars {
            height: 7.2mm;
            overflow: hidden;
        }

        .label-code__text {
            margin-top: 0.3mm;
            font-size: 5.4pt;
            font-weight: 700;
            text-align: center;
            letter-spacing: 0.1em;
            line-height: 1;
        }

        /* —— Alıcı —— */
        .label-recipient {
            padding: 1mm 0 0.8mm;
            min-height: 0;
            overflow: hidden;
        }

        .label-recipient__tag {
            display: inline-block;
            margin-bottom: 0.6mm;
            padding: 0.2mm 1mm;
            font-size: 5pt;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #fff;
            background: #0f172a;
            border-radius: 0.4mm;
            line-height: 1.3;
        }

        .label-recipient__name {
            font-size: 8.2pt;
            font-weight: 800;
            line-height: 1.12;
            margin-bottom: 0.45mm;
        }

        .label-recipient__line {
            font-size: 6.2pt;
            line-height: 1.2;
            margin-bottom: 0.3mm;
            word-break: break-word;
        }

        .label-recipient__line--muted {
            color: #334155;
        }

        .label-recipient__city {
            font-size: 6.4pt;
            font-weight: 700;
            line-height: 1.15;
        }

        /* —— Detay grid —— */
        .label-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8mm 2mm;
            padding: 1mm 0;
            border-top: 0.2mm solid #cbd5e1;
            border-bottom: 0.2mm solid #cbd5e1;
            align-content: start;
        }

        .label-details__item {
            min-width: 0;
        }

        .label-details__item--full {
            grid-column: 1 / -1;
        }

        .label-details__key {
            display: block;
            font-size: 4.8pt;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            line-height: 1.1;
            margin-bottom: 0.25mm;
        }

        .label-details__val {
            display: block;
            font-size: 5.8pt;
            font-weight: 600;
            line-height: 1.18;
            word-break: break-word;
        }

        .label-badge {
            display: inline-block;
            width: 100%;
            text-align: center;
            border: 0.3mm solid #0f172a;
            border-radius: 0.6mm;
            padding: 0.6mm 1mm;
            font-size: 5.8pt;
            font-weight: 800;
            letter-spacing: 0.03em;
            line-height: 1.15;
        }

        /* —— Alt: kargo —— */
        .label-foot {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2mm;
            padding-top: 0.8mm;
            font-size: 5.6pt;
            line-height: 1.15;
        }

        .label-foot__item {
            min-width: 0;
        }

        .label-foot__key {
            display: block;
            font-size: 4.8pt;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.2mm;
        }

        .label-foot__val {
            display: block;
            font-weight: 700;
            word-break: break-word;
        }

        .label-foot__item:last-child {
            text-align: right;
        }

        @media print {
            html, body {
                width: 100mm;
                height: 70mm;
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .preview {
                width: 100mm;
                padding: 0;
                margin: 0;
            }

            .label-sheet {
                margin: 0;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="preview">
        <div class="preview-card no-print">
            <div class="preview-head">
                <div>
                    <div class="preview-head__title">Kargo etiketi</div>
                    <div class="preview-head__meta">
                        {{ $label->orderNumber() }} · {{ $label->recipientName() }}<br>
                        100 × 70 mm termal etiket
                    </div>
                </div>
                <div class="preview-actions">
                    <button type="button" class="preview-btn preview-btn--primary" onclick="window.print()">Yazdır</button>
                    <button type="button" class="preview-btn preview-btn--secondary" onclick="window.close()">Kapat</button>
                </div>
            </div>

            <p class="preview-tip">
                Yazıcı ayarlarında kağıt boyutunu <strong>100 × 70 mm</strong>, ölçeği <strong>%100</strong> ve kenar boşluklarını <strong>0</strong> yapın.
            </p>
        </div>

        <div class="label-sheet">
                <div class="label">
                    <header class="label-head">
                        <div class="label-head__brand">
                            @if($label->logoUrl())
                                <img src="{{ $label->logoUrl() }}" alt="{{ $label->senderName() }}" class="label-head__logo">
                            @endif
                            <div class="label-head__brand-text">
                                <div class="label-head__brand-name">{{ $label->logoUrl() ? $label->senderName() : strtoupper($label->senderName()) }}</div>
                                @if($label->senderPhone())
                                    <div class="label-head__brand-phone">{{ $label->senderPhone() }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="label-head__order">
                            <div class="label-head__order-no">{{ $label->orderNumber() }}</div>
                            <div class="label-head__order-date">{{ $label->orderDate() }}</div>
                        </div>
                    </header>

                    <section class="label-code">
                        <div class="label-code__bars">{!! $barcodeSvg !!}</div>
                        <div class="label-code__text">{{ $label->barcodeValue() }}</div>
                    </section>

                    <section class="label-recipient">
                        <span class="label-recipient__tag">Alıcı</span>
                        <div class="label-recipient__name">{{ $label->recipientName() }}</div>
                        <div class="label-recipient__line label-recipient__line--muted">{{ $label->recipientPhone() }}</div>
                        <div class="label-recipient__line">{{ $label->addressLine() }}</div>
                        <div class="label-recipient__city">
                            {{ $label->cityDistrict() }}
                            @if($label->postalCode())
                                · {{ $label->postalCode() }}
                            @endif
                        </div>
                    </section>

                    <section class="label-details">
                        <div class="label-details__item">
                            <span class="label-details__key">Ürün</span>
                            <span class="label-details__val">{{ $label->productSummary() }}</span>
                        </div>
                        <div class="label-details__item">
                            <span class="label-details__key">Adet</span>
                            <span class="label-details__val">{{ $label->itemCount() }} parça</span>
                        </div>

                        @if($label->isCashOnDelivery())
                            <div class="label-details__item label-details__item--full">
                                <span class="label-badge">{{ $label->orderPaymentSummary() }}</span>
                            </div>
                            <div class="label-details__item">
                                <span class="label-details__key">Kargo ödemesi</span>
                                <span class="label-details__val">{{ $label->shippingPaymentSummary() }}</span>
                            </div>
                        @else
                            <div class="label-details__item">
                                <span class="label-details__key">Ödeme</span>
                                <span class="label-details__val">{{ $label->orderPaymentSummary() }}</span>
                            </div>
                            <div class="label-details__item">
                                <span class="label-details__key">Kargo ödemesi</span>
                                <span class="label-details__val">{{ $label->shippingPaymentSummary() }}</span>
                            </div>
                        @endif

                        @if($label->salesChannelLabel())
                            <div class="label-details__item label-details__item--full">
                                <span class="label-details__key">Satış kanalı</span>
                                <span class="label-details__val">{{ $label->salesChannelLabel() }}</span>
                            </div>
                        @endif
                    </section>

                    <footer class="label-foot">
                        <div class="label-foot__item">
                            <span class="label-foot__key">Kargo firması</span>
                            <span class="label-foot__val">{{ $label->cargoCompany() }}</span>
                        </div>
                        <div class="label-foot__item">
                            <span class="label-foot__key">Takip no</span>
                            <span class="label-foot__val">{{ $label->trackingNumber() ?? '—' }}</span>
                        </div>
                    </footer>
                </div>
            </div>
    </div>

    @if($autoPrint)
        <script>
            window.addEventListener('load', function () {
                window.setTimeout(function () {
                    window.print();
                }, 600);
            });
        </script>
    @endif
</body>
</html>
