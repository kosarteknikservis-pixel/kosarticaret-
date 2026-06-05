@if($homeRows->isNotEmpty())
    <div class="shop-home-layout shop-reveal mb-8 lg:mb-10">
        @php $prioritizedHomeProductList = false; @endphp
        @foreach($homeRows as $row)
            @php
                $columns = $row->bannersByColumn();
                $visibleColumns = collect($row->columns)
                    ->map(function ($span, $colIndex) use ($columns) {
                        $blocks = ($columns->get($colIndex, collect()))
                            ->filter(fn ($b) => $b->canDisplay() && ($b->isProductList() || $b->imageUrl()))
                            ->values();

                        return [
                            'index' => $colIndex,
                            'span' => (int) $span,
                            'blocks' => $blocks,
                        ];
                    })
                    ->filter(fn ($column) => $column['blocks']->isNotEmpty())
                    ->values();
            @endphp
            @if($visibleColumns->isNotEmpty())
                <div class="shop-home-layout__row" data-home-layout-row>
                    <div class="shop-home-layout__grid" data-home-layout-grid>
                        @foreach($visibleColumns as $column)
                            @php
                                $displaySpan = $visibleColumns->count() === 1 ? 12 : $column['span'];
                            @endphp
                            <div class="shop-home-layout__col" style="--col-span: {{ $displaySpan }}" data-home-layout-col>
                                @foreach($column['blocks'] as $block)
                                    @if($block->isProductList())
                                        @include('shop.partials.home-product-list', ['block' => $block, 'priorityProductCards' => ! $prioritizedHomeProductList])
                                        @php $prioritizedHomeProductList = true; @endphp
                                    @elseif($block->imageUrl())
                                        @include('shop.partials.home-block', ['block' => $block, 'mediaSpan' => $column['span']])
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif
