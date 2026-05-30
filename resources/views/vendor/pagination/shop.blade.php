@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('shop.pagination') }}" class="w-full">
        <div class="flex flex-wrap items-center justify-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="shop-pagination__disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="shop-pagination__link" aria-label="{{ __('pagination.previous') }}">‹</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="shop-pagination__dots" aria-hidden="true">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="shop-pagination__current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="shop-pagination__link" aria-label="{{ __('shop.pagination_page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="shop-pagination__link" aria-label="{{ __('pagination.next') }}">›</a>
            @else
                <span class="shop-pagination__disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">›</span>
            @endif
        </div>
    </nav>
@endif
