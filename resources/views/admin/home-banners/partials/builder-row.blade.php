<div class="hp-row" data-row-id="{{ $row->id }}" data-columns="{{ json_encode($row->columns) }}">
    <div class="hp-row__head">
        <span class="hp-row__handle" title="Satırı sürükle">⋮⋮</span>
        <span class="hp-row__title">{{ $row->name ?: 'Satır' }}</span>
        <span class="hp-row__layout text-xs text-slate-400">{{ implode(' | ', $row->columns) }}/12</span>
        <button type="button" class="hp-row__delete text-xs text-red-500 hover:text-red-700 ml-auto" data-delete-row="{{ $row->id }}" title="Satırı sil">Sil</button>
    </div>
    <div class="hp-row__grid" style="--hp-cols: {{ count($row->columns) }}">
        @foreach($row->columns as $colIndex => $span)
            @php $colBlocks = $row->bannersByColumn()->get($colIndex, collect()); @endphp
            <div class="hp-col" data-col-index="{{ $colIndex }}" data-span="{{ $span }}" style="--col-span: {{ $span }}">
                @php $specKey = $span >= 8 ? 'home_banner_slider' : 'home_banner_tile'; @endphp
                <div class="hp-col__label">
                    <span>{{ $span }}/12</span>
                    <x-admin.image-spec :key="$specKey" compact class="!mt-1 !mb-0" />
                </div>
                <div class="hp-col__drop" data-row-id="{{ $row->id }}" data-col-index="{{ $colIndex }}">
                    @foreach($colBlocks as $banner)
                        @include('admin.home-banners.partials.block-card', ['banner' => $banner])
                    @endforeach
                </div>
                <button type="button"
                        class="hp-col__add"
                        data-create-url="{{ route('admin.home-banners.panel.create', ['type' => 'banner', 'row_id' => $row->id, 'col_index' => $colIndex]) }}">
                    + Blok
                </button>
            </div>
        @endforeach
    </div>
</div>
