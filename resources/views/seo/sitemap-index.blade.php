{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
{!! '<'.'?xml-stylesheet type="text/xsl" href="'.e(\App\Support\SitemapGenerator::stylesheetHref()).'"?>' !!}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($entries as $entry)
    <sitemap>
        <loc>{{ $entry['loc'] }}</loc>
        @if(!empty($entry['lastmod']))<lastmod>{{ $entry['lastmod'] }}</lastmod>@endif
    </sitemap>
@endforeach
</sitemapindex>
