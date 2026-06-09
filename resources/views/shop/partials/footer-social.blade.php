@php
    $socialLinks = \App\Support\SocialMediaLinks::configured();
@endphp

@if($socialLinks !== [])
    <div class="kfooter__social">
        <p class="kfooter__social-label">{{ __('shop.footer_social_title') }}</p>
        <ul class="kfooter__social-list" role="list">
            @foreach($socialLinks as $link)
                <li>
                    <a href="{{ $link['url'] }}"
                       class="kfooter__social-link kfooter__social-link--{{ $link['platform'] }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="{{ $link['label'] }}">
                        <x-shop.social-icon :platform="$link['platform']" class="kfooter__social-icon" />
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
