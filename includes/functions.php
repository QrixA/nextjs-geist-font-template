<?php
require_once 'config.php';
require_once 'db.php';

// Security Functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    if (hash_equals($_SESSION['csrf_token'], $token)) {
        if (time() - $_SESSION['csrf_token_time'] < CSRF_EXPIRY) {
            return true;
        }
    }
    return false;
}

// Session Management
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /dashboard.php');
        exit();
    }
}

// Database Helper Functions
function getUserById($userId) {
    $db = Database::getInstance();
    try {
        $stmt = $db->query("SELECT * FROM users WHERE id = ?", [$userId]);
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error fetching user: " . $e->getMessage());
        return null;
    }
}

function getGlobalAnnouncement() {
    $db = Database::getInstance();
    try {
        $sql = "SELECT * FROM announcements 
                WHERE is_global = 1 
                AND start_date <= NOW() 
                AND end_date >= NOW() 
                ORDER BY created_at DESC 
                LIMIT 1";
        $stmt = $db->query($sql);
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error fetching announcement: " . $e->getMessage());
        return null;
    }
}

// Cart Functions
function initCart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function addToCart($productId, $billingCycle) {
    initCart();
    $_SESSION['cart'][] = [
        'product_id' => $productId,
        'billing_cycle' => $billingCycle,
        'added_at' => time()
    ];
}

function removeFromCart($index) {
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
    }
}

function getCartTotal() {
    $total = 0;
    $db = Database::getInstance();
    
    foreach ($_SESSION['cart'] as $item) {
        try {
            $stmt = $db->query(
                "SELECT hourly_price, monthly_price, yearly_price 
                FROM products WHERE id = ?", 
                [$item['product_id']]
            );
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            switch ($item['billing_cycle']) {
                case 'hourly':
                    $total += $product['hourly_price'];
                    break;
                case 'monthly':
                    $total += $product['monthly_price'];
                    break;
                case 'yearly':
                    $total += $product['yearly_price'];
                    break;
            }
        } catch (Exception $e) {
            error_log("Error calculating cart total: " . $e->getMessage());
        }
    }
    
    return $total;
}

// Promo Code Functions
function validatePromoCode($code) {
    $db = Database::getInstance();
    try {
        $stmt = $db->query(
            "SELECT * FROM promo_codes 
            WHERE code = ? 
            AND expiry_date > NOW() 
            AND usage_count < usage_limit",
            [$code]
        );
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error validating promo code: " . $e->getMessage());
        return null;
    }
}

function applyPromoCode($code, $amount) {
    $promo = validatePromoCode($code);
    if ($promo) {
        return $amount * (1 - ($promo['discount_percentage'] / 100));
    }
    return $amount;
}

// Service Management Functions
function createService($orderId, $userId, $productId, $billingCycle) {
    $db = Database::getInstance();
    try {
        // Get product details
        $stmt = $db->query("SELECT region FROM products WHERE id = ?", [$productId]);
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        // Calculate expiry date based on billing cycle
        $startDate = date('Y-m-d H:i:s');
        switch ($billingCycle) {
            case 'hourly':
                $expiryDate = date('Y-m-d H:i:s', strtotime('+1 hour'));
                break;
            case 'monthly':
                $expiryDate = date('Y-m-d H:i:s', strtotime('+1 month'));
                break;
            case 'yearly':
                $expiryDate = date('Y-m-d H:i:s', strtotime('+1 year'));
                break;
        }
        
        // Insert service record
        $sql = "INSERT INTO services (order_id, user_id, product_id, region, 
                start_date, expiry_date, billing_cycle) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $orderId, 
            $userId, 
            $productId, 
            $product['region'],
            $startDate,
            $expiryDate,
            $billingCycle
        ];
        
        $db->query($sql, $params);
        return true;
    } catch (Exception $e) {
        error_log("Error creating service: " . $e->getMessage());
        return false;
    }
}

// Email Functions
function sendEmail($to, $subject, $message) {
    try {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . SITE_NAME . ' <' . SMTP_USER . '>',
            'Reply-To: ' . SMTP_USER,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    } catch (Exception $e) {
        error_log("Error sending email: " . $e->getMessage());
        return false;
    }
}

// Language Functions
function loadLanguage($lang = null) {
    if ($lang === null) {
        $lang = isset($_SESSION['language']) ? $_SESSION['language'] : DEFAULT_LANGUAGE;
    }
    
    if (!in_array($lang, AVAILABLE_LANGUAGES)) {
        $lang = DEFAULT_LANGUAGE;
    }
    
    $langFile = __DIR__ . "/../lang/{$lang}.php";
    if (file_exists($langFile)) {
        return include $langFile;
    }
    
    return include __DIR__ . "/../lang/" . DEFAULT_LANGUAGE . ".php";
}

