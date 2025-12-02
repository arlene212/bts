<?php
require_once __DIR__ . '/Captcha.php';
Captcha::start();
$code = Captcha::currentCode();
if (!$code) {
    $code = Captcha::generate();
}

header('Content-Type: audio/wav');
header('Cache-Control: no-store, no-cache, must-revalidate');

// Simple tone-based audio: map chars to frequencies
function charFreq($c) {
    if (ctype_digit($c)) return 500 + (int)$c * 20;
    if (ctype_upper($c)) return 800 + (ord($c) - 65) * 10;
    if (ctype_lower($c)) return 1000 + (ord($c) - 97) * 8;
    return 600;
}

$sampleRate = 8000; $bits = 16; $channels = 1;
$durationPerChar = 0.4; // seconds
$silence = 0.12; // gap seconds
$samples = '';

for ($i = 0; $i < strlen($code); $i++) {
    $f = charFreq($code[$i]);
    $len = (int)($sampleRate * $durationPerChar);
    for ($n = 0; $n < $len; $n++) {
        $val = (int)(10000 * sin(2 * M_PI * $f * ($n / $sampleRate)));
        $samples .= pack('v', $val & 0xFFFF);
    }
    // silence gap
    $gapLen = (int)($sampleRate * $silence);
    for ($n = 0; $n < $gapLen; $n++) {
        $samples .= pack('v', 0);
    }
}

$dataSize = strlen($samples);
$byteRate = $sampleRate * $channels * ($bits / 8);
$blockAlign = $channels * ($bits / 8);

// WAV header
echo 'RIFF';
echo pack('V', 36 + $dataSize);
echo 'WAVEfmt ';
echo pack('V', 16); // PCM
echo pack('v', 1);  // format
echo pack('v', $channels);
echo pack('V', $sampleRate);
echo pack('V', $byteRate);
echo pack('v', $blockAlign);
echo pack('v', $bits);
echo 'data';
echo pack('V', $dataSize);
echo $samples;
?>
