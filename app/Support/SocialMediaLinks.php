<?php

namespace App\Support;

use App\Models\SiteSetting;

class SocialMediaLinks
{
    /** @var array<string, array{key: string, label: string}> */
    public const PLATFORMS = [
        'instagram' => ['key' => 'social_instagram_url', 'label' => 'Instagram'],
        'facebook' => ['key' => 'social_facebook_url', 'label' => 'Facebook'],
        'youtube' => ['key' => 'social_youtube_url', 'label' => 'YouTube'],
        'linkedin' => ['key' => 'social_linkedin_url', 'label' => 'LinkedIn'],
        'x' => ['key' => 'social_x_url', 'label' => 'X'],
        'tiktok' => ['key' => 'social_tiktok_url', 'label' => 'TikTok'],
    ];

    /**
     * @return list<array{platform: string, label: string, url: string}>
     */
    public static function configured(): array
    {
        $links = [];

        foreach (self::PLATFORMS as $platform => $meta) {
            $url = trim((string) SiteSetting::get($meta['key'], ''));
            if ($url === '') {
                continue;
            }

            if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
                $url = 'https://'.$url;
            }

            $links[] = [
                'platform' => $platform,
                'label' => $meta['label'],
                'url' => $url,
            ];
        }

        return $links;
    }

    /** @return list<string> */
    public static function settingKeys(): array
    {
        return array_column(self::PLATFORMS, 'key');
    }
}
