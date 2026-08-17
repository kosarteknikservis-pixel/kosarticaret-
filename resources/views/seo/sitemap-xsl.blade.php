{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sm="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
    exclude-result-prefixes="sm image">
    <xsl:output method="html" encoding="UTF-8" indent="yes"/>
    <xsl:variable name="siteName" select="'{{ htmlspecialchars(\App\Support\SiteName::get(), ENT_QUOTES, 'UTF-8') }}'"/>
    <xsl:variable name="siteUrl" select="'{{ htmlspecialchars(\App\Support\Seo::siteUrl(), ENT_QUOTES, 'UTF-8') }}'"/>

    <xsl:template match="/">
        <html lang="tr">
            <head>
                <meta charset="UTF-8"/>
                <meta name="viewport" content="width=device-width, initial-scale=1"/>
                <title>
                    <xsl:value-of select="$siteName"/> — Site haritası
                </title>
                <style>
                    :root { color-scheme: light; }
                    body { margin: 0; font-family: Segoe UI, system-ui, sans-serif; background: #f4f6f8; color: #1e293b; }
                    header { background: #0f2744; color: #fff; padding: 28px 24px; }
                    header p { margin: 8px 0 0; opacity: .78; font-size: 14px; }
                    main { max-width: 1100px; margin: 0 auto; padding: 24px; }
                    .meta { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
                    .chip { background: #fff; border: 1px solid #dbe3ea; border-radius: 999px; padding: 6px 12px; font-size: 13px; }
                    table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dbe3ea; }
                    th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #edf1f5; font-size: 14px; vertical-align: top; }
                    th { background: #eef3f7; font-weight: 600; }
                    a { color: #1d4e89; }
                    .count { color: #64748b; font-size: 12px; }
                    footer { padding: 20px 24px 40px; color: #64748b; font-size: 12px; }
                </style>
            </head>
            <body>
                <header>
                    <h1><xsl:value-of select="$siteName"/> site haritası</h1>
                    <p>Google ve Bing bu XML’i tarar. Bu görünüm tarayıcı için XSLT belgesidir; URL listesi değişmez.</p>
                </header>
                <main>
                    <xsl:apply-templates/>
                </main>
                <footer>
                    Kaynak: <xsl:value-of select="$siteUrl"/>/sitemap.xml · XML Sitemap 0.9
                </footer>
            </body>
        </html>
    </xsl:template>

    <xsl:template match="sm:sitemapindex">
        <div class="meta">
            <span class="chip">Sitemap index</span>
            <span class="chip"><xsl:value-of select="count(sm:sitemap)"/> alt harita</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Alt sitemap</th>
                    <th>Son güncelleme</th>
                </tr>
            </thead>
            <tbody>
                <xsl:for-each select="sm:sitemap">
                    <tr>
                        <td><a href="{sm:loc}"><xsl:value-of select="sm:loc"/></a></td>
                        <td><xsl:value-of select="sm:lastmod"/></td>
                    </tr>
                </xsl:for-each>
            </tbody>
        </table>
    </xsl:template>

    <xsl:template match="sm:urlset">
        <div class="meta">
            <span class="chip">URL seti</span>
            <span class="chip"><xsl:value-of select="count(sm:url)"/> adres</span>
            <span class="chip"><xsl:value-of select="count(sm:url/image:image)"/> görsel</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>URL</th>
                    <th>Son güncelleme</th>
                    <th>Görsel</th>
                </tr>
            </thead>
            <tbody>
                <xsl:for-each select="sm:url">
                    <tr>
                        <td><a href="{sm:loc}"><xsl:value-of select="sm:loc"/></a></td>
                        <td><xsl:value-of select="sm:lastmod"/></td>
                        <td class="count"><xsl:value-of select="count(image:image)"/></td>
                    </tr>
                </xsl:for-each>
            </tbody>
        </table>
    </xsl:template>
</xsl:stylesheet>
