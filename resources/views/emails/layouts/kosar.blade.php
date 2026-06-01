@php
    $siteName = config('kosar.name', config('app.name'));
    $contactEmail = \App\Models\SiteSetting::get('contact_email', config('kosar.contact.email'));
    $logoUrl = \App\Support\SiteLogo::url();
    $buttonUrl = $buttonUrl ?? null;
    $buttonLabel = $buttonLabel ?? null;
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $subject ?? $siteName }}</title>
</head>
<body style="margin:0;background:#eef3f8;color:#142033;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;color:transparent;">{{ $preheader ?? '' }}</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef3f8;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #dbe5ef;box-shadow:0 18px 50px rgba(15,35,60,.10);">
                    <tr>
                        <td style="background:#14345a;padding:26px 30px;color:#ffffff;">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="height:34px;max-width:180px;object-fit:contain;background:#ffffff;border-radius:10px;padding:7px 10px;">
                            @else
                                <div style="font-size:22px;font-weight:800;letter-spacing:.08em;">{{ $siteName }}</div>
                            @endif
                            <p style="margin:16px 0 0;font-size:13px;color:#b9c9da;letter-spacing:.04em;text-transform:uppercase;">Endüstriyel güven, hızlı hizmet</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:34px 30px;">
                            <h1 style="margin:0 0 14px;font-size:28px;line-height:1.2;color:#10233f;">{{ $title }}</h1>
                            <div style="font-size:15px;line-height:1.75;color:#46566b;">
                                {!! $body !!}
                            </div>
                            @isset($imageUrl)
                                @if($imageUrl)
                                    <div style="margin-top:24px;">
                                        <img src="{{ $imageUrl }}" alt="" style="width:100%;max-height:320px;object-fit:cover;border-radius:18px;border:1px solid #e5edf5;">
                                    </div>
                                @endif
                            @endisset
                            @yield('details')
                            @if($buttonUrl && $buttonLabel)
                                <div style="margin-top:28px;">
                                    <a href="{{ $buttonUrl }}" style="display:inline-block;background:#14345a;color:#ffffff;text-decoration:none;border-radius:14px;padding:14px 22px;font-weight:700;font-size:14px;">{{ $buttonLabel }}</a>
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 30px;background:#f7fafc;border-top:1px solid #e5edf5;color:#718096;font-size:12px;line-height:1.6;">
                            <p style="margin:0 0 8px;">{{ $footerNote ?? 'Bu e-posta KOŞAR Ticaret tarafından gönderilmiştir.' }}</p>
                            <p style="margin:0;">{{ $siteName }} · {{ $contactEmail }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
