<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (isLoggedIn()) {
    try {
        $db = Database::getInstance();
        
        // Log the logout activity
        logActivity($_SESSION['user_id'], 'logout');
        
        // Clear remember me token if exists
        if (isset($_COOKIE['remember_token'])) {
            $db->update('users',
                ['remember_token' => null],
                'id = ?',
                [$_SESSION['user_id']]
            );
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        // Update last activity
        $db->update('users',
            ['last_activity' => date('Y-m-d H:i:s')],
            'id = ?',
            [$_SESSION['user_id']]
        );
        
    } catch (Exception $e) {
        logError($e->getMessage());
    }
}

// Destroy all session data
$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

session_destroy();

// Clear all cookies
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time() - 3600, '/');
    }
}

// Redirect to login page with success message
setFlashMessage('success', 'You have been successfully logged out.');
header('Location: login.php');
exit();
