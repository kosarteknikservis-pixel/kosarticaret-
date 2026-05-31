# -*- coding: utf-8 -*-
import sqlite3, sys, os
sys.stdout.reconfigure(encoding='utf-8')

DB = os.path.join(os.path.dirname(__file__), '..', 'database.sqlite')
conn = sqlite3.connect(DB)
cur = conn.cursor()

LINKS = {
    115: '<p><strong>İlgili kategoriler:</strong> <a href="/kategoriler/su-pompalari/dalgic-pompalar">Dalgıç pompalar</a>, <a href="/kategoriler/su-pompalari/santrifuj-pompalar">santrifüj pompalar</a>, <a href="/kategoriler/su-pompalari/kademeli-pompalar">kademeli pompalar</a> ve <a href="/kategoriler/hidrofor-sistemleri">hidrofor sistemleri</a> için alt kategorileri inceleyebilirsiniz. Marka karşılaştırması için <a href="/marka/pedrollo">Pedrollo</a> ve <a href="/marka/sumak">Sumak</a> sayfalarına bakabilirsiniz.</p>',
    117: '<p><strong>İlgili ürün grupları:</strong> Ev ve ofis kullanımı için <a href="/kategoriler/vantilatorler/ev-tipi-vantilator">ev tipi vantilatörler</a>, fabrika ve depo uygulamaları için <a href="/kategoriler/vantilatorler/sanayi-tipi-vantilator">sanayi tipi vantilatörler</a> kategorilerini karşılaştırabilirsiniz.</p>',
    118: '<p><strong>Doğru seçim için:</strong> Daha küçük alanlarda <a href="/kategoriler/vantilatorler/ev-tipi-vantilator">ev tipi vantilatör</a>, fabrika ve depo gibi büyük alanlarda sanayi tipi model tercih edilmelidir. Tüm seçenekler için <a href="/kategoriler/vantilatorler">vantilatörler</a> ana kategorisine dönebilirsiniz.</p>',
    119: '<p><strong>Alt kategoriler:</strong> Konut kullanımı için <a href="/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar">ev tipi hidroforlar</a>, çok katlı binalar için <a href="/kategoriler/hidrofor-sistemleri/hidrofor-grubu">hidrofor grupları</a>, marka bazlı seçim için <a href="/kategoriler/hidrofor-sistemleri/pedrollo-hidrofor">Pedrollo hidrofor</a> ve <a href="/kategoriler/hidrofor-sistemleri/sumak-hidrofor">Sumak hidrofor</a> sayfalarını inceleyebilirsiniz.</p>',
    124: '<p><strong>Karşılaştırmalı seçim:</strong> Küçük konutlar için <a href="/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar">ev tipi hidrofor</a>, profesyonel sistemler için hidrofor grubu tercih edilir. Marka seçenekleri için <a href="/marka/pedrollo">Pedrollo</a> ve <a href="/marka/sumak">Sumak</a> sayfalarına bakabilirsiniz.</p>',
    128: '<p><strong>Benzer kategoriler:</strong> Tek daire ve müstakil evler için <a href="/kategoriler/hidrofor-sistemleri/ev-tipi-hidroforlar">ev tipi hidroforlar</a>, apartman ve oteller için <a href="/kategoriler/hidrofor-sistemleri/hidrofor-grubu">hidrofor grubu</a>, sıcak su tesisatı için <a href="/kategoriler/hidrofor-sistemleri/sicak-su-hidroforu">sıcak su hidroforu</a> kategorilerini inceleyebilirsiniz.</p>',
    130: '<p><strong>Dalgıç pompa seçimi:</strong> Temiz su için <a href="/kategoriler/su-pompalari/dalgic-pompalar/temiz-su-dalgic-pompasi">temiz su dalgıç pompası</a>, sondaj kuyuları için <a href="/kategoriler/su-pompalari/dalgic-pompalar/derin-kuyu-dalgic-pompa">derin kuyu dalgıç pompası</a>, bodrum ve yağmur suyu için <a href="/kategoriler/su-pompalari/dalgic-pompalar/drenaj-dalgic-pompa">drenaj dalgıç pompası</a>, fosseptik için <a href="/kategoriler/su-pompalari/dalgic-pompalar/foseptik-dalgic-pompa">foseptik dalgıç pompa</a> kategorilerine bakabilirsiniz.</p>',
    132: '<p><strong>Alt kategoriler:</strong> Standart uygulamalar için <a href="/kategoriler/su-pompalari/santrifuj-pompalar/tek-fanli-santrifuj-pompa">tek fanlı santrifüj pompa</a>, yüksek basınç için <a href="/kategoriler/su-pompalari/santrifuj-pompalar/cift-fanli-santrifuj-pompa">çift fanlı santrifüj pompa</a>, kimyasal sıvılar için <a href="/kategoriler/su-pompalari/santrifuj-pompalar/paslanmaz-pompalar-kimyasal">paslanmaz kimyasal pompalar</a> ve yüksek debi için <a href="/kategoriler/su-pompalari/santrifuj-pompalar/salyangoz-pompalar-bol-su-veren">salyangoz pompalar</a> incelenebilir.</p>',
    136: '<p><strong>Özel uygulamalar:</strong> Yangın sistemleri için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/yangin-pompalari">yangın pompaları</a>, havuz sistemleri için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/on-filtreli-havuz-pompasi">ön filtreli havuz pompaları</a>, elektrik olmayan alanlar için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/dizel-su-motorlari">dizel su motorları</a> ve bodrum atık suyu için <a href="/kategoriler/su-pompalari/ozel-amacli-pompalar/foseptik-tahliye-cihazi">foseptik tahliye cihazı</a> sayfalarını inceleyebilirsiniz.</p>',
    139: '<p><strong>Kademeli pompa türleri:</strong> Yer tasarrufu için <a href="/kategoriler/su-pompalari/kademeli-pompalar/dikey-kademeli-pompalar">dikey kademeli pompalar</a>, kolay servis için <a href="/kategoriler/su-pompalari/kademeli-pompalar/yatay-kademeli-pompalar">yatay kademeli pompalar</a>, kompakt sistemler için <a href="/kategoriler/su-pompalari/kademeli-pompalar/monoblok-yatay-kademeli">monoblok yatay kademeli</a> ve ağır sanayi için <a href="/kategoriler/su-pompalari/kademeli-pompalar/norm-tipi-yatay-kademeli">norm tipi yatay kademeli</a> seçeneklerini inceleyebilirsiniz.</p>',
    149: '<p><strong>Sirkülasyon seçenekleri:</strong> Konut sistemleri için <a href="/kategoriler/su-pompalari/sirkulasyon-pompalari/rekorlu-disli-sirkulasyon-pompalari">rekorlu dişli sirkülasyon pompaları</a>, büyük tesisler için <a href="/kategoriler/su-pompalari/sirkulasyon-pompalari/inline-sirkulasyon-pompalari">inline</a> ve <a href="/kategoriler/su-pompalari/sirkulasyon-pompalari/flansli-sirkulasyon-pompalari">flanşlı sirkülasyon pompaları</a>, sıcak su hatları için <a href="/kategoriler/su-pompalari/sirkulasyon-pompalari/sicak-su-pompalari">sıcak su pompaları</a> incelenebilir.</p>',
}

updated = 0
for cat_id, paragraph in LINKS.items():
    cur.execute("SELECT description FROM categories WHERE id=?", (cat_id,))
    row = cur.fetchone()
    if not row:
        print(f"  ✗ id={cat_id} BULUNAMADI")
        continue
    desc = row[0] or ''
    if '<a ' not in desc:
        desc = desc.rstrip() + "\n\n" + paragraph
        cur.execute("UPDATE categories SET description=? WHERE id=?", (desc, cat_id))
        updated += 1
        print(f"  ✓ id={cat_id} link eklendi")
    else:
        print(f"  - id={cat_id} zaten linkli")

conn.commit()
conn.close()
print(f"\nInternal links added: {updated}")
