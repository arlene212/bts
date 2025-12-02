<?php
function cert_key_path()
{
  $base = realpath(__DIR__ . '/..');
  $dir = $base . DIRECTORY_SEPARATOR . '.private';
  if (!is_dir($dir)) { mkdir($dir, 0700, true); }
  return $dir . DIRECTORY_SEPARATOR . 'cert.key';
}

function cert_load_key()
{
  $path = cert_key_path();
  if (!file_exists($path)) {
    $key = random_bytes(32);
    file_put_contents($path, $key);
    return $key;
  }
  return file_get_contents($path);
}

function cert_encrypt($plaintext)
{
  $key = cert_load_key();
  $iv = random_bytes(16);
  $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
  return json_encode(['iv' => base64_encode($iv), 'data' => base64_encode($ciphertext)]);
}

function cert_decrypt($payload)
{
  $key = cert_load_key();
  $obj = json_decode($payload, true);
  $iv = base64_decode($obj['iv'] ?? '');
  $data = base64_decode($obj['data'] ?? '');
  return openssl_decrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}
?>
