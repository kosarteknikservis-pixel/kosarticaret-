@props(['brand'])

@php
$icons = [
    'visa', 'mastercard', 'paypal', 'amex', 'visa_electron', 'maestro', 'troy',
];
@endphp

@if(in_array($brand, $icons, true))
    <svg class="shop-pay-icon__svg" viewBox="0 0 64 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
        @switch($brand)
            @case('visa')
                <rect width="64" height="40" rx="4" fill="#fff"/>
                <text x="32" y="26" text-anchor="middle" fill="#1A1F71" font-family="Arial, Helvetica, sans-serif" font-size="14" font-weight="800" font-style="italic">VISA</text>
                @break
            @case('mastercard')
                <rect width="64" height="40" rx="4" fill="#fff"/>
                <circle cx="26" cy="20" r="11" fill="#EB001B"/>
                <circle cx="38" cy="20" r="11" fill="#F79E1B" fill-opacity=".95"/>
                <text x="32" y="36" text-anchor="middle" fill="#231F20" font-family="Arial, sans-serif" font-size="5.5" font-weight="700">mastercard</text>
                @break
            @case('paypal')
                <rect width="64" height="40" rx="4" fill="#fff"/>
                <text x="20" y="24" fill="#003087" font-family="Arial, sans-serif" font-size="13" font-weight="800">P</text>
                <text x="34" y="24" fill="#009CDE" font-family="Arial, sans-serif" font-size="13" font-weight="800">P</text>
                <text x="32" y="34" text-anchor="middle" fill="#003087" font-family="Arial, sans-serif" font-size="6" font-weight="700">PayPal</text>
                @break
            @case('amex')
                <rect width="64" height="40" rx="4" fill="#016FD0"/>
                <text x="32" y="17" text-anchor="middle" fill="#fff" font-family="Arial, sans-serif" font-size="7" font-weight="700">AMERICAN</text>
                <text x="32" y="27" text-anchor="middle" fill="#fff" font-family="Arial, sans-serif" font-size="7" font-weight="700">EXPRESS</text>
                @break
            @case('visa_electron')
                <rect width="64" height="40" rx="4" fill="#1A1F71"/>
                <text x="32" y="18" text-anchor="middle" fill="#fff" font-family="Arial, sans-serif" font-size="9" font-weight="800" font-style="italic">VISA</text>
                <text x="32" y="30" text-anchor="middle" fill="#F7B600" font-family="Arial, sans-serif" font-size="7" font-weight="700">Electron</text>
                @break
            @case('maestro')
                <rect width="64" height="40" rx="4" fill="#fff"/>
                <circle cx="26" cy="18" r="10" fill="#EB001B"/>
                <circle cx="38" cy="18" r="10" fill="#0099DF"/>
                <text x="32" y="34" text-anchor="middle" fill="#231F20" font-family="Arial, sans-serif" font-size="6" font-weight="700">maestro</text>
                @break
            @case('troy')
                <rect width="64" height="40" rx="4" fill="#004B93"/>
                <text x="32" y="25" text-anchor="middle" fill="#fff" font-family="Arial, sans-serif" font-size="12" font-weight="800">TROY</text>
                @break
        @endswitch
    </svg>
@endif
