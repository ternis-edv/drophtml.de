<?php

// Renders public/og.png (1200x630) — the Open Graph / Twitter share card.
//
// The brand icon is rasterized from public/brand/icon-white.svg via macOS
// QuickLook (qlmanage), recolored to the brand palette, then cropped and
// composited with text/layout in PHP GD. Requires macOS (qlmanage) and the
// bundled "Avenir Next" system font. Run from anywhere: php scripts/render-og.php
//
// Note: the brand typeface (Instrument Sans) is only shipped as WOFF/WOFF2,
// which GD cannot load, so typography uses a quality system sans instead.

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir().'/og-render-'.uniqid();
mkdir($tmp, 0755, true);

// --- rasterize the brand icon on a temporary recolored copy ---
$iconSvg = file_get_contents($root.'/public/brand/icon-white.svg');
$dropBody = 'M8 14h12v16l12 12 12-12V14h12v18.7a8 8 0 0 1-2.34 5.66L37.66 54.36a8 8 0 0 1-11.32 0L10.34 38.36A8 8 0 0 1 8 32.7Z';
// drop body -> drop indigo #4F46E5, detached square -> periwinkle #A5B4FC
$recolored = str_replace(
    '<g fill="#FFFFFF"><path d="'.$dropBody.'"/><rect x="26" y="6" width="12" height="12" rx="2"/></g>',
    '<g><path fill="#4F46E5" d="'.$dropBody.'"/><rect x="26" y="6" width="12" height="12" rx="2" fill="#A5B4FC"/></g>',
    $iconSvg
);
if ($recolored === $iconSvg) {
    exit("could not recolor brand icon (source svg changed?)\n");
}
file_put_contents($tmp.'/icon.svg', $recolored);
shell_exec("qlmanage -t -s 1600 -o '".escapeshellarg($tmp)."' '".escapeshellarg($tmp.'/icon.svg')."' 2>/dev/null");
if (! is_file($tmp.'/icon.svg.png')) {
    exit("qlmanage did not produce a raster\n");
}

const W = 1200;
const H = 630;
const LEFT = 88;
const MAX_TEXT_RIGHT = 720; // headline/subline must stay clear of the card

// --- crop the rasterized icon to its non-white bbox and restore alpha ---
$src = imagecreatefrompng($tmp.'/icon.svg.png');
$sx = imagesx($src);
$sy = imagesy($src);

$bLeft = $sx;
$bTop = $sy;
$bRight = 0;
$bBottom = 0;
for ($y = 0; $y < $sy; $y += 2) {
    for ($x = 0; $x < $sx; $x += 2) {
        $c = imagecolorat($src, $x, $y);
        if ((($c >> 16) & 0xFF) < 240 || (($c >> 8) & 0xFF) < 240 || ($c & 0xFF) < 240) {
            $bLeft = min($bLeft, $x);
            $bRight = max($bRight, $x);
            $bTop = min($bTop, $y);
            $bBottom = max($bBottom, $y);
        }
    }
}

$iconW = $bRight - $bLeft + 1;
$iconH = $bBottom - $bTop + 1;
$icon = imagecrop($src, ['x' => $bLeft, 'y' => $bTop, 'width' => $iconW, 'height' => $iconH]);

// qlmanage flattens alpha onto white; turn the white background back transparent
imagealphablending($icon, false);
imagesavealpha($icon, true);
for ($y = 0; $y < $iconH; $y++) {
    for ($x = 0; $x < $iconW; $x++) {
        $c = imagecolorat($icon, $x, $y);
        if ((($c >> 16) & 0xFF) > 244 && (($c >> 8) & 0xFF) > 244 && ($c & 0xFF) > 244) {
            imagesetpixel($icon, $x, $y, imagecolorallocatealpha($icon, 0, 0, 0, 127));
        }
    }
}

// --- canvas: brand ink #0D0E15 ---
$bg = imagecreatetruecolor(W, H);
imagefill($bg, 0, 0, imagecolorallocate($bg, 13, 14, 21));

