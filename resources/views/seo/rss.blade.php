{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0">
    <channel>
        <title>{{ $title }}</title>
        <link>{{ $link }}</link>
        <description>{{ $description }}</description>
        <language>tr-TR</language>
        @foreach($items as $item)
            <item>
                <title>{{ $item['title'] }}</title>
                <link>{{ $item['link'] }}</link>
                <guid>{{ $item['link'] }}</guid>
                @if(!empty($item['pubDate']))<pubDate>{{ $item['pubDate'] }}</pubDate>@endif
                <description>{{ $item['description'] }}</description>
            </item>
        @endforeach
    </channel>
</rss>
