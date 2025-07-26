<?php
// includes/functions.php

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    // If URL starts with /, it's absolute, otherwise make it relative to BASE_URL
    if (strpos($url, '/') === 0) {
        header("Location: $url");
    } else {
        header("Location: " . BASE_URL . "/$url");
    }
    exit();
}

function redirectIfNotLoggedIn() {
    global $auth;
    if (!$auth->isLoggedIn()) {
        redirect(BASE_URL . '/auth/login.php');
    }
}

function isAdmin() {
    global $auth;
    return $auth->getUserRole() === 'Admin';
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
?>