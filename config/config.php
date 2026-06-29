<?php
// Global config file

if (!defined('APP_URL')) {
    // Check if we are on localhost or production
    if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == 'localhost:8080') {
        // Local setup
        $protocol = "http";
        $baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/ITECA_website_fixed";
    } else {
        // Vercel / Production
        $protocol = "https";
        $baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'];
    }
    
    define('APP_URL', rtrim($baseUrl, '/'));
}

// Full URL helper
function url($path = '') {
    return APP_URL . '/' . ltrim($path, '/');
}

// Redirect helper
function redirect($path) {
    header('Location: ' . url($path));
    exit;
}
