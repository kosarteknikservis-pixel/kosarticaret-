<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\BlogPostController as AdminBlogPostController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailTemplateController as AdminEmailTemplateController;
use App\Http\Controllers\Admin\HomeBannerController as AdminHomeBannerController;
use App\Http\Controllers\Admin\ImageOptimizationController as AdminImageOptimizationController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PreviewController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\ProjectReferenceController as AdminProjectReferenceController;
use App\Http\Controllers\Admin\SearchAnalyticsController as AdminSearchAnalyticsController;
use App\Http\Controllers\Admin\NavigationController as AdminNavigationController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\ParasutIntegrationController as AdminParasutIntegrationController;
use App\Http\Controllers\Admin\BulkProductUpdateController;
use App\Http\Controllers\Admin\CarrierIntegrationController;
use App\Http\Controllers\Admin\TelegramIntegrationController;
use App\Http\Controllers\Admin\OrderShipmentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingSettingsController;
use App\Http\Controllers\Admin\SiteBackupController as AdminSiteBackupController;
use App\Http\Controllers\Admin\ThemeController as AdminThemeController;
use App\Http\Controllers\PublicStorageController;
use App\Http\Controllers\Payment\IyzicoCallbackController;
use App\Http\Controllers\Payment\PaytrCallbackController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\Shop\AccountController;
use App\Http\Controllers\Shop\AnalyticsHeartbeatController;
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
use App\Http\Controllers\Shop\ProductCompareController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\ProductInstallmentController;
use App\Http\Controllers\Shop\ProductReviewController;
use App\Http\Controllers\Shop\PumpSelectorController;
use App\Http\Controllers\Shop\QuoteRequestController;
use App\Http\Controllers\Shop\SearchController;
use App\Http\Controllers\Shop\SearchSuggestController;
use Illuminate\Support\Facades\Route;

Route::get('/storage/{path}', PublicStorageController::class)
    ->where('path', '.*')
    ->name('storage.public');

Route::get('/favicon.ico', fn () => redirect(\App\Support\SiteFavicon::url(), 302))->name('favicon');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/sitemap-{chunk}.xml', [SeoController::class, 'sitemapChunk'])->name('sitemap.chunk');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/urun-feed.xml', [SeoController::class, 'merchantFeed'])->name('merchant.feed');
Route::get('/{file}', [SeoController::class, 'verificationFile'])
    ->where('file', 'google[a-zA-Z0-9_-]+\.html|[a-f0-9]{32}\.txt')
    ->name('google.verification-file');

Route::get('/dil/{locale}', LocaleController::class)->name('locale.switch');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/urunler', [ProductController::class, 'index'])->name('products.index');
Route::get('/urun/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/karsilastir', [ProductCompareController::class, 'index'])->name('compare.index');
Route::post('/karsilastir/{product:slug}', [ProductCompareController::class, 'add'])->name('compare.add');
Route::delete('/karsilastir', [ProductCompareController::class, 'clear'])->name('compare.clear');
Route::delete('/karsilastir/{slug}', [ProductCompareController::class, 'remove'])->name('compare.remove');
Route::get('/karsilastir/durum', [ProductCompareController::class, 'status'])->name('compare.status');
Route::get('/pompa-secici', [PumpSelectorController::class, 'show'])->name('pump-selector.show');
Route::post('/pompa-secici/oner', [PumpSelectorController::class, 'recommend'])->middleware('throttle:20,1')->name('pump-selector.recommend');
Route::get('/urun/{product:slug}/taksit', ProductInstallmentController::class)->name('products.installments');
Route::post('/urun/{product:slug}/yorum', [ProductReviewController::class, 'store'])->middleware('throttle:6,1')->name('products.review');
Route::get('/kategoriler', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/urun-kategori/{legacyCategory}', function (Illuminate\Http\Request $request) {
    $target = \App\Support\LegacyRedirectResolver::resolve($request);

    return redirect()->to(url($target ?? '/kategoriler'), 301);
})->where('legacyCategory', '.*');
Route::get('/kategoriler/{category}', [CategoryController::class, 'show'])
    ->where('category', '.*')
    ->name('categories.show');
