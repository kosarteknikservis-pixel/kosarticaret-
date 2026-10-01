<?php

use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ImageVariant;
use App\Support\PublicPageCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    private const SKU = 'R-3001';

    private const SLUG = 'renato-r-3001-2500w-kumandali-dikey-karbon-isitici-6-kademe-termostatli';

    private const SOURCE_DIR = 'data/product-images/renato-r-3001';

    private const STORAGE_DIR = 'products/renato';

    /**
     * İlk görsel kapak; kalanlar galeri sırasıdır.
     *
     * @var array<string, string>
     */
    private array $images = [
        '01-renato-r-3001-dikey-karbon-isitici-on-gorunum.jpg' => 'Renato R-3001 dikey karbon ısıtıcı, çalışır durumda önden görünüm',
        '02-renato-r-3001-ayakli-isitici-acili-gorunum.jpg' => 'Renato R-3001 ayaklı dikey ısıtıcının açılı görünümü',
        '03-renato-r-3001-karbon-isitici-dijital-ekran.jpg' => 'Renato R-3001 karbon ısıtıcı, dijital ekranda kademe göstergesiyle önden görünüm',
        '04-renato-r-3001-karbon-rezistans-yan-gorunum.jpg' => 'Renato R-3001 ısıtıcının karbon rezistansı, açılı yan görünüm',
        '05-renato-r-3001-uzaktan-kumanda.jpg' => 'Renato R-3001 ısıtıcının uzaktan kumandası, Max ve Min tuşları',
        '06-renato-r-3001-kontrol-paneli-yakin-cekim.jpg' => 'Renato R-3001 gövdesindeki ekranlı kontrol paneli ve açma-kapama tuşu, yakın çekim',
        '07-renato-r-3001-rezistans-koruma-izgarasi.jpg' => 'Renato R-3001 karbon rezistans ve metal koruma ızgarası, yakın çekim',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('product_images')) {
            return;
        }

        $product = Product::query()->where('sku', self::SKU)->orWhere('slug', self::SLUG)->first();
        if ($product === null) {
            return;
        }

        foreach (array_keys($this->images) as $file) {
            if (! is_file(database_path(self::SOURCE_DIR.'/'.$file))) {
                return;
            }
        }

        $newPaths = array_map(fn (string $file) => self::STORAGE_DIR.'/'.$file, array_keys($this->images));
        $oldPaths = array_filter([$product->image, ...$product->images()->pluck('path')->all()]);
        foreach (array_diff($oldPaths, $newPaths) as $path) {
            ImageVariant::delete($path);
            Storage::disk('public')->delete($path);
        }
        $product->images()->delete();

        $alts = array_values($this->images);
        foreach (array_keys($this->images) as $i => $file) {
            $path = $newPaths[$i];
            Storage::disk('public')->put($path, (string) file_get_contents(database_path(self::SOURCE_DIR.'/'.$file)));
            ImageVariant::generate($path, ImageVariant::presetsFor($i === 0 ? 'product' : 'product-gallery'));

            if ($i > 0) {
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'alt' => $alts[$i],
                    'sort_order' => $i,
                ]);
            }
        }

        $product->forceFill(['image' => $newPaths[0], 'image_alt' => $alts[0]])->save();

        PublicPageCache::forgetAll();
    }

    public function down(): void
    {
        // Eski görsel dosyaları silindiği için geri alınamaz.
    }
};
