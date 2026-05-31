<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BlogPostController as AdminBlogPostController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomeBannerController as AdminHomeBannerController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PreviewController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\NavigationController as AdminNavigationController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\BulkProductUpdateController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingSettingsController;
use App\Http\Controllers\PublicStorageController;
use App\Http\Controllers\Payment\IyzicoCallbackController;
use App\Http\Controllers\Payment\PaytrCallbackController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\Shop\AccountController;
use App\Http\Controllers\Shop\BlogController;
use App\Http\Controllers\Shop\BrandController;
use App\Http\Controllers\Shop\CartApiController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CategoryController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\ContactController;
use App\Http\Controllers\Shop\CustomerAuthController;
use App\Http\Controllers\Shop\FavoriteController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\LocaleController;
use App\Http\Controllers\Shop\OrderTrackingController;
use App\Http\Controllers\Shop\PageController;
use App\Http\Controllers\Shop\NewsletterController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\ProductInstallmentController;
use App\Http\Controllers\Shop\ProductReviewController;
use App\Http\Controllers\Shop\SearchController;
use App\Http\Controllers\Shop\SearchSuggestController;
use Illuminate\Support\Facades\Route;

Route::get('/storage/{path}', PublicStorageController::class)
    ->where('path', '.*')
    ->name('storage.public');

