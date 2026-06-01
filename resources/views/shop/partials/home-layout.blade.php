@if($homeRows->isNotEmpty())
    <div class="shop-home-layout shop-reveal mb-8 lg:mb-10">
        @foreach($homeRows as $row)
            @php
                $columns = $row->bannersByColumn();
                $hasContent = $columns->flatten()->contains(fn ($b) => $b->canDisplay() && ($b->isProductList() || $b->imageUrl()));
            @endphp
            @if($hasContent)
                <div class="shop-home-layout__row" data-home-layout-row>
                    <div class="shop-home-layout__grid" data-home-layout-grid>
                        @foreach($row->columns as $colIndex => $span)
                            @php $colBlocks = $columns->get($colIndex, collect()); @endphp
                            <div class="shop-home-layout__col" style="--col-span: {{ $span }}" data-home-layout-col>
                                @foreach($colBlocks as $block)
                                    @if($block->canDisplay())
                                        @if($block->isProductList())
                                            @include('shop.partials.home-product-list', ['block' => $block])
                                        @elseif($block->imageUrl())
                                            @include('shop.partials.home-block', ['block' => $block])
                                        @endif
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
