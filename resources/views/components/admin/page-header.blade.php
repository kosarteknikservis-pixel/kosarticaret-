@props(['title', 'subtitle' => null])

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h2 class="admin-page-title">{{ $title }}</h2>
        @if($subtitle)<p class="admin-page-sub">{{ $subtitle }}</p>@endif
    </div>
    @if(isset($actions))
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
