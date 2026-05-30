<div class="hp-block {{ !$banner->active ? 'hp-block--off' : '' }} {{ $banner->isBanner() ? 'hp-block--wide' : '' }}"
     data-id="{{ $banner->id }}"
     data-type="{{ $banner->type }}"
     data-panel-url="{{ route('admin.home-banners.panel', $banner) }}"
     role="button"
     tabindex="0"
     aria-label="{{ $banner->displayTitle() ?: $banner->typeLabel() }}">
    <div class="hp-block__toolbar">
        <span class="hp-block__handle" title="Sürükle">⋮⋮</span>
        <span class="hp-block__type">{{ $banner->typeLabel() }}</span>
        <label class="hp-block__toggle" title="Yayında">
            <input type="checkbox" class="hp-block__active-input" data-quick-active {{ $banner->active ? 'checked' : '' }}>
        </label>
    </div>
    <div class="hp-block__preview">
        @if($banner->isProductList())
            <div class="hp-block__empty hp-block__empty--list">{{ $banner->listSourceSummary() }}</div>
        @elseif($banner->imageUrl())
            <img src="{{ $banner->imageUrl() }}" alt="" class="hp-block__img">
        @else
            <div class="hp-block__empty">Görsel yok</div>
        @endif
        @if($banner->displayTitle())
            <span class="hp-block__label">{{ $banner->displayTitle() }}</span>
        @endif
    </div>
</div>
