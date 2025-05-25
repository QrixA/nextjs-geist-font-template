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
$email = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    }
    // Validate input
    elseif (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    }
    else {
        try {
            $db = Database::getInstance();
            $user = $db->getRow(
                "SELECT * FROM users WHERE email = ? AND is_active = 1",
                [$email]
            );
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Update last login
                $db->update('users', 
                    ['last_login' => date('Y-m-d H:i:s')],
                    'id = ?',
                    [$user['id']]
                );
                
                // Log activity
                logActivity($user['id'], 'login');
                
                // Set remember me cookie if requested
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + 30*24*60*60, '/', '', true, true);
                    
                    $db->update('users',
                        ['remember_token' => password_hash($token, PASSWORD_DEFAULT)],
                        'id = ?',
                        [$user['id']]
                    );
                }
                
                // Redirect to dashboard or intended page
                $redirect = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                unset($_SESSION['redirect_after_login']);
                
                header('Location: ' . $redirect);
                exit();
            } else {
                $error = 'Invalid email or password.';
                // Add delay to prevent brute force attacks
                sleep(1);
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
    <title>Login - <?php echo SITE_NAME; ?></title>
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

            <?php
            $flashMessage = getFlashMessage();
            if ($flashMessage):
            ?>
                <div class="alert alert-<?php echo $flashMessage['type']; ?>">
                    <?php echo $flashMessage['message']; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" 
                           required autocomplete="email" autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                </div>

                <div class="auth-links">
                    <a href="forgot-password.php">Forgot your password?</a>
                    <a href="register.php">Don't have an account? Register</a>
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
    .auth-form input[type="password"] {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #eee;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }

    .auth-form input[type="email"]:focus,
    .auth-form input[type="password"]:focus {
        outline: none;
        border-color: #000;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }

    .checkbox-label input[type="checkbox"] {
        width: 1rem;
        height: 1rem;
    }

    .auth-links {
        margin-top: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
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
