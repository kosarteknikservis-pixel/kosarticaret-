@if(!empty($breadcrumbs))
    <nav aria-label="Breadcrumb" class="shop-breadcrumb">
        <ol class="shop-breadcrumb__list">
            @foreach($breadcrumbs as $i => $crumb)
                <li class="flex items-center gap-1">
                    @if($i > 0)
                        <x-shop.icon name="chevron-right" class="w-3.5 h-3.5 text-slate-300 shrink-0" aria-hidden="true" />
                    @endif
                    @if(!empty($crumb['url']) && $i < count($breadcrumbs) - 1)
                        <a href="{{ $crumb['url'] }}" class="shop-breadcrumb__link">{{ $crumb['name'] }}</a>
                    @else
                        <span @if($i === count($breadcrumbs) - 1) aria-current="page" class="shop-breadcrumb__current" @endif>{{ $crumb['name'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
