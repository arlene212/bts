<?php
require_once __DIR__ . '/Captcha.php';

Captcha::start();
$locked = Captcha::isLocked();
$code = $locked ? (Captcha::currentCode() ?: 'LOCKED') : Captcha::generate();

// Fallback to SVG if GD is not available
$hasGd = function_exists('imagecreatetruecolor');
if (!$hasGd) {
    header('Content-Type: image/svg+xml');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    $w = 300; $h = 80;
    $noise = '';
    for ($i = 0; $i < 80; $i++) {
        $x1 = random_int(0, $w); $y1 = random_int(0, $h);
        $x2 = random_int(0, $w); $y2 = random_int(0, $h);
        $noise .= "<line x1='$x1' y1='$y1' x2='$x2' y2='$y2' stroke='rgba(180,180,180,0.5)' stroke-width='1'/>";
    }
    $chars = str_split($code);
    $x = 20; $svgChars = '';
    foreach ($chars as $ch) {
        $angle = random_int(-20, 20);
        $y = 45 + random_int(-8, 8);
        $svgChars .= "<text x='$x' y='$y' transform='rotate($angle $x,$y)' fill='#222' font-size='28' font-family='Inter, Arial, sans-serif'>$ch</text>";
        $x += 30 + random_int(0, 10);
    }
    $wave = '';
    $amp = 5; $freq = 0.15;
    $path = 'M 0 ' . (int)($h/2);
    for ($wx = 1; $wx < $w; $wx++) {
        $wy = (int)($h/2 + sin($wx * $freq) * $amp);
        $path .= " L $wx $wy";
    }
    $wave = "<path d='$path' stroke='#777' fill='none'/>";
    echo "<svg xmlns='http://www.w3.org/2000/svg' width='$w' height='$h' role='img' aria-label='CAPTCHA image'><rect width='100%' height='100%' fill='#f5f5f5'/>$noise$svgChars$wave</svg>";
    exit;
}

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate');

$width = 300; $height = 80;
$im = imagecreatetruecolor($width, $height);
imageantialias($im, true);
$bg = imagecolorallocate($im, 245, 245, 245);
imagefilledrectangle($im, 0, 0, $width, $height, $bg);

$noise1 = imagecolorallocate($im, 220, 220, 220);
$noise2 = imagecolorallocate($im, 200, 200, 200);
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($im, random_int(0, $width - 1), random_int(0, $height - 1), $noise1);
}
for ($i = 0; $i < 10; $i++) {
    imageline($im, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $noise2);
}

$palette = [
    imagecolorallocate($im, 30, 30, 30),
    imagecolorallocate($im, 0, 80, 160),
    imagecolorallocate($im, 160, 0, 100),
    imagecolorallocate($im, 20, 120, 20),
];

$chars = str_split($code);
$x = 20; $yBase = 40;
foreach ($chars as $idx => $ch) {
    $font = 5; // built-in font
    $col = $palette[$idx % count($palette)];
    $angle = random_int(-20, 20);
    $y = $yBase + random_int(-8, 8);
    $cw = 18; $chh = 24;
    $charIm = imagecreatetruecolor($cw, $chh);
    $tbg = imagecolorallocate($charIm, 255, 255, 255);
    imagefilledrectangle($charIm, 0, 0, $cw, $chh, $tbg);
    imagestring($charIm, $font, 4, 4, $chars[$idx], $col);
    $rot = imagerotate($charIm, $angle, $tbg);
    imagecolortransparent($rot, $tbg);
    imagecopy($im, $rot, $x, $y - 10, 0, 0, imagesx($rot), imagesy($rot));
    imagedestroy($charIm); imagedestroy($rot);
    $x += 30 + random_int(0, 10);
}

$waveCol = imagecolorallocate($im, 120, 120, 120);
$amp = 5; $freq = 0.15;
for ($wx = 0; $wx < $width; $wx++) {
    $wy = (int)($height/2 + sin($wx * $freq) * $amp);
    imagesetpixel($im, $wx, $wy, $waveCol);
}

imagepng($im);
imagedestroy($im);
?>
