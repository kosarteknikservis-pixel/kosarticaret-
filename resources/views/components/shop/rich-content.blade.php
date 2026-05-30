@props(['content' => null])

@if(filled($content))
    <div {{ $attributes->class(['shop-rich-content']) }}>
        {!! \App\Support\RichContent::render($content) !!}
    </div>
@endif
