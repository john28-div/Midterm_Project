<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/db.php';

function detect_base_path(): string
{
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (preg_match('#/(seeker|employer|provider|admin|database)$#', $script)) {
        $script = dirname($script);
    }
    return rtrim($script, '/');
}

function url(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $base = detect_base_path();
    }
    $path = ltrim($path, '/');
    return ($base === '' ? '' : $base) . '/' . $path;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: ' . url('index.php'));
        exit;
    }
    return $user;
}

function require_role(string ...$roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        header('Location: ' . role_home($user['role']));
        exit;
    }
    return $user;
}

function role_home(string $role): string
{
    switch ($role) {
        case 'employer':
            return url('employer/dashboard.php');
        case 'provider':
            return url('provider/dashboard.php');
        case 'admin':
            return url('admin/dashboard.php');
        default:
            return url('seeker/dashboard.php');
    }
}

function login_user(array $row): void
{
    $_SESSION['user'] = [
        'user_id'    => (int) $row['user_id'],
        'full_name'  => $row['full_name'],
        'email'      => $row['email'],
        'role'       => $row['role'],
        'barangay'   => $row['barangay'],
        'contact_no' => $row['contact_no'],
        'status'     => $row['status'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function first_name(string $fullName): string
{
    $parts = preg_split('/\s+/', trim($fullName));
    return $parts[0] ?? $fullName;
}

function greeting_hiligaynon(): string
{
    $hour = (int) date('G');
    if ($hour < 12) {
        return 'Maayong buntag';
    }
    if ($hour < 18) {
        return 'Maayong hapon';
    }
    return 'Maayong gab-i';
}

function profile_completeness(array $user): int
{
    $fields = ['full_name', 'email', 'barangay', 'contact_no'];
    $filled = 0;
    foreach ($fields as $f) {
        if (!empty($user[$f])) {
            $filled++;
        }
    }
    return (int) round(($filled / count($fields)) * 100);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function barangays(): array
{
    return [
        'Agsirab', 'Agcalaga', 'Biri', 'Bonbon', 'Bontoc', 'Cayang',
        'Jayubo', 'Poblacion Ilaya', 'Poblacion Ilawod', 'Tranghawan',
    ];
}

function format_date(?string $date): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date('M j, Y', $ts) : $date;
}

function format_schedule(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    return $ts ? date('D g:i A', $ts) : $datetime;
}

function application_label(string $status): string
{
    $map = [
        'submitted'    => 'Submitted',
        'under_review' => 'Under review',
        'interview'    => 'Interview set',
        'hired'        => 'Hired',
        'rejected'     => 'Rejected',
        'withdrawn'    => 'Withdrawn',
    ];
    return $map[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function enrollment_label(string $status): string
{
    $map = [
        'pending'   => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
    return $map[$status] ?? ucfirst($status);
}

function get_employer_for_user(mysqli $conn, int $userId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM employers WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
