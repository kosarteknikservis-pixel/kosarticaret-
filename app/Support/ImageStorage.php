<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ImageStorage
{
    public static function storePublic(UploadedFile $file, string $directory, string $baseName): string
    {
        $base = Str::slug($baseName);
        if ($base === '') {
            $base = 'gorsel';
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $ext = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? $ext : 'jpg';

        $name = $base.'.'.$ext;
        $counter = 1;

        while (Storage::disk('public')->exists($directory.'/'.$name)) {
            $name = $base.'-'.$counter.'.'.$ext;
            $counter++;
        }

        return $file->storeAs($directory, $name, 'public');
    }
}
