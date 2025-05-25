<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';
$formData = [
    'email' => '',
    'full_name' => '',
    'phone' => ''
];

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $formData = [
        'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? '')
    ];
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    }
    // Validate input
    elseif (empty($formData['email']) || empty($formData['full_name']) || 
            empty($formData['phone']) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields.';
    }
    elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    }
    elseif (strlen($password) < MIN_PASSWORD_LENGTH) {
        $error = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long.';
    }
    elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    }
    elseif (!preg_match('/^[0-9]{10,15}$/', $formData['phone'])) {
        $error = 'Please enter a valid phone number.';
    }
    else {
        try {
            $db = Database::getInstance();
            
            // Check if email already exists
            $existingUser = $db->getRow(
                "SELECT id FROM users WHERE email = ?",
                [$formData['email']]
            );
            
            if ($existingUser) {
                $error = 'Email address is already registered.';
            } else {
                // Generate verification token
                $verificationToken = bin2hex(random_bytes(32));
                
                // Create user
                $userId = $db->insert('users', [
                    'email' => $formData['email'],
                    'password' => password_hash($password, HASH_ALGO),
                    'full_name' => $formData['full_name'],
                    'phone' => $formData['phone'],
                    'role' => 'user',
                    'is_active' => 1,
                    'email_verified' => 0,
                    'email_verification_token' => $verificationToken,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                if ($userId) {
                    // Send verification email
                    $verificationLink = SITE_URL . '/verify-email.php?token=' . $verificationToken;
                    $emailBody = "Welcome to " . SITE_NAME . "!\n\n" .
                                "Please verify your email address by clicking the link below:\n" .
                                $verificationLink . "\n\n" .
                                "If you didn't create this account, please ignore this email.";
                    
                    if (sendEmail($formData['email'], 'Verify Your Email Address', $emailBody)) {
                        $success = 'Registration successful! Please check your email to verify your account.';
                        
                        // Log activity
                        logActivity($userId, 'register');
                        
                        // Clear form data
                        $formData = ['email' => '', 'full_name' => '', 'phone' => ''];
                    } else {
                        $error = 'Registration successful but failed to send verification email. Please contact support.';
                    }
                } else {
                    throw new Exception('Failed to create user account.');
                }
            }
        } catch (Exception $e) {
            logError($e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}

// Load language file
$lang = loadLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['language']) ? $_SESSION['language'] : DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <a href="index.php" class="logo">
                    <h1><?php echo SITE_NAME; ?></h1>
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($formData['email']); ?>"
                           required autocomplete="email" autofocus>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($formData['full_name']); ?>"
                           required autocomplete="name">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($formData['phone']); ?>"
                           required autocomplete="tel"
                           pattern="[0-9]{10,15}"
                           title="Please enter a valid phone number (10-15 digits)">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" 
                           required autocomplete="new-password"
                           minlength="<?php echo MIN_PASSWORD_LENGTH; ?>">
                    <small class="form-text">
                        Must be at least <?php echo MIN_PASSWORD_LENGTH; ?> characters long
                    </small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           required autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" required>
                        <span>I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></span>
                    </label>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Create Account</button>
                </div>

                <div class="auth-links">
                    <a href="login.php">Already have an account? Login</a>
                </div>
            </form>
        </div>
    </div>

    <style>
    .auth-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        padding: 2rem;
    }

    .auth-container {
        width: 100%;
        max-width: 400px;
    }

    .auth-box {
        background: #fff;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .auth-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .auth-header h1 {
        font-size: 1.5rem;
        color: #000;
    }

    .auth-form .form-group {
        margin-bottom: 1.5rem;
    }

    .auth-form label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #000;
    }

    .auth-form input[type="email"],
    .auth-form input[type="text"],
    .auth-form input[type="tel"],
    .auth-form input[type="password"] {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #eee;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }

    .auth-form input:focus {
        outline: none;
        border-color: #000;
    }

    .form-text {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #666;
    }

    .checkbox-label {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        cursor: pointer;
        font-size: 0.875rem;
    }

    .checkbox-label input[type="checkbox"] {
        width: 1rem;
        height: 1rem;
        margin-top: 0.25rem;
    }

    .checkbox-label a {
        color: #000;
        text-decoration: none;
    }

    .checkbox-label a:hover {
        text-decoration: underline;
    }

    .auth-links {
        margin-top: 1.5rem;
        text-align: center;
    }

    .auth-links a {
        color: #666;
        text-decoration: none;
        font-size: 0.875rem;
    }

    .auth-links a:hover {
        color: #000;
    }

    @media (max-width: 640px) {
        .auth-box {
            padding: 1.5rem;
        }
    }
    </style>
</body>
</html>
