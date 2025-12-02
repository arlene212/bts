<?php
class Mailer {
    private $config;

    public function __construct() {
        $base = __DIR__ . '/../config/mail.php';
        $local = __DIR__ . '/../config/mail.local.php';
        $cfg = file_exists($base) ? include $base : [];
        $localCfg = file_exists($local) ? include $local : [];
        $this->config = array_merge([
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_secure' => 'tls',
            'smtp_username' => '',
            'smtp_password' => '',
            'from_email' => '',
            'from_name' => 'BTS eLMS',
        ], $cfg, $localCfg);
    }

    public function send($toEmail, $toName, $subject, $htmlBody) {
        if (empty($this->config['smtp_username']) || empty($this->config['smtp_password']) || empty($this->config['from_email'])) {
            return [false, 'Email is not configured. Please set config/mail.local.php'];
        }

        $host = $this->config['smtp_host'];
        $port = (int)$this->config['smtp_port'];
        $username = $this->config['smtp_username'];
        $password = $this->config['smtp_password'];
        $fromEmail = $this->config['from_email'];
        $fromName = $this->config['from_name'];

        $timeout = 30;
        $errNo = 0; $errStr = '';
        $fp = stream_socket_client("tcp://{$host}:{$port}", $errNo, $errStr, $timeout, STREAM_CLIENT_CONNECT);
        if (!$fp) return [false, 'SMTP connection failed: ' . $errStr];
        stream_set_timeout($fp, $timeout);

        $read = function($expect = null) use ($fp) {
            $resp = '';
            while (!feof($fp)) {
                $line = fgets($fp, 512);
                if ($line === false) break;
                $resp .= $line;
                if (preg_match('/^\d{3} [\s\S]*?\r?\n$/', $line)) break;
                if (substr($line, 3, 1) === ' ') break;
            }
            if ($expect && strpos($resp, (string)$expect) !== 0) {
                return [false, $resp];
            }
            return [true, $resp];
        };

        list($ok, $banner) = $read(); if (!$ok) return [false, 'SMTP banner error'];

        $send = function($cmd, $expect) use ($fp, $read) {
            fwrite($fp, $cmd . "\r\n");
            return $read($expect);
        };

        list($ok) = $send('EHLO localhost', '250'); if (!$ok) return [false, 'EHLO failed'];
        list($ok) = $send('STARTTLS', '220'); if (!$ok) return [false, 'STARTTLS failed'];
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            return [false, 'TLS negotiation failed'];
        }
        list($ok) = $send('EHLO localhost', '250'); if (!$ok) return [false, 'EHLO (post-TLS) failed'];

        list($ok) = $send('AUTH LOGIN', '334'); if (!$ok) return [false, 'AUTH LOGIN failed'];
        list($ok) = $send(base64_encode($username), '334'); if (!$ok) return [false, 'Username rejected'];
        list($ok) = $send(base64_encode($password), '235'); if (!$ok) return [false, 'Password rejected'];

        list($ok, $resp) = $send('MAIL FROM:<' . $fromEmail . '>', '250'); if (!$ok) return [false, 'MAIL FROM failed: ' . $resp];
        list($ok, $resp) = $send('RCPT TO:<' . $toEmail . '>', '250'); if (!$ok) return [false, 'RCPT TO failed: ' . $resp];
        list($ok, $resp) = $send('DATA', '354'); if (!$ok) return [false, 'DATA failed: ' . $resp];

        $date = date('D, d M Y H:i:s O');
        $msgId = sprintf('<%s@bts>', bin2hex(random_bytes(8)));
        $headers = [];
        $headers[] = 'Date: ' . $date;
        $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
        $headers[] = 'To: ' . ($toName ? $toName . ' ' : '') . '<' . $toEmail . '>';
        $headers[] = 'Subject: ' . $subject;
        $headers[] = 'Message-ID: ' . $msgId;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';

        $body = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.";
        fwrite($fp, $body . "\r\n");
        list($ok, $resp) = $read('250'); if (!$ok) return [false, 'Body send failed: ' . $resp];
        fwrite($fp, "QUIT\r\n");
        fclose($fp);
        return [true, 'Email sent'];
    }
}
?>
