<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'slug' => 'hidrofor-secimi-rehberi',
                'title' => 'Ev Tipi Hidrofor Seçimi: Nelere Dikkat Etmeli?',
                'excerpt' => 'Debisi, basıncı ve kullanım alanına göre doğru hidrofor nasıl seçilir? Kısa rehber.',
                'content' => '<p>Ev tipi hidrofor seçerken önce su tüketim noktalarınızı (banyo, mutfak, bahçe) listeleyin.</p><p>Leo, DAB ve Wilo gibi markalarda debi (Lt/dk) ve maksimum basınç (Bar) değerleri katalogda belirtilir.</p><p>Kosar teknik ekibi ücretsiz ön danışmanlık sunar — iletişim sayfasından ulaşabilirsiniz.</p>',
                'published_at' => '2026-03-15',
                'tags' => ['hidrofor', 'rehber'],
            ],
            [
                'slug' => 'dalgic-pompa-bakimi',
                'title' => 'Dalgıç Pompa Bakımı ve Ömrünü Uzatma',
                'excerpt' => 'Mevsimlik kontroller ve arıza belirtileri.',
                'content' => '<p>Dalgıç pompalar uzun ömürlüdür; düzenli filtre temizliği ve kuru çalıştırmaktan kaçınmak kritiktir.</p><p>Elektrik kesintisi sonrası yeniden çalıştırmadan önce emme hattını kontrol edin.</p>',
                'published_at' => '2026-02-20',
                'tags' => ['pompa', 'bakım'],
            ],
        ];

        foreach ($posts as $row) {
            BlogPost::query()->updateOrCreate(
                ['slug' => $row['slug']],
                array_merge($row, ['published' => true]),
            );
        }
    }
}
