# -*- coding: utf-8 -*-
"""
Kategori meta_title + meta_description günceller.
Çalıştır: python database/seeders/category_meta_seed.py
"""
import sqlite3, sys, os
sys.stdout.reconfigure(encoding='utf-8')

DB = os.path.join(os.path.dirname(__file__), '..', 'database.sqlite')
conn = sqlite3.connect(DB)
cur  = conn.cursor()

# (id, meta_title, meta_description)
# meta_title  : 50-60 karakter ideal
# meta_description: 140-160 karakter ideal
METAS = [

    # 115 — Su Pompaları
    (
        115,
        "Su Pompası Çeşitleri ve Fiyatları | Koşar Ticaret",
        "Santrifüj, dalgıç, kademeli ve hidrofor dahil 1.000'i aşkın su pompası modeli. "
        "Pedrollo, Sumak, Ebara garantili ürünler. Ücretsiz teknik danışmanlık ve hızlı teslimat.",
    ),

    # 130 — Dalgıç Pompalar
    (
        130,
        "Dalgıç Pompa Modelleri ve Fiyatları | Koşar Ticaret",
        "Temiz su, drenaj, foseptik, kirli su ve derin kuyu dalgıç pompaları. "
        "Pedrollo ve Sumak markalı garantili modeller. Uygun fiyat, hızlı teslimat, teknik destek.",
    ),

    # 132 — Santrifüj Pompalar
    (
        132,
        "Santrifüj Pompa Çeşitleri ve Fiyatları | Koşar Ticaret",
        "Tek fanlı, çift fanlı, paslanmaz çelik ve salyangoz santrifüj pompalar. "
        "Sulama, bina tesisatı ve sanayi proses uygulamaları için en uygun ve verimli çözümler.",
    ),

    # 119 — Hidrofor Sistemleri
    (
        119,
        "Hidrofor Sistemi Modelleri ve Fiyatları | Koşar Ticaret",
        "Ev tipi, çok katlı apartman ve sanayi hidrofor sistemleri. Pedrollo ve Sumak markalı, "
        "otomatik basınç kontrolü. Ücretsiz teknik danışmanlık ve hızlı teslimat.",
    ),

    # 117 — Vantilatörler
    (
        117,
        "Vantilatör Modelleri ve Fiyatları | Koşar Ticaret",
        "Ev tipi ve sanayi tipi vantilatörler. Depo, fabrika ve ofis havalandırma çözümleri. "
        "Yüksek verimli, sessiz ve enerji tasarruflu modeller en uygun fiyatlarla.",
    ),

    # 139 — Kademeli Pompalar
    (
        139,
        "Kademeli Pompa Çeşitleri ve Fiyatları | Koşar Ticaret",
        "Dikey kademeli, monoblok yatay ve norm tipi kademeli pompalar. Yüksek basınç gerektiren "
        "bina tesisatı, sanayi prosesleri ve yangın sistemleri için profesyonel çözümler.",
    ),

    # 149 — Sirkülasyon Pompaları
    (
        149,
        "Sirkülasyon Pompası Çeşitleri ve Fiyatları | Koşar Ticaret",
        "Rekorlu, inline ve flanşlı sirkülasyon pompaları. Kalorifer, yerden ısıtma ve güneş enerjisi "
        "sistemleri için. Pedrollo ve Sumak markalı, enerji tasarruflu modeller.",
    ),

    # 128 — Hidroforlar
    (
        128,
        "Hidrofor Fiyatları: Pedrollo, Sumak Modelleri | Koşar Ticaret",
        "Ev ve apartman tipi hidrofor sistemleri. Pedrollo ve Sumak markalı, otomatik basınç "
        "tanklı modeller. Garantili ürün, uygun fiyat ve ücretsiz teknik danışmanlık.",
    ),

    # 118 — Sanayi Tipi Vantilatör
    (
        118,
        "Sanayi Tipi Vantilatör Fiyatları | Koşar Ticaret",
        "Fabrika, depo ve atölye için yüksek kapasiteli sanayi vantilatörleri. AC ve EC motorlu, "
        "IP44/IP55 korumalı modeller. Uygun fiyat, teknik destek ve hızlı kargo.",
    ),

    # 136 — Özel Amaçlı Pompalar
    (
        136,
        "Özel Amaçlı Pompa Çeşitleri ve Fiyatları | Koşar Ticaret",
        "Yangın söndürme, havuz, jakuzi, foseptik tahliye ve dizel su motorları. "
        "Özel uygulama ihtiyaçlarınız için uzman ekibimizden ücretsiz teknik danışmanlık.",
    ),

    # 124 — Hidrofor Grubu
    (
        124,
        "Hidrofor Grubu Fiyatları | Pedrollo, Sumak | Koşar Ticaret",
        "Çok katlı bina, otel ve sanayi tesisleri için çift ve üçlü pompalı hidrofor grupları. "
        "Frekans invertörlü, otomatik sıra değiştirmeli sistemler. Teknik destek dahil.",
    ),
]

updated = 0
for cat_id, meta_title, meta_desc in METAS:
    cur.execute(
        "UPDATE categories SET meta_title=?, meta_description=? WHERE id=?",
        (meta_title, meta_desc, cat_id)
    )
    if cur.rowcount:
        updated += 1
        print(f"  ✓ id={cat_id:3d}  [{len(meta_title):2d} chars title | {len(meta_desc):3d} chars desc]  {meta_title}")
    else:
        print(f"  ✗ id={cat_id} BULUNAMADI")

conn.commit()
conn.close()
print(f"\nToplam {updated} kategori meta bilgisi güncellendi.")