Route::get('/markalar', [BrandController::class, 'index'])->name('brands.index');
Route::get('/markalar/{legacyBrand}/page/{page}', function (string $legacyBrand) {
    if ($legacyBrand === 'marmara') {
        return redirect()->route('brands.index', [], 301);
    }

    $slug = (string) (config('legacy_redirects.brand_aliases', [])[$legacyBrand] ?? $legacyBrand);

    return redirect()->route('brands.show', ['brand' => $slug], 301);
})->where('legacyBrand', '[^/]+')->whereNumber('page');
Route::get('/markalar/{legacyBrand}', function (string $legacyBrand) {
    if ($legacyBrand === 'marmara') {
        return redirect()->route('brands.index', [], 301);
    }

    $slug = (string) (config('legacy_redirects.brand_aliases', [])[$legacyBrand] ?? $legacyBrand);

    return redirect()->route('brands.show', ['brand' => $slug], 301);
})->where('legacyBrand', '[^/]+');
Route::get('/marka/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');
Route::get('/ara', SearchController::class)->name('search');
Route::get('/ara/oneri', SearchSuggestController::class)->name('search.suggest');
Route::post('/analitik/aktif', AnalyticsHeartbeatController::class)->middleware('throttle:12,1')->name('analytics.heartbeat');

Route::get('/sepet', [CartController::class, 'index'])->name('cart.index');
Route::post('/sepet/ekle/{product:slug}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/sepet/{product:slug}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/sepet/{product:slug}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/sepet/teklif', [QuoteRequestController::class, 'store'])->middleware('throttle:3,1')->name('cart.quote');