Route::get('/favicon.ico', fn () => redirect(\App\Support\SiteFavicon::url(), 302))->name('favicon');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::get('/dil/{locale}', LocaleController::class)->name('locale.switch');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/urunler', [ProductController::class, 'index'])->name('products.index');
Route::get('/urun/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/urun/{product:slug}/taksit', ProductInstallmentController::class)->name('products.installments');
Route::post('/urun/{product:slug}/yorum', [ProductReviewController::class, 'store'])->name('products.review');
Route::get('/kategoriler', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/kategoriler/{category}', [CategoryController::class, 'show'])
    ->where('category', '.*')
    ->name('categories.show');
Route::get('/markalar', [BrandController::class, 'index'])->name('brands.index');
Route::get('/marka/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');
Route::get('/ara', SearchController::class)->name('search');
Route::get('/ara/oneri', SearchSuggestController::class)->name('search.suggest');

Route::get('/sepet', [CartController::class, 'index'])->name('cart.index');
Route::post('/sepet/ekle/{product:slug}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/sepet/{product:slug}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/sepet/{product:slug}', [CartController::class, 'remove'])->name('cart.remove');

Route::post('/bulten', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');

Route::prefix('sepet/ajax')->name('cart.ajax.')->group(function () {
    Route::get('detay', [CartApiController::class, 'detail'])->name('detail');
    Route::get('ozet', [CartApiController::class, 'summary'])->name('summary');
    Route::post('ekle/{product:slug}', [CartApiController::class, 'add'])->name('add');
    Route::patch('{product:slug}', [CartApiController::class, 'update'])->name('update');
    Route::delete('{product:slug}', [CartApiController::class, 'remove'])->name('remove');
});

Route::get('/favoriler', [FavoriteController::class, 'index'])->name('favorites.index');
Route::post('/favoriler/{product:slug}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

Route::get('/odeme', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/odeme', [CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/odeme/kupon', [CheckoutController::class, 'applyCoupon'])->name('checkout.coupon');
Route::delete('/odeme/kupon', [CheckoutController::class, 'removeCoupon'])->name('checkout.coupon.remove');
Route::get('/siparis-onay/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/odeme/sonuc/{order}', [CheckoutController::class, 'payment'])->name('checkout.payment');
Route::post('/odeme/sonuc/{order}', [CheckoutController::class, 'completePayment'])->name('checkout.payment.complete');

Route::get('/siparis-takip', [OrderTrackingController::class, 'show'])->name('tracking.show');
Route::post('/siparis-takip', [OrderTrackingController::class, 'lookup'])->name('tracking.lookup');

Route::post('/odeme/iyzico/callback', IyzicoCallbackController::class)->name('payment.iyzico.callback');
Route::post('/odeme/paytr/callback', PaytrCallbackController::class)->name('payment.paytr.callback');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/sayfa/{page:slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/iletisim', [ContactController::class, 'show'])->name('contact.show');
Route::post('/iletisim', [ContactController::class, 'store'])->name('contact.store');

Route::middleware('guest')->group(function () {
    Route::get('/giris', [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/giris', [CustomerAuthController::class, 'login']);
    Route::get('/kayit', [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('/kayit', [CustomerAuthController::class, 'register']);
});

Route::post('/cikis', [CustomerAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'shop.customer'])->prefix('hesabim')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::get('/siparis/{order}', [AccountController::class, 'order'])->name('order');
});

$legalSlugs = [
    'hakkimizda', 'gizlilik-politikasi', 'kvkk',
    'kargo-ve-iade', 'mesafeli-satis-sozlesmesi', 'on-bilgilendirme', 'sss',
];
foreach ($legalSlugs as $slug) {
    Route::redirect('/'.$slug, '/sayfa/'.$slug, 301);
}

Route::prefix('yonetim')->name('admin.')->group(function () {
    Route::get('giris', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('giris', [AdminAuthController::class, 'login']);
    Route::post('cikis', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('profil', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profil', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::resource('urunler', AdminProductController::class)
            ->except(['show'])
            ->parameters(['urunler' => 'product'])
            ->names('products');
        Route::delete('urunler/{product}/galeri/{image}', [AdminProductController::class, 'destroyImage'])->name('products.gallery.destroy');
        Route::get('urunler/toplu-guncelle', [BulkProductUpdateController::class, 'index'])->name('products.bulk-update');
        Route::post('urunler/toplu-guncelle/onizle', [BulkProductUpdateController::class, 'preview'])->name('products.bulk-update.preview');
        Route::post('urunler/toplu-guncelle', [BulkProductUpdateController::class, 'apply'])->name('products.bulk-update.apply');
        Route::post('urunler/toplu-guncelle/csv', [BulkProductUpdateController::class, 'applyCsv'])->name('products.bulk-update.csv');
        Route::resource('menu', AdminNavigationController::class)->except(['show'])->parameters(['menu' => 'navigation']);
        Route::get('yorumlar', [AdminReviewController::class, 'index'])->name('reviews.index');
        Route::patch('yorumlar/{review}/onayla', [AdminReviewController::class, 'approve'])->name('reviews.approve');
        Route::delete('yorumlar/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::get('bulten', [AdminNewsletterController::class, 'index'])->name('newsletter.index');
        Route::resource('kategoriler', AdminCategoryController::class)
            ->except(['show'])
            ->parameters(['kategoriler' => 'category'])
            ->names('categories');
        Route::resource('markalar', AdminBrandController::class)
            ->except(['show'])
            ->parameters(['markalar' => 'brand'])
            ->names('brands');
        Route::resource('kuponlar', AdminCouponController::class)
            ->except(['show'])
            ->parameters(['kuponlar' => 'coupon'])
            ->names('coupons');
        Route::resource('sayfalar', AdminPageController::class)
            ->except(['show'])
            ->parameters(['sayfalar' => 'page'])
            ->names('pages');
        Route::resource('blog', AdminBlogPostController::class)->except(['show'])->parameters(['blog' => 'blog']);
        Route::get('bannerlar/duzenleyici', [AdminHomeBannerController::class, 'builder'])->name('home-banners.builder');
        Route::post('bannerlar/duzen/kaydet', [AdminHomeBannerController::class, 'saveLayout'])->name('home-banners.layout.save');
        Route::post('bannerlar/satir', [AdminHomeBannerController::class, 'storeRow'])->name('home-banners.rows.store');
        Route::delete('bannerlar/satir/{homeRow}', [AdminHomeBannerController::class, 'destroyRow'])->name('home-banners.rows.destroy');
        Route::get('bannerlar/panel/ekle', [AdminHomeBannerController::class, 'panelCreate'])->name('home-banners.panel.create');
        Route::get('bannerlar/{home_banner}/panel', [AdminHomeBannerController::class, 'panel'])->name('home-banners.panel');
        Route::patch('bannerlar/{home_banner}/hizli', [AdminHomeBannerController::class, 'quickPatch'])->name('home-banners.quick');
        Route::put('bannerlar/olcu', [AdminHomeBannerController::class, 'updateDimensions'])->name('home-banners.dimensions');
        Route::post('bannerlar/siralama', [AdminHomeBannerController::class, 'reorder'])->name('home-banners.reorder');
        Route::resource('bannerlar', AdminHomeBannerController::class)
            ->except(['show'])
            ->parameters(['bannerlar' => 'home_banner'])
            ->names('home-banners');
        Route::post('ai/slug', [\App\Http\Controllers\Admin\AiController::class, 'slug'])->name('ai.slug');
        Route::post('ai/meta', [\App\Http\Controllers\Admin\AiController::class, 'meta'])->name('ai.meta');
        Route::post('ai/generate', [\App\Http\Controllers\Admin\AiController::class, 'generate'])->name('ai.generate');
        Route::redirect('odeme/paytr', 'entegrasyonlar/odeme/paytr');
        Route::redirect('odeme/iyzico', 'entegrasyonlar/odeme/iyzico');
        Route::prefix('entegrasyonlar')->name('integrations.')->group(function () {
            Route::get('/', fn () => redirect()->route('admin.integrations.payment.index'))->name('index');
            Route::prefix('odeme')->name('payment.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'indexPayment'])->name('index');
                Route::get('paytr', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'editPaytr'])->name('paytr');
                Route::put('paytr', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'updatePaytr'])->name('paytr.update');
                Route::get('iyzico', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'editIyzico'])->name('iyzico');
                Route::put('iyzico', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'updateIyzico'])->name('iyzico.update');
            });
        });
        Route::get('ayarlar', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('ayarlar', [SettingController::class, 'update'])->name('settings.update');
        Route::get('kargo-odeme', [ShippingSettingsController::class, 'edit'])->name('shipping-settings.edit');
        Route::put('kargo-odeme', [ShippingSettingsController::class, 'update'])->name('shipping-settings.update');
        Route::get('musteriler', [AdminCustomerController::class, 'index'])->name('customers.index');
        Route::get('musteriler/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');
        Route::get('iletisim-mesajlari', [AdminContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::get('iletisim-mesajlari/{message}', [AdminContactMessageController::class, 'show'])->name('contact-messages.show');
        Route::delete('iletisim-mesajlari/{message}', [AdminContactMessageController::class, 'destroy'])->name('contact-messages.destroy');
        Route::post('onizleme', [PreviewController::class, 'start'])->name('preview.start');
        Route::post('onizleme/kapat', [PreviewController::class, 'stop'])->name('preview.stop');
        Route::resource('kampanyalar', AdminPromotionController::class)
            ->except(['show'])
            ->parameters(['kampanyalar' => 'promotion'])
            ->names('promotions');
        Route::get('siparisler', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('siparisler/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::patch('siparisler/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
    });
});
