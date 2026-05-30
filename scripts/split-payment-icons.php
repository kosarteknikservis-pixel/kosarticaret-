<?php

$src = $argv[1] ?? '';
$dest = $argv[2] ?? '';
$scale = (int) ($argv[3] ?? 4);

if ($src === '' || $dest === '') {
    fwrite(STDERR, "Usage: php split-payment-icons.php <source.png> <dest-dir> [scale]\n");
    exit(1);
}

$keys = ['visa', 'mastercard', 'paypal', 'amex', 'visa_electron', 'maestro'];
$img = imagecreatefrompng($src);
$w = imagesx($img);
$h = imagesy($img);
$n = count($keys);
$slice = (int) floor($w / $n);

if (! is_dir($dest)) {
    mkdir($dest, 0755, true);
}

copy($src, $dest.'/cards-strip.png');

for ($i = 0; $i < $n; $i++) {
    $x = $i * $slice;
    $sw = ($i === $n - 1) ? $w - $x : $slice;
    $dw = max(1, $sw * $scale);
    $dh = max(1, $h * $scale);
    $crop = imagecreatetruecolor($dw, $dh);
    imagealphablending($crop, false);
    imagesavealpha($crop, true);
    $transparent = imagecolorallocatealpha($crop, 0, 0, 0, 127);
    imagefill($crop, 0, 0, $transparent);
    imagecopyresampled($crop, $img, 0, 0, $x, 0, $dw, $dh, $sw, $h);
    imagepng($crop, $dest.'/'.$keys[$i].'.png', 9);
    imagedestroy($crop);
}

imagedestroy($img);
echo "OK {$w}x{$h} scale={$scale}\n";
