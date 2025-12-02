<?php
require_once __DIR__ . '/../DatabaseConnection.php';
require_once __DIR__ . '/../SecurityManager.php';
require_once __DIR__ . '/../SessionManager.php';
require_once __DIR__ . '/../ErrorHandler.php';
require_once __DIR__ . '/../config.php';

// AES helpers for encrypting sensitive fields in generated artifact
require_once __DIR__ . '/../../guest/includes/crypto.php';

function strong_password($length = 14) {
    $upp = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $low = 'abcdefghijklmnopqrstuvwxyz';
    $num = '0123456789';
    $spe = '!@#$%^&*()-_=+[]{};:,.?';
    $all = $upp . $low . $num . $spe;
    $pwd = '';
    $pwd .= $upp[random_int(0, strlen($upp) - 1)];
    $pwd .= $low[random_int(0, strlen($low) - 1)];
    $pwd .= $num[random_int(0, strlen($num) - 1)];
    $pwd .= $spe[random_int(0, strlen($spe) - 1)];
    for ($i = 4; $i < $length; $i++) {
        $pwd .= $all[random_int(0, strlen($all) - 1)];
    }
    return str_shuffle($pwd);
}

function next_user_id(PDO $pdo, $role) {
    switch ($role) {
        case 'admin': $prefix = '1000'; break;
        case 'trainer': $prefix = '2000'; break;
        case 'trainee': $prefix = '3000'; break;
        case 'guest': $prefix = '4000'; break;
        default: $prefix = '0000';
    }
    $stmt = $pdo->prepare('SELECT MAX(user_id) AS max_id FROM users WHERE user_id LIKE ?');
    $stmt->execute([$prefix . '%']);
    $maxId = $stmt->fetchColumn();
    if ($maxId) {
        $next = intval(substr($maxId, -6)) + 1;
    } else {
        $next = 1;
    }
    return $prefix . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
}

function load_template($path) {
    if (!file_exists($path)) {
        throw new RuntimeException('Seed template not found: ' . $path);
    }
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    if (!is_array($data) || !isset($data['accounts'])) {
        throw new RuntimeException('Invalid seed template format');
    }
    return $data['accounts'];
}

function encrypt_sensitive_value($value) {
    if ($value === null || $value === '') return null;
    if (strpos($value, 'ENC:') === 0) {
        $plain = substr($value, 4);
        return cert_encrypt($plain);
    }
    return cert_encrypt($value);
}

try {
    $db = new DatabaseConnection();
    $pdo = $db->getConnection();

    $accounts = load_template(__DIR__ . '/../seeds/users.template.json');
    $generated = [];
    $credentials = [];

    foreach ($accounts as $acct) {
        $username = $acct['username'];
        $email = $acct['email'];
        $role = $acct['role'];
        $first = $acct['first_name'];
        $last = $acct['last_name'];
        $contact = $acct['contact_number'] ?? null;
        $studentId = $acct['student_id'] ?? null;

        $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $check->execute([$email]);
        if ((int)$check->fetchColumn() > 0) {
            continue; // skip existing
        }

        $userId = next_user_id($pdo, $role);
        $plainPassword = strong_password(16);
        $hash = hash_password($plainPassword);

        $ins = $pdo->prepare('INSERT INTO users (user_id, first_name, last_name, email, contact_number, password, role, status, date_created, password_changed_at, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, "active", NOW(), NULL, 1)');

        $plainContact = $contact ? (strpos($contact, 'ENC:') === 0 ? substr($contact, 4) : $contact) : null;
        $plainStudent = $studentId ? (strpos($studentId, 'ENC:') === 0 ? substr($studentId, 4) : $studentId) : null;

        $ins->execute([$userId, $first, $last, $email, $plainContact, $hash, $role]);

        if ($plainStudent) {
            $up = $pdo->prepare('UPDATE users SET student_id = ? WHERE user_id = ?');
            $up->execute([$plainStudent, $userId]);
        }

        $generated[] = [
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'first_name' => $first,
            'last_name' => $last,
            'user_id' => $userId,
            'password_hash' => $hash,
            'sensitive' => [
                'contact_number' => $contact ? encrypt_sensitive_value($contact) : null,
                'student_id' => $studentId ? encrypt_sensitive_value($studentId) : null
            ],
            'permissions' => (
                $role === 'admin' ? ['manage_users','manage_courses','view_reports','backup_restore'] :
                ($role === 'trainer' ? ['manage_activities','grade_trainees','view_reports'] :
                ($role === 'trainee' ? ['submit_activities','view_courses'] : []))
            ),
            'access_level' => (
                $role === 'admin' ? 'full' : ($role === 'trainer' ? 'elevated' : ($role === 'trainee' ? 'standard' : 'restricted'))
            ),
            'purpose' => (
                $role === 'admin' ? 'System administration and oversight' :
                ($role === 'trainer' ? 'Course creation and trainee evaluation' :
                ($role === 'trainee' ? 'Learning participation and submissions' : ''))
            )
        ];

        $credentials[] = $username . ' | ' . $email . ' | ' . $plainPassword . ' | ' . $role . ' | ' . $userId;
    }

    // Write generated artifacts
    if (!is_dir(__DIR__ . '/../seeds')) {
        mkdir(__DIR__ . '/../seeds', 0775, true);
    }
    file_put_contents(__DIR__ . '/../seeds/users.generated.json', json_encode(['accounts' => $generated], JSON_PRETTY_PRINT));
    file_put_contents(__DIR__ . '/../seeds/users.credentials.secure.txt', implode(PHP_EOL, $credentials));

    echo json_encode([
        'status' => 'ok',
        'inserted' => count($generated),
        'generated_json' => 'php/seeds/users.generated.json',
        'credentials_file' => 'php/seeds/users.credentials.secure.txt'
    ], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>
