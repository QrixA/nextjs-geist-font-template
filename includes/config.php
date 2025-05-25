<?php
// Prevent direct access
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));
}

// Site Configuration
define('SITE_NAME', 'SakuraCloudID');
define('SITE_URL', 'http://localhost:8000');
define('DEFAULT_LANGUAGE', 'en');
define('SUPPORT_EMAIL', 'support@sakuracloud.id');
define('ADMIN_EMAIL', 'admin@sakuracloud.id');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sakuracloud');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Security Configuration
define('HASH_ALGO', PASSWORD_ARGON2ID);
define('MIN_PASSWORD_LENGTH', 8);
define('SESSION_LIFETIME', 7200); // 2 hours
define('CSRF_TOKEN_SECRET', 'your-secret-key-here');
define('JWT_SECRET', 'your-jwt-secret-here');
define('API_KEY_SALT', 'your-api-key-salt-here');

// Duitku Payment Gateway Configuration
define('DUITKU_MERCHANT_CODE', 'your-merchant-code');
define('DUITKU_API_KEY', 'your-api-key');
define('DUITKU_CALLBACK_URL', SITE_URL . '/duitku-callback.php');
define('DUITKU_RETURN_URL', SITE_URL . '/payment-success.php');
define('DUITKU_SANDBOX_URL', 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry');
define('DUITKU_PRODUCTION_URL', 'https://api.duitku.com/webapi/api/merchant/v2/inquiry');

// Service Configuration
define('DEFAULT_TIMEZONE', 'Asia/Jakarta');
define('CURRENCY', 'IDR');
define('CURRENCY_SYMBOL', 'Rp');
define('DECIMAL_PLACES', 2);
define('THOUSAND_SEPARATOR', ',');
define('DECIMAL_SEPARATOR', '.');

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hour
define('CACHE_PREFIX', 'sakura_');

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-specific-password');
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_FROM_NAME', SITE_NAME);
define('SMTP_FROM_EMAIL', SUPPORT_EMAIL);

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);
define('UPLOAD_PATH', BASE_PATH . '/uploads');

// API Configuration
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 60); // requests per minute
define('API_RESPONSE_CACHE', 300); // 5 minutes

// Service Limits
define('MAX_SERVICES_PER_USER', 10);
define('MAX_TICKETS_PER_DAY', 5);
define('MIN_TOPUP_AMOUNT', 10000); // IDR 10,000
define('MAX_TOPUP_AMOUNT', 10000000); // IDR 10,000,000

// Error Reporting
if ($_SERVER['SERVER_NAME'] === 'localhost' || strpos($_SERVER['SERVER_NAME'], 'dev.') === 0) {
    // Development environment
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG_MODE', true);
} else {
    // Production environment
    error_reporting(0);
    ini_set('display_errors', 0);
    define('DEBUG_MODE', false);
}

// Set default timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    
    session_start();
}

// Database Tables
define('TABLES', [
    'users' => 'users',
    'services' => 'services',
    'products' => 'products',
    'categories' => 'categories',
    'orders' => 'orders',
    'transactions' => 'transactions',
    'tickets' => 'tickets',
    'ticket_replies' => 'ticket_replies',
    'promo_codes' => 'promo_codes',
    'activity_logs' => 'activity_logs',
    'announcements' => 'announcements',
    'api_keys' => 'api_keys',
    'password_resets' => 'password_resets'
]);

// Product Types
define('PRODUCT_TYPES', [
    'vps' => 'Virtual Private Server',
    'dedicated' => 'Dedicated Server',
    'storage' => 'Cloud Storage',
    'backup' => 'Cloud Backup',
    'cdn' => 'Content Delivery Network'
]);

// Service Statuses
define('SERVICE_STATUSES', [
    'active' => 'Active',
    'suspended' => 'Suspended',
    'expired' => 'Expired',
    'cancelled' => 'Cancelled',
    'pending' => 'Pending'
]);

// Payment Statuses
define('PAYMENT_STATUSES', [
    'pending' => 'Pending',
    'paid' => 'Paid',
    'failed' => 'Failed',
    'refunded' => 'Refunded',
    'expired' => 'Expired'
]);

// Ticket Statuses
define('TICKET_STATUSES', [
    'open' => 'Open',
    'in_progress' => 'In Progress',
    'resolved' => 'Resolved',
    'closed' => 'Closed'
]);

// Ticket Priorities
define('TICKET_PRIORITIES', [
    'low' => 'Low',
    'medium' => 'Medium',
    'high' => 'High',
    'urgent' => 'Urgent'
]);

// Data Center Regions
define('REGIONS', [
    'id-jkt' => 'Jakarta, Indonesia',
    'sg-sin' => 'Singapore',
    'jp-tky' => 'Tokyo, Japan'
]);

// Billing Cycles
define('BILLING_CYCLES', [
    'hourly' => 'Hourly',
    'monthly' => 'Monthly',
    'yearly' => 'Yearly'
]);

// User Roles
define('USER_ROLES', [
    'user' => 'User',
    'reseller' => 'Reseller',
    'admin' => 'Administrator',
    'super_admin' => 'Super Administrator'
]);

// Activity Types
define('ACTIVITY_TYPES', [
    'login' => 'Login',
    'logout' => 'Logout',
    'register' => 'Registration',
    'order' => 'New Order',
    'payment' => 'Payment',
    'service_activation' => 'Service Activation',
    'service_suspension' => 'Service Suspension',
    'service_cancellation' => 'Service Cancellation',
    'ticket_creation' => 'Ticket Creation',
    'ticket_update' => 'Ticket Update',
    'profile_update' => 'Profile Update',
    'password_change' => 'Password Change',
    'api_access' => 'API Access'
]);

// Load environment-specific configuration if exists
$envConfig = BASE_PATH . '/config.' . (DEBUG_MODE ? 'development' : 'production') . '.php';
if (file_exists($envConfig)) {
    require_once $envConfig;
}