function translate($key, $params = []) {
    static $translations = null;
    
    if ($translations === null) {
        $translations = loadLanguage();
    }
    
    $text = isset($translations[$key]) ? $translations[$key] : $key;
    
    if (!empty($params)) {
        foreach ($params as $param => $value) {
            $text = str_replace(":$param", $value, $text);
        }
    }
    
    return $text;
}

// Format Functions
function formatMoney($amount) {
    return CURRENCY . ' ' . number_format($amount, 2, ',', '.');
}

function formatDate($date) {
    return date('d M Y H:i', strtotime($date));
}

// Notification Functions
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Validation Functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return strlen($password) >= MIN_PASSWORD_LENGTH;
}

function validatePhone($phone) {
    return preg_match('/^[0-9]{10,15}$/', $phone);
}

// Payment Gateway Functions
function initializeDuitkuPayment($amount, $orderId, $user) {
    try {
        // Generate signature
        $signature = md5(DUITKU_MERCHANT_CODE . $orderId . $amount . DUITKU_API_KEY);
        
        // Prepare payment data
        $paymentData = [
            'merchantCode' => DUITKU_MERCHANT_CODE,
            'paymentAmount' => $amount,
            'merchantOrderId' => $orderId,
            'productDetails' => 'Top Up Balance - ' . SITE_NAME,
            'email' => $user['email'],
            'phoneNumber' => $user['phone'],
            'customerVaName' => $user['full_name'],
            'callbackUrl' => DUITKU_CALLBACK_URL,
            'returnUrl' => DUITKU_RETURN_URL,
            'signature' => $signature,
            'expiryPeriod' => 60 // 1 hour expiry
        ];
        
        // Send request to Duitku
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => DEBUG_MODE ? DUITKU_SANDBOX_URL : DUITKU_PRODUCTION_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($paymentData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Failed to connect to payment gateway: ' . $error);
        }
        
        return json_decode($response, true);
        
    } catch (Exception $e) {
        logError($e->getMessage());
        return null;
    }
}

function validateDuitkuCallback($merchantCode, $amount, $merchantOrderId, $signature) {
    $validSignature = md5(DUITKU_MERCHANT_CODE . $amount . $merchantOrderId . DUITKU_API_KEY);
    return $merchantCode === DUITKU_MERCHANT_CODE && hash_equals($validSignature, $signature);
}

function processSuccessfulPayment($userId, $amount) {
    $db = Database::getInstance();
    try {
        $db->beginTransaction();
        
        // Update user balance
        $sql = "UPDATE users SET account_balance = account_balance + ? WHERE id = ?";
        $db->query($sql, [$amount, $userId]);
        
        // Log transaction
        logActivity($userId, 'payment_success', ['amount' => $amount]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        logError($e->getMessage());
        return false;
    }
}

// Service Management Functions
function activateService($serviceId) {
    $db = Database::getInstance();
    try {
        $sql = "UPDATE services SET status = 'active', activated_at = NOW() WHERE id = ?";
        return $db->query($sql, [$serviceId])->affected_rows > 0;
    } catch (Exception $e) {
        logError($e->getMessage());
        return false;
    }
}

function suspendService($serviceId, $reason) {
    $db = Database::getInstance();
    try {
        $sql = "UPDATE services SET status = 'suspended', suspended_reason = ? WHERE id = ?";
        return $db->query($sql, [$reason, $serviceId])->affected_rows > 0;
    } catch (Exception $e) {
        logError($e->getMessage());
        return false;
    }
}

function renewService($serviceId) {
    $db = Database::getInstance();
    try {
        $db->beginTransaction();
        
        // Get service details
        $sql = "SELECT * FROM services WHERE id = ?";
        $service = $db->getRow($sql, [$serviceId]);
        
        if (!$service) {
            throw new Exception('Service not found');
        }
        
        // Calculate new expiry date
        $newExpiry = new DateTime($service['expiry_date']);
        switch ($service['billing_cycle']) {
            case 'hourly':
                $newExpiry->modify('+1 hour');
                break;
            case 'monthly':
                $newExpiry->modify('+1 month');
                break;
            case 'yearly':
                $newExpiry->modify('+1 year');
                break;
        }
        
        // Update service
        $sql = "UPDATE services SET 
                expiry_date = ?, 
                status = 'active',
                renewed_at = NOW() 
                WHERE id = ?";
        $db->query($sql, [$newExpiry->format('Y-m-d H:i:s'), $serviceId]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        logError($e->getMessage());
        return false;
    }
}

// Logging Functions
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " ERROR: " . $message . "\n";
    if (!empty($context)) {
        $logMessage .= "Context: " . json_encode($context) . "\n";
    }
    error_log($logMessage, 3, __DIR__ . '/../logs/error.log');
}

function logActivity($userId, $action, $details = []) {
    $db = Database::getInstance();
    try {
        $sql = "INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)";
        $db->query($sql, [$userId, $action, json_encode($details)]);
    } catch (Exception $e) {
        logError("Failed to log activity: " . $e->getMessage(), [
            'user_id' => $userId,
            'action' => $action,
            'details' => $details
        ]);
    }
}
