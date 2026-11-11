@props(['title', 'subtitle' => null])

<div {{ $attributes->class(['admin-page-header']) }}>
    <div class="min-w-0">
        <h2 class="admin-page-title">{{ $title }}</h2>
        @if($subtitle)<p class="admin-page-sub">{{ $subtitle }}</p>@endif
    </div>
    @if(isset($actions))
        <div class="admin-page-header__actions">{{ $actions }}</div>
    @endif
</div>