Route::post('/bulten', [NewsletterController::class, 'subscribe'])->middleware('throttle:5,1')->name('newsletter.subscribe');

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
Route::post('/odeme/iletisim-kaydet', [CheckoutController::class, 'saveContact'])->middleware('throttle:30,1')->name('checkout.contact-save');
Route::post('/odeme', [CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/odeme/kupon', [CheckoutController::class, 'applyCoupon'])->name('checkout.coupon');
Route::delete('/odeme/kupon', [CheckoutController::class, 'removeCoupon'])->name('checkout.coupon.remove');
Route::get('/siparis-onay/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/odeme/sonuc/{order}', [CheckoutController::class, 'payment'])->name('checkout.payment');
Route::post('/odeme/sonuc/{order}', [CheckoutController::class, 'completePayment'])->name('checkout.payment.complete');

Route::get('/siparis-takip', [OrderTrackingController::class, 'show'])->name('tracking.show');
Route::post('/siparis-takip', [OrderTrackingController::class, 'lookup'])->middleware('throttle:10,1')->name('tracking.lookup');

Route::post('/odeme/iyzico/callback', IyzicoCallbackController::class)->name('payment.iyzico.callback');
Route::post('/odeme/paytr/callback', PaytrCallbackController::class)->name('payment.paytr.callback');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/sayfa/{page:slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/iletisim', [ContactController::class, 'show'])->name('contact.show');
Route::post('/iletisim', [ContactController::class, 'store'])->middleware('throttle:3,1')->name('contact.store');

$legacyBlogPattern = collect(config('legacy_redirects.blog_posts', []))
    ->keys()
    ->map(fn (string $path) => ltrim($path, '/'))
    ->filter()
    ->implode('|');

if ($legacyBlogPattern !== '') {
    Route::get('/{legacyBlogPost}', function (string $legacyBlogPost) {
        $target = config('legacy_redirects.blog_posts')['/'.$legacyBlogPost] ?? null;
        abort_unless(is_string($target) && $target !== '', 404);

        return redirect(url($target), 301);
    })->where('legacyBlogPost', $legacyBlogPattern);
}

Route::middleware('guest')->group(function () {
    Route::get('/giris', [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/giris', [CustomerAuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/kayit', [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('/kayit', [CustomerAuthController::class, 'register'])->middleware('throttle:3,1');
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

Route::get('/admin', function () {
    return auth()->check() && auth()->user()?->is_admin
        ? redirect()->route('admin.dashboard')
        : redirect()->route('admin.login');
});
Route::redirect('/admin/giris', '/yonetim/giris', 301);

Route::prefix('yonetim')->name('admin.')->group(function () {
    Route::get('giris', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('giris', [AdminAuthController::class, 'login'])->middleware('throttle:admin-login');
    Route::post('cikis', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('musteri-hareketleri', [AdminAnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('arama-analitigi', [AdminSearchAnalyticsController::class, 'index'])->name('search-analytics.index');
        Route::get('musteri-hareketleri/ziyaretci/{visitor}', [AdminAnalyticsController::class, 'showVisitor'])->name('analytics.visitor');
        Route::delete('musteri-hareketleri/yarim-sepet/{cart}', [AdminAnalyticsController::class, 'destroyAbandonedCart'])->name('analytics.abandoned-carts.destroy');
        Route::get('profil', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profil', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::get('urunler/disa-aktar', [AdminProductController::class, 'export'])->name('products.export');
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
        Route::post('bulten/kampanya', [AdminNewsletterController::class, 'storeCampaign'])->name('newsletter.campaigns.store');
        Route::post('bulten/kampanya/{campaign}/test', [AdminNewsletterController::class, 'testCampaign'])->name('newsletter.campaigns.test');
        Route::get('bulten/kampanya/{campaign}/duzenle', [AdminNewsletterController::class, 'editCampaign'])->name('newsletter.campaigns.edit');
        Route::put('bulten/kampanya/{campaign}', [AdminNewsletterController::class, 'updateCampaign'])->name('newsletter.campaigns.update');
        Route::delete('bulten/kampanya/{campaign}', [AdminNewsletterController::class, 'destroyCampaign'])->name('newsletter.campaigns.destroy');
        Route::get('bulten/kampanya/{campaign}/onizle', [AdminNewsletterController::class, 'previewCampaign'])->name('newsletter.campaigns.preview');
        Route::post('bulten/kampanya/{campaign}/gonder', [AdminNewsletterController::class, 'sendCampaign'])->name('newsletter.campaigns.send');
        Route::get('eposta-sablonlari', [AdminEmailTemplateController::class, 'index'])->name('email-templates.index');
        Route::get('eposta-sablonlari/yeni', [AdminEmailTemplateController::class, 'create'])->name('email-templates.create');
        Route::post('eposta-sablonlari', [AdminEmailTemplateController::class, 'store'])->name('email-templates.store');
        Route::get('eposta-sablonlari/{emailTemplate}/duzenle', [AdminEmailTemplateController::class, 'edit'])->name('email-templates.edit');
        Route::get('eposta-sablonlari/{emailTemplate}/onizle', [AdminEmailTemplateController::class, 'preview'])->name('email-templates.preview');
        Route::put('eposta-sablonlari/{emailTemplate}', [AdminEmailTemplateController::class, 'update'])->name('email-templates.update');
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
        Route::resource('referanslar', AdminProjectReferenceController::class)
            ->except(['show'])
            ->parameters(['referanslar' => 'project_reference'])
            ->names('project-references');
        Route::get('tema', [AdminThemeController::class, 'edit'])->name('theme.edit');
        Route::post('tema', [AdminThemeController::class, 'update'])->name('theme.update');
        Route::post('tema/onizle', [AdminThemeController::class, 'preview'])->name('theme.preview');
        Route::post('tema/preset', [AdminThemeController::class, 'applyPreset'])->name('theme.preset');
        Route::post('tema/bolum-sablonu', [AdminThemeController::class, 'applySectionPreset'])->name('theme.section-preset');
        Route::post('tema/ozel-css', [AdminThemeController::class, 'customCss'])->name('theme.custom-css');
        Route::post('tema/yedek', [AdminThemeController::class, 'createBackup'])->name('theme.backup');
        Route::post('tema/yedek/geri-yukle', [AdminThemeController::class, 'restoreBackup'])->name('theme.backup.restore');
        Route::delete('tema/yedek', [AdminThemeController::class, 'deleteBackup'])->name('theme.backup.delete');
        Route::post('tema/sifirla', [AdminThemeController::class, 'reset'])->name('theme.reset');
        Route::get('site-yedekleri', [AdminSiteBackupController::class, 'index'])->name('site-backups.index');
        Route::post('site-yedekleri', [AdminSiteBackupController::class, 'store'])->name('site-backups.store');
        Route::post('site-yedekleri/yukle', [AdminSiteBackupController::class, 'upload'])->name('site-backups.upload');
        Route::get('site-yedekleri/indir', [AdminSiteBackupController::class, 'download'])->name('site-backups.download');
        Route::post('site-yedekleri/geri-yukle', [AdminSiteBackupController::class, 'restore'])->name('site-backups.restore');
        Route::delete('site-yedekleri', [AdminSiteBackupController::class, 'destroy'])->name('site-backups.destroy');
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
            Route::get('parasut/baglan', [AdminParasutIntegrationController::class, 'connect'])->name('parasut.connect');
            Route::get('parasut/callback', [AdminParasutIntegrationController::class, 'callback'])->name('parasut.callback');
            Route::delete('parasut/baglanti', [AdminParasutIntegrationController::class, 'disconnect'])->name('parasut.disconnect');
            Route::prefix('odeme')->name('payment.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'indexPayment'])->name('index');
                Route::get('paytr', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'editPaytr'])->name('paytr');
                Route::put('paytr', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'updatePaytr'])->name('paytr.update');
                Route::get('iyzico', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'editIyzico'])->name('iyzico');
                Route::put('iyzico', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'updateIyzico'])->name('iyzico.update');
            });
            Route::prefix('kargo')->name('shipping.')->group(function () {
                Route::get('dhl', [CarrierIntegrationController::class, 'editDhl'])->name('dhl');
                Route::put('dhl', [CarrierIntegrationController::class, 'updateDhl'])->name('dhl.update');
                Route::post('dhl/test', [CarrierIntegrationController::class, 'testDhl'])->name('dhl.test');
            });
            Route::prefix('bildirimler')->name('notifications.')->group(function () {
                Route::get('telegram', [TelegramIntegrationController::class, 'edit'])->name('telegram');
                Route::put('telegram', [TelegramIntegrationController::class, 'update'])->name('telegram.update');
                Route::post('telegram/test', [TelegramIntegrationController::class, 'test'])->name('telegram.test');
            });
            Route::prefix('pazaryerleri')->name('marketplace.')->group(function () {
                Route::get('/', \App\Http\Controllers\Admin\Marketplace\MarketplaceDashboardController::class)->name('index');
                Route::get('hazirlik', \App\Http\Controllers\Admin\Marketplace\MarketplaceReadinessController::class)->name('readiness');
                Route::get('barkod-import', [\App\Http\Controllers\Admin\Marketplace\MarketplaceLogisticsImportController::class, 'create'])->name('logistics-import');
                Route::post('barkod-import', [\App\Http\Controllers\Admin\Marketplace\MarketplaceLogisticsImportController::class, 'store'])->name('logistics-import.store');
                Route::get('loglar', \App\Http\Controllers\Admin\Marketplace\MarketplaceSyncLogController::class)->name('logs');
                Route::get('kanallar', [\App\Http\Controllers\Admin\Marketplace\MarketplaceChannelController::class, 'index'])->name('channels.index');
                Route::get('kanallar/{channel}', [\App\Http\Controllers\Admin\Marketplace\MarketplaceChannelController::class, 'edit'])->name('channels.edit');
                Route::put('kanallar/{channel}', [\App\Http\Controllers\Admin\Marketplace\MarketplaceChannelController::class, 'update'])->name('channels.update');
                Route::post('kanallar/{channel}/test', [\App\Http\Controllers\Admin\Marketplace\MarketplaceChannelController::class, 'testConnection'])->name('channels.test');

                Route::prefix('eslestirmeler')->name('mappings.')->group(function () {
                    Route::get('/', \App\Http\Controllers\Admin\Marketplace\MarketplaceMappingHubController::class)->name('index');
                    Route::get('kategoriler', [\App\Http\Controllers\Admin\Marketplace\MarketplaceCategoryMappingController::class, 'index'])->name('categories');
                    Route::post('kategoriler', [\App\Http\Controllers\Admin\Marketplace\MarketplaceCategoryMappingController::class, 'store'])->name('categories.store');
                    Route::delete('kategoriler/{mapping}', [\App\Http\Controllers\Admin\Marketplace\MarketplaceCategoryMappingController::class, 'destroy'])->name('categories.destroy');
                    Route::post('kategoriler/oner', [\App\Http\Controllers\Admin\Marketplace\MarketplaceCategoryMappingController::class, 'suggest'])->name('categories.suggest');
                    Route::post('kategoriler/harici-import', [\App\Http\Controllers\Admin\Marketplace\MarketplaceCategoryMappingController::class, 'importExternal'])->name('categories.import-external');
                    Route::get('markalar', [\App\Http\Controllers\Admin\Marketplace\MarketplaceBrandMappingController::class, 'index'])->name('brands');
                    Route::post('markalar', [\App\Http\Controllers\Admin\Marketplace\MarketplaceBrandMappingController::class, 'store'])->name('brands.store');
                    Route::delete('markalar/{mapping}', [\App\Http\Controllers\Admin\Marketplace\MarketplaceBrandMappingController::class, 'destroy'])->name('brands.destroy');
                    Route::post('markalar/oner', [\App\Http\Controllers\Admin\Marketplace\MarketplaceBrandMappingController::class, 'suggest'])->name('brands.suggest');
                    Route::get('ozellikler', [\App\Http\Controllers\Admin\Marketplace\MarketplaceAttributeMappingController::class, 'index'])->name('attributes');
                    Route::post('ozellikler', [\App\Http\Controllers\Admin\Marketplace\MarketplaceAttributeMappingController::class, 'store'])->name('attributes.store');
                    Route::delete('ozellikler/{mapping}', [\App\Http\Controllers\Admin\Marketplace\MarketplaceAttributeMappingController::class, 'destroy'])->name('attributes.destroy');
                    Route::get('yedek/indir', [\App\Http\Controllers\Admin\Marketplace\MarketplaceMappingBackupController::class, 'export'])->name('export');
                    Route::post('yedek/yukle', [\App\Http\Controllers\Admin\Marketplace\MarketplaceMappingBackupController::class, 'import'])->name('import');
                });

                Route::get('listelemeler', [\App\Http\Controllers\Admin\Marketplace\MarketplaceListingController::class, 'index'])->name('listings.index');
                Route::post('listelemeler/gonder', [\App\Http\Controllers\Admin\Marketplace\MarketplaceListingController::class, 'publish'])->name('listings.publish');
                Route::post('listelemeler/toplu-gonder', [\App\Http\Controllers\Admin\Marketplace\MarketplaceListingController::class, 'bulkPublish'])->name('listings.bulk-publish');
                Route::post('listelemeler/{listing}/yeniden-dene', [\App\Http\Controllers\Admin\Marketplace\MarketplaceListingController::class, 'retry'])->name('listings.retry');

                Route::get('siparisler', [\App\Http\Controllers\Admin\Marketplace\MarketplaceOrderSyncController::class, 'index'])->name('orders.index');
                Route::post('siparisler/import', [\App\Http\Controllers\Admin\Marketplace\MarketplaceOrderSyncController::class, 'import'])->name('orders.import');
            });
        });
        Route::get('ayarlar', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('ayarlar', [SettingController::class, 'update'])->name('settings.update');
        Route::post('ayarlar/smtp-test', [SettingController::class, 'testSmtp'])->name('settings.smtp-test');
        Route::get('performans/gorseller', [AdminImageOptimizationController::class, 'index'])->name('performance.images');
        Route::post('performans/gorseller/optimize', [AdminImageOptimizationController::class, 'optimize'])->name('performance.images.optimize');
        Route::get('performans/site-hizi', [\App\Http\Controllers\Admin\PageSpeedController::class, 'index'])->name('performance.pagespeed');
        Route::post('performans/site-hizi/olc', [\App\Http\Controllers\Admin\PageSpeedController::class, 'run'])->middleware('throttle:6,60')->name('performance.pagespeed.run');
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
        Route::delete('siparisler/toplu-sil', [AdminOrderController::class, 'bulkDestroy'])->name('orders.bulk-destroy');
        Route::get('siparisler/{order}/kargo-etiketi', [AdminOrderController::class, 'shippingLabel'])->name('orders.shipping-label');
        Route::post('siparisler/{order}/kargo/plan', [OrderShipmentController::class, 'generatePlan'])->name('orders.shipments.plan');
        Route::post('siparisler/{order}/kargo/plan-kaydet', [OrderShipmentController::class, 'savePlan'])->name('orders.shipments.save-plan');
        Route::post('siparisler/{order}/kargo/tumunu-gonder', [OrderShipmentController::class, 'submitAll'])->name('orders.shipments.submit-all');
        Route::post('siparisler/{order}/kargo/{shipment}/gonder', [OrderShipmentController::class, 'submit'])->name('orders.shipments.submit');
        Route::post('siparisler/{order}/kargo/{shipment}/sync', [OrderShipmentController::class, 'sync'])->name('orders.shipments.sync');
        Route::get('siparisler/{order}/kargo/{shipment}/etiket', [OrderShipmentController::class, 'label'])->name('orders.shipments.label');
        Route::get('siparisler/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::patch('siparisler/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
        Route::post('siparisler/{order}/odeme-hatirlat', [AdminOrderController::class, 'sendPaymentReminder'])->name('orders.payment-reminder');
        Route::delete('siparisler/{order}', [AdminOrderController::class, 'destroy'])->name('orders.destroy');
        Route::post('siparisler/{order}/parasut', [AdminParasutIntegrationController::class, 'syncOrder'])->name('orders.parasut.sync');
    });
});
