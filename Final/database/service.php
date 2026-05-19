<?php
// Basic DB bootstrap for the new-code screens (no router logic).
// - Creates a shared $pdo connection (via db.php)
// - Provides shared helpers for HTML escaping + auth/session
//
// NOTE: This file was renamed from "bootstrap.php" to "Service.php" per request.

// Basic auth/session helpers live here to keep pages simple (no header/footer includes).
session_start();

require_once __DIR__ . '/db.php'; // provides $pdo

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Database connection not available.');
}

$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/**
 * DB helper: check if a column exists in a table (used for backward-compatible schema updates).
 */
function tableHasColumn(string $table, string $column): bool {
    global $pdo;
    static $cache = [];
    $key = strtolower($table . ':' . $column);
    if (array_key_exists($key, $cache)) return $cache[$key];
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        $cache[$key] = (bool)$stmt->fetch();
    } catch (Throwable $e) {
        $cache[$key] = false;
    }
    return $cache[$key];
}

// Seed minimal default users (keeps the app usable after SQL import).
try {
    // users (basic auth)
    // Note: table is created in usjr-database.sql; this insert only runs when table exists.
    if (tableHasColumn('users', 'usertype') && tableHasColumn('users', 'userrole')) {
        $pdo->exec("INSERT IGNORE INTO users (username, password, usertype, userrole, status) VALUES ('admin', 'admin', 'Administrator', 'Administrator', 1)");
    } else {
        $pdo->exec("INSERT IGNORE INTO users (username, password, role, status) VALUES ('admin', 'admin', 'admin', 1)");
    }
} catch (Throwable $e) {
    // Keep it simple: don't crash the whole app for bootstrap inserts.
    // If tables aren't created yet, the user should import the SQL first.
}

// Remove any legacy "Others" placeholder rows (id=0) if they exist.
// Requirement: "NO OTHERS MUST SEE IN DATABASE AND EVERY PAGES".
try { $pdo->exec("DELETE FROM students WHERE studid = 0"); } catch (Throwable $e) {}
try { $pdo->exec("DELETE FROM programs WHERE progid = 0"); } catch (Throwable $e) {}
try { $pdo->exec("DELETE FROM departments WHERE deptid = 0"); } catch (Throwable $e) {}
try { $pdo->exec("DELETE FROM colleges WHERE collid = 0"); } catch (Throwable $e) {}
// Remove any legacy "Others" roles in users table (older code used role='others').
try {
    if (tableHasColumn('users', 'role')) {
        $pdo->exec("DELETE FROM users WHERE LOWER(role) = 'others'");
    }
    if (tableHasColumn('users', 'userrole')) {
        $pdo->exec("DELETE FROM users WHERE LOWER(userrole) = 'others'");
    }
} catch (Throwable $e) {}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Require the user to be logged in.
 * Pass the correct login path from the current page (e.g. ../../login.php).
 */
function requireLogin(string $loginPath): void {
    if (!currentUser()) {
        header('Location: ' . $loginPath);
        exit;
    }
}

/**
 * Basic DB-backed login.
 * Supports either plain-text passwords (admin/admin) OR password_hash() values.
 */
function loginUser(string $username, string $password): bool {
    global $pdo;

    $username = trim($username);
    if ($username === '' || $password === '') return false;

    try {
        $cols = ['username', 'password', 'status'];
        if (tableHasColumn('users', 'usertype')) $cols[] = 'usertype';
        if (tableHasColumn('users', 'userrole')) $cols[] = 'userrole';
        if (tableHasColumn('users', 'role')) $cols[] = 'role';
        $stmt = $pdo->prepare("SELECT " . implode(', ', $cols) . " FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        if (!$row) return false;
        if ((int)($row['status'] ?? 1) !== 1) return false;

        $stored = (string)($row['password'] ?? '');

        $ok = false;
        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
            $ok = password_verify($password, $stored);
        } else {
            $ok = hash_equals($stored, $password);
        }

        if (!$ok) return false;

        $roleValue = (string)($row['userrole'] ?? $row['role'] ?? 'staff');

        $_SESSION['user'] = [
            'username' => (string)$row['username'],
            'role'     => $roleValue,
        ];
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function logoutUser(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}