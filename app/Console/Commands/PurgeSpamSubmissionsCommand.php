<?php

namespace App\Console\Commands;

use App\Models\ContactMessage;
use App\Models\ProductReview;
use App\Support\ContactFormSpamGuard;
use Illuminate\Console\Command;

class PurgeSpamSubmissionsCommand extends Command
{
    protected $signature = 'spam:purge-pending
                            {--dry-run : Silmeden önizle}
                            {--force : Onay sormadan sil}';

    protected $description = 'Onay bekleyen spam yorum ve iletişim mesajlarını temizler';

    public function handle(): int
    {
        $reviewIds = [];
        ProductReview::query()
            ->where('approved', false)
            ->orderBy('id')
            ->chunkById(200, function ($reviews) use (&$reviewIds) {
                foreach ($reviews as $review) {
                    $spam = ContactFormSpamGuard::looksLikeObviousSpam([
                        'author_name' => $review->author_name,
                        'email' => $review->email,
                        'title' => $review->title,
                        'body' => $review->body,
                    ]);

                    if ($spam) {
                        $reviewIds[] = $review->id;
                    }
                }
            });

        $messageIds = [];
        ContactMessage::query()
            ->orderBy('id')
            ->chunkById(200, function ($messages) use (&$messageIds) {
                foreach ($messages as $message) {
                    $spam = ContactFormSpamGuard::looksLikeObviousSpam([
                        'ad_soyad' => $message->name,
                        'eposta' => $message->email,
                        'konu' => $message->subject,
                        'mesaj' => $message->body,
                    ]);

                    if ($spam) {
                        $messageIds[] = $message->id;
                    }
                }
            });

        $this->line('Spam yorum: '.count($reviewIds));
        $this->line('Spam iletişim: '.count($messageIds));

        if ($reviewIds === [] && $messageIds === []) {
            $this->info('Temizlenecek kayıt yok.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-run tamamlandı.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Seçilen kayıtlar silinsin mi?', true)) {
            return self::SUCCESS;
        }

        if ($reviewIds !== []) {
            ProductReview::query()->whereIn('id', $reviewIds)->delete();
        }

        if ($messageIds !== []) {
            ContactMessage::query()->whereIn('id', $messageIds)->delete();
        }

        $this->info('Spam kayıtlar silindi.');

        return self::SUCCESS;
    }
}