$avenir = '/System/Library/Fonts/Avenir Next.ttc';

function textWidth(int $size, string $text): int
{
    global $avenir;
    $box = imagettfbbox($size, 0, $avenir, $text);

    return abs($box[2] - $box[0]);
}

function drawCentered(GdImage $img, int $size, float $cx, int $baselineY, int $color, string $text): void
{
    global $avenir;
    imagettftext($img, $size, 0, (int) round($cx - textWidth($size, $text) / 2), $baselineY, $color, $avenir, $text);
}

// Soft radial glow: many rings of constant tiny alpha so the center accumulates
// to ~$targetOpacity (0..1) while fading smoothly to 0 at the outer radius.
function glow(GdImage $img, int $cx, int $cy, int $outerR, int $innerR, float $targetOpacity, array $rgb): void
{
    $rings = 20;
    $perRing = 1 - pow(1 - $targetOpacity, 1 / $rings);
    $alpha = (int) round(127 * (1 - $perRing));
    $color = imagecolorallocatealpha($img, $rgb[0], $rgb[1], $rgb[2], $alpha);
    for ($i = 0; $i < $rings; $i++) {
        $r = (int) round($outerR - ($outerR - $innerR) * $i / max(1, $rings - 1));
        imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $color);
    }
}

imagealphablending($bg, true);
glow($bg, 950, 300, 560, 200, 0.10, [79, 70, 229]);   // indigo glow behind the card
glow($bg, 40, 680, 480, 160, 0.07, [165, 180, 252]);  // faint periwinkle glow bottom-left

$white = imagecolorallocate($bg, 245, 246, 249);
$muted = imagecolorallocate($bg, 154, 161, 178);
$peri = imagecolorallocate($bg, 165, 180, 252);
$indigo = imagecolorallocate($bg, 99, 102, 241);
$dim = imagecolorallocate($bg, 63, 69, 83);

// --- brand icon, pasted crisp ---
$iconSize = 120;
$iconScaled = imagecreatetruecolor($iconSize, $iconSize);
imagealphablending($iconScaled, false);
imagesavealpha($iconScaled, true);
imagefill($iconScaled, 0, 0, imagecolorallocatealpha($iconScaled, 0, 0, 0, 127));
imagecopyresampled($iconScaled, $icon, 0, 0, 0, 0, $iconSize, $iconSize, $iconW, $iconH);
imagealphablending($bg, true);
imagecopy($bg, $iconScaled, LEFT, 88, 0, 0, $iconSize, $iconSize);
$iconBottom = 88 + $iconSize;

// --- typography ---
imagettftext($bg, 26, 0, LEFT + 2, $iconBottom + 108, $peri, $avenir, 'INSTANT STATIC SITE HOSTING');

// Headline: shrink until it fits inside the safe width
$headSize = 78;
$line1 = 'Drop your site.';
while ($headSize > 40 && LEFT + textWidth($headSize, $line1) > MAX_TEXT_RIGHT) {
    $headSize--;
}
$line1Y = $iconBottom + 140 + $headSize;
$line2Y = $line1Y + $headSize + 12;
imagettftext($bg, $headSize, 0, LEFT, $line1Y, $white, $avenir, $line1);

// "Make it " in white, "live." in periwinkle, both measured from the shared prefix
$prefix = 'Make it ';
$prefixW = textWidth($headSize, $prefix);
imagettftext($bg, $headSize, 0, LEFT, $line2Y, $white, $avenir, $prefix);
imagettftext($bg, $headSize, 0, LEFT + $prefixW, $line2Y, $peri, $avenir, 'live.');

// Subline: shrink until it fits
$subSize = 23;
$sub = 'Drag & drop HTML or ZIP files - no build setup, no server.';
while ($subSize > 16 && LEFT + textWidth($subSize, $sub) > MAX_TEXT_RIGHT) {
    $subSize--;
}
$subY = $line2Y + 52;
imagettftext($bg, $subSize, 0, LEFT + 2, $subY, $muted, $avenir, $sub);

