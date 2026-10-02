<?php
$src = 'C:/Users/Ashu/.gemini/antigravity-ide/brain/8144735a-087a-4336-aa0e-d13fdc7238c0/.user_uploaded/media_1790921021405.png';
$im = imagecreatefrompng($src);
$w = imagesx($im);
$h = imagesy($im);

$minX = $w; $minY = $h; $maxX = 0; $maxY = 0;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $alpha = ($rgb >> 24) & 0x7F;

        // Non-white and non-transparent
        if ($alpha < 120 && ($r < 245 || $g < 245 || $b < 245)) {
            if ($x < $minX) $minX = $x;
            if ($x > $maxX) $maxX = $x;
            if ($y < $minY) $minY = $y;
            if ($y > $maxY) $maxY = $y;
        }
    }
}

echo "Image dimensions: {$w}x{$h}\n";
echo "Content bounding box: minX=$minX, minY=$minY, maxX=$maxX, maxY=$maxY\n";
$contentW = $maxX - $minX;
$contentH = $maxY - $minY;
echo "Content size: {$contentW}x{$contentH}\n";
