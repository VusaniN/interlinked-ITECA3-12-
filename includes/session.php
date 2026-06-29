<?php
// Session & Auth functions - Interlinked Marketplace

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin($redirect = 'auth/login.php') {
    if (!isLoggedIn()) {
        header("Location: " . url($redirect));
        exit;
    }
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function hasAnyRole(array $roles) {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
}

function requireRole($role, $redirect = 'index.php') {
    if (!hasRole($role)) {
        header("Location: " . url($redirect));
        exit;
    }
}

function requireSeller() {
    if (!hasAnyRole(['seller', 'admin'])) {
        $_SESSION['error'] = "You're registered as a buyer. Only sellers can list products.";
        header("Location: " . url('index.php'));
        exit;
    }
}

function requireBuyer() {
    if (!hasAnyRole(['buyer', 'admin'])) {
        header("Location: " . url('index.php'));
        exit;
    }
}

function requireAdmin() {
    if (!hasAnyRole(['admin', 'moderator'])) {
        header("Location: " . url('index.php'));
        exit;
    }
}

function currentUser() {
    return [
        'id'          => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
        'username'    => isset($_SESSION['username']) ? $_SESSION['username'] : '',
        'email'       => isset($_SESSION['email']) ? $_SESSION['email'] : '',
        'role'        => isset($_SESSION['role']) ? $_SESSION['role'] : 'guest',
        'avatar'      => isset($_SESSION['avatar']) ? $_SESSION['avatar'] : 'default.png',
        'full_name'   => isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '',
        'seller_code' => isset($_SESSION['seller_code']) ? $_SESSION['seller_code'] : null,
    ];
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return $_SESSION['csrf_token'] === $token;
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function regenerateSession() {
    session_regenerate_id(true);
}

function e($string) {
    return htmlspecialchars(isset($string) ? $string : '', ENT_QUOTES, 'UTF-8');
}