// Domain pill
$domSize = 26;
$dom = 'drophtml.de';
$domW = textWidth($domSize, $dom);
$pillH = $domSize + 22;
$domY = $subY + 26;
imagefilledrectangle($bg, LEFT, $domY, LEFT + $domW + 48, $domY + $pillH, imagecolorallocatealpha($bg, 79, 70, 229, 85));
imagettftext($bg, $domSize, 0, LEFT + 24, $domY + $pillH - 9, $white, $avenir, $dom);

// --- mock browser card, right side ---
$cardX = 756;
$cardY = 135;
$cardW = 356;
$cardH = 330;
imagefilledrectangle($bg, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, imagecolorallocate($bg, 23, 25, 35)); // #171923
imagerectangle($bg, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $dim);

// title bar
imagefilledrectangle($bg, $cardX, $cardY, $cardX + $cardW, $cardY + 44, imagecolorallocate($bg, 40, 44, 56));
$dots = [[240, 85, 77], [245, 146, 76], [92, 201, 83]];
foreach ($dots as $i => $dot) {
    imagefilledellipse($bg, $cardX + 24 + $i * 20, $cardY + 22, 11, 11, imagecolorallocate($bg, ...$dot));
}
$urlW = 216;
$urlX = $cardX + 88;
imagefilledrectangle($bg, $urlX, $cardY + 10, $urlX + $urlW, $cardY + 34, imagecolorallocate($bg, 13, 14, 21));
drawCentered($bg, 14, $urlX + $urlW / 2, $cardY + 28, $muted, 'drophtml.de/s/launch');

// preview stage
$stageX = $cardX + 22;
$stageW = $cardW - 44;
imagefilledrectangle($bg, $stageX, $cardY + 64, $stageX + $stageW, $cardY + 186, imagecolorallocate($bg, 40, 44, 56));
$stageMidX = $stageX + $stageW / 2;
imagettftext($bg, 30, 0, (int) round($stageMidX - textWidth(30, 'Hello World') / 2), $cardY + 126, $white, $avenir, 'Hello World');
imagettftext($bg, 14, 0, (int) round($stageMidX - textWidth(14, 'Deployed from index.html') / 2), $cardY + 158, $muted, $avenir, 'Deployed from index.html');

// drop-me button bar
$barY = $cardY + 208;
imagefilledrectangle($bg, $stageX, $barY, $stageX + $stageW, $barY + 40, $indigo);
$barMidY = $barY + 20;
imageline($bg, $stageX + 40, $barMidY - 6, $stageX + 40, $barMidY + 6, $white);
imageline($bg, $stageX + 34, $barMidY, $stageX + 46, $barMidY, $white);
imagettftext($bg, 14, 0, $stageX + 58, $barY + 25, $white, $avenir, 'drop-me.html');
$kb = '2.1 KB';
imagettftext($bg, 12, 0, $stageX + $stageW - 24 - textWidth(12, $kb), $barY + 25, $peri, $avenir, $kb);

// file chips
$chipY = $cardY + 268;
$chipH = 40;
$chip1W = (int) ($stageW / 2) - 7;
imagefilledrectangle($bg, $stageX, $chipY, $stageX + $chip1W, $chipY + $chipH, imagecolorallocate($bg, 40, 44, 56));
drawCentered($bg, 13, $stageX + $chip1W / 2, $chipY + 26, $white, 'styles.css');
$chip2X = $stageX + $chip1W + 14;
imagefilledrectangle($bg, $chip2X, $chipY, $stageX + $stageW, $chipY + $chipH, imagecolorallocate($bg, 40, 44, 56));
drawCentered($bg, 13, $chip2X + ($stageW - $chip1W - 14) / 2, $chipY + 26, $white, 'assets/img');

imagepng($bg, $root.'/public/og.png');
exec('rm -rf '.escapeshellarg($tmp));
echo 'public/og.png rendered ('.round(filesize($root.'/public/og.png') / 1024)." KiB)\n";
