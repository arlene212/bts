<?php
require_once __DIR__ . '/SessionManager.php';

class Captcha {
    public static function start(): void {
        SessionManager::startSession();
        if (!isset($_SESSION['captcha'])) {
            $_SESSION['captcha'] = [
                'code' => null,
                'generated_at' => 0,
                'attempts' => 0,
                'lock_until' => 0,
            ];
        }
    }

    public static function generate(int $minLen = 6, int $maxLen = 8): string {
        self::start();
        if (self::isLocked()) {
            return self::currentCode() ?? '';
        }
        $length = random_int($minLen, $maxLen);
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $digits = '0123456789';
        $all = $upper . $lower . $digits;

        // Ensure mixture: at least one of each
        $codeChars = [];
        $codeChars[] = $upper[random_int(0, strlen($upper) - 1)];
        $codeChars[] = $lower[random_int(0, strlen($lower) - 1)];
        $codeChars[] = $digits[random_int(0, strlen($digits) - 1)];
        while (count($codeChars) < $length) {
            $codeChars[] = $all[random_int(0, strlen($all) - 1)];
        }
        // Shuffle
        for ($i = count($codeChars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$codeChars[$i], $codeChars[$j]] = [$codeChars[$j], $codeChars[$i]];
        }
        $code = implode('', $codeChars);
        $_SESSION['captcha']['code'] = $code;
        $_SESSION['captcha']['generated_at'] = time();
        $_SESSION['captcha']['attempts'] = 0;
        return $code;
    }

    public static function currentCode(): ?string {
        self::start();
        return $_SESSION['captcha']['code'] ?? null;
    }

    public static function validate(string $input, int $maxAttempts = 5, int $lockMinutes = 15): array {
        self::start();
        $now = time();

        if (self::isLocked()) {
            $remaining = $_SESSION['captcha']['lock_until'] - $now;
            return [false, 'Too many CAPTCHA attempts. Try again in ' . ceil($remaining / 60) . ' minutes.'];
        }

        $code = $_SESSION['captcha']['code'] ?? null;
        if (!$code) {
            return [false, 'CAPTCHA has expired. Please refresh and try again.'];
        }

        $input = trim($input);
        $ok = hash_equals($code, $input);
        if ($ok) {
            // Reset on success
            $_SESSION['captcha']['attempts'] = 0;
            // Invalidate code after successful validation
            $_SESSION['captcha']['code'] = null;
            return [true, ''];
        }

        $_SESSION['captcha']['attempts'] = (int)($_SESSION['captcha']['attempts'] ?? 0) + 1;
        if ($_SESSION['captcha']['attempts'] >= $maxAttempts) {
            $_SESSION['captcha']['lock_until'] = $now + ($lockMinutes * 60);
            return [false, 'Too many CAPTCHA attempts. Please wait ' . $lockMinutes . ' minutes.'];
        }
        return [false, 'Incorrect CAPTCHA. Please try again.'];
    }

    public static function isLocked(): bool {
        self::start();
        return isset($_SESSION['captcha']['lock_until']) && $_SESSION['captcha']['lock_until'] > time();
    }
}
?>
