@props(['platform', 'class' => 'w-4 h-4'])

@php
$iconClass = $attributes->get('class', $class);
@endphp

@if($platform === 'instagram')
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" {{ $attributes->merge(['class' => $iconClass]) }} aria-hidden="true">
    <path d="M7.8 2h8.4A5.8 5.8 0 0122 7.8v8.4A5.8 5.8 0 0116.2 22H7.8A5.8 5.8 0 012 16.2V7.8A5.8 5.8 0 017.8 2m0 2A3.8 3.8 0 004 7.8v8.4A3.8 3.8 0 007.8 20h8.4a3.8 3.8 0 003.8-3.8V7.8A3.8 3.8 0 0016.2 4H7.8m8.65 1.5a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5M12 7a5 5 0 110 10 5 5 0 010-10m0 2a3 3 0 100 6 3 3 0 000-6z"/>
</svg>
@elseif($platform === 'facebook')
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" {{ $attributes->merge(['class' => $iconClass]) }} aria-hidden="true">
    <path d="M22 12a10 10 0 10-11.5 9.9v-7H7.9V12h2.6V9.8c0-2.6 1.5-4 3.9-4 1.1 0 2.3.2 2.3.2v2.5h-1.3c-1.3 0-1.7.8-1.7 1.6V12h2.9l-.5 2.9h-2.4v7A10 10 0 0022 12z"/>
</svg>
@elseif($platform === 'youtube')
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" {{ $attributes->merge(['class' => $iconClass]) }} aria-hidden="true">
    <path d="M21.6 7.2a2.5 2.5 0 00-1.8-1.8C18 5 12 5 12 5s-6 0-7.8.4A2.5 2.5 0 002.4 7.2 26 26 0 002 12a26 26 0 00.4 4.8 2.5 2.5 0 001.8 1.8C6 19 12 19 12 19s6 0 7.8-.4a2.5 2.5 0 001.8-1.8A26 26 0 0022 12a26 26 0 00-.4-4.8zM10 15.5v-7l6 3.5-6 3.5z"/>
</svg>
@elseif($platform === 'linkedin')
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" {{ $attributes->merge(['class' => $iconClass]) }} aria-hidden="true">
    <path d="M19 3a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h14M8.3 10.5H5.8V18h2.5v-7.5zM7 8.8a1.45 1.45 0 110-2.9 1.45 1.45 0 010 2.9zm12.5 9.2h-2.5v-3.7c0-.9 0-2-1.2-2s-1.4 1-1.4 2v3.7H12v-7.5h2.4v1c.3-.6 1.1-1.2 2.3-1.2 2.5 0 3 1.6 3 3.7V18z"/>
</svg>
@elseif($platform === 'x')
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" {{ $attributes->merge(['class' => $iconClass]) }} aria-hidden="true">
    <path d="M18.9 2H22l-6.8 7.8L22.7 22h-6.7l-5.2-6.8L5 22H2l7.3-8.4L1.4 2h6.9l4.7 6.2L18.9 2zm-1.2 18h1.9L7.1 3.9H5.1L17.7 20z"/>
</svg>
@elseif($platform === 'tiktok')
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" {{ $attributes->merge(['class' => $iconClass]) }} aria-hidden="true">
    <path d="M16.6 5.8c1 1.2 2.4 2 4 2.1v3.4c-1.5 0-2.9-.5-4-1.3v5.9a5.6 5.6 0 11-5.6-5.6c.3 0 .7 0 1 .1v3.5a2.2 2.2 0 00-1-.2 2.3 2.3 0 102.3 2.3V2h3.3v3.8z"/>
</svg>
@endif
