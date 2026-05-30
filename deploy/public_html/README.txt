DIRECTADMIN — public_html KURULUMU (404 cozumu)
==============================================

LiteSpeed 404 aliyorsaniz Document Root muhtemelen public_html.
Asagidaki adimlari uygulayin.

1) Bu klasordeki 2 dosyayi sunucuya yukleyin:
   domains/kosarticaret.com/public_html/index.php
   domains/kosarticaret.com/public_html/.htaccess

2) kosar/public/icindeki klasorleri public_html'e KOPYALAYIN (index.php ve .htaccess haric):
   - css/     -> public_html/css/
   - js/      -> public_html/js/
   - images/  -> public_html/images/
   - favicon.svg vb.

   Dosya yoneticisinde: kosar/public/css sec -> kopyala -> public_html yapistir
   (Ayni sekilde js, images)

3) storage link (SSH):
   cd ~/domains/kosarticaret.com/kosar
   php artisan storage:link
   Sonra public_html/storage sembolik link olmali veya
   public_html/storage -> ../kosar/storage/app/public baglantisi

   SSH yoksa DirectAdmin'de public_html icinde "storage" adinda
   symlink olusturun: hedef ../kosar/storage/app/public

4) .env kosar/.env icinde:
   APP_URL=https://kosarticaret.com
   APP_ENV=production
   APP_DEBUG=false
   DB_CONNECTION=mysql

5) Test:
   https://kosarticaret.com
   https://kosarticaret.com/kosar/public/  (bu aciliyorsa 2. yol da calisir)

ALTERNATIF (daha temiz):
DirectAdmin Domain Setup -> Document Root:
   domains/kosarticaret.com/kosar/public
