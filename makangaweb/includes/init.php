<?php
// Loaded at the top of every page: session, database and helper functions.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

/* ---------- Output and navigation helpers ---------- */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function money($amount)
{
    return 'UGX ' . number_format((float)$amount);
}

function project_icon($title)
{
    $title = strtolower($title);

    if (str_contains($title, 'voltpay') || str_contains($title, 'power')) {
        return '⚡';
    }
    if (str_contains($title, 'gas')) {
        return '🔥';
    }
    if (str_contains($title, 'school')) {
        return '🎓';
    }
    if (str_contains($title, 'shop')) {
        return '🛍️';
    }

    return '🌐';
}

function project_image($title)
{
    $title = strtolower($title);

    if (str_contains($title, 'voltpay') || str_contains($title, 'power')) {
        return 'images/project-voltpay.jpg';
    }
    if (str_contains($title, 'gas')) {
        return 'images/project-gas-guardian.jpg';
    }
    if (str_contains($title, 'school')) {
        return 'images/project-school.jpg';
    }
    if (str_contains($title, 'shop')) {
        return 'images/project-shop.jpg';
    }

    return 'images/workspace.jpg';
}

function flash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $message];
}

function get_flash()
{
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/* ---------- Authentication helpers ---------- */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function is_admin()
{
    static $current_role = null;
    if (!is_logged_in()) {
        return false;
    }

    if ($current_role === null) {
        $current_role = (string)q_val(
            $GLOBALS['conn'],
            'SELECT role FROM users WHERE user_id = ?',
            'i',
            [(int)$_SESSION['user_id']]
        );
        if ($current_role !== '') {
            $_SESSION['role'] = $current_role;
        }
    }

    return $current_role === 'admin';
}

function require_login()
{
    if (!is_logged_in()) {
        flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

function require_admin()
{
    require_login();
    if (!is_admin()) {
        flash('error', 'That area is for administrators only.');
        redirect('profile.php');
    }
}

/* ---------- CSRF protection for forms ---------- */
function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_ok()
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/* ---------- Prepared-statement shortcuts ---------- */
function q_run($conn, $sql, $types = '', $params = [])
{
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

function q_all($conn, $sql, $types = '', $params = [])
{
    $stmt = q_run($conn, $sql, $types, $params);
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function q_one($conn, $sql, $types = '', $params = [])
{
    $rows = q_all($conn, $sql, $types, $params);
    return $rows[0] ?? null;
}

function q_val($conn, $sql, $types = '', $params = [])
{
    $row = q_one($conn, $sql, $types, $params);
    return $row ? reset($row) : 0;
}

// For INSERT / UPDATE / DELETE: returns the number of affected rows.
function q_exec($conn, $sql, $types = '', $params = [])
{
    $stmt = q_run($conn, $sql, $types, $params);
    $n = $stmt->affected_rows;
    $stmt->close();
    return $n;
}
