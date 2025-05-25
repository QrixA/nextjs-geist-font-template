<?php
// Prevent direct access if already installed
if (file_exists('includes/config.php') && !isset($_GET['force'])) {
    die('Application appears to be already installed. To force reinstallation, use ?force=1');
}

// Installation steps
$steps = [
    1 => 'Database Configuration',
    2 => 'Admin Account Setup',
    3 => 'Site Configuration',
    4 => 'Installation'
];

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            // Validate and save database configuration
            if (validateDatabaseConfig($_POST)) {
                saveDatabaseConfig($_POST);
                header('Location: install.php?step=2');
                exit;
            }
            break;
            
        case 2:
            // Validate and save admin account
            if (validateAdminAccount($_POST)) {
                saveAdminAccount($_POST);
                header('Location: install.php?step=3');
                exit;
            }
            break;
            
        case 3:
            // Validate and save site configuration
            if (validateSiteConfig($_POST)) {
                saveSiteConfig($_POST);
                header('Location: install.php?step=4');
                exit;
            }
            break;
            
        case 4:
            // Perform installation
            if (performInstallation()) {
                $success = 'Installation completed successfully!';
            }
            break;
    }
}

/**
 * Validation Functions
 */

function validateDatabaseConfig($data) {
    global $error;
    
    $required = ['db_host', 'db_name', 'db_user', 'db_pass'];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $error = 'All fields are required.';
            return false;
        }
    }
    
    // Test database connection
    try {
        $conn = new mysqli(
            $data['db_host'],
            $data['db_user'],
            $data['db_pass'],
            $data['db_name']
        );
        
        if ($conn->connect_error) {
            throw new Exception($conn->connect_error);
        }
        
        $conn->close();
        return true;
        
    } catch (Exception $e) {
        $error = 'Database connection failed: ' . $e->getMessage();
        return false;
    }
}

function validateAdminAccount($data) {
    global $error;
    
    $required = ['email', 'password', 'confirm_password', 'full_name'];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $error = 'All fields are required.';
            return false;
        }
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
        return false;
    }
    
    if (strlen($data['password']) < 8) {
        $error = 'Password must be at least 8 characters long.';
        return false;
    }
    
    if ($data['password'] !== $data['confirm_password']) {
        $error = 'Passwords do not match.';
        return false;
    }
    
    return true;
}

function validateSiteConfig($data) {
    global $error;
    
    $required = ['site_name', 'site_url', 'timezone', 'currency'];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $error = 'All fields are required.';
            return false;
        }
    }
    
    if (!in_array($data['timezone'], DateTimeZone::listIdentifiers())) {
        $error = 'Invalid timezone.';
        return false;
    }
    
    return true;
}

/**
 * Installation Functions
 */

function saveDatabaseConfig($data) {
    $_SESSION['install']['db'] = [
        'host' => $data['db_host'],
        'name' => $data['db_name'],
        'user' => $data['db_user'],
        'pass' => $data['db_pass']
    ];
}

function saveAdminAccount($data) {
    $_SESSION['install']['admin'] = [
        'email' => $data['email'],
        'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
        'full_name' => $data['full_name']
    ];
}

function saveSiteConfig($data) {
    $_SESSION['install']['site'] = [
        'name' => $data['site_name'],
        'url' => $data['site_url'],
        'timezone' => $data['timezone'],
        'currency' => $data['currency']
    ];
}

function performInstallation() {
    global $error;
    
    try {
        // Create database tables
        $sql = file_get_contents('database/schema.sql');
        if (!executeSQLFile($sql)) {
            throw new Exception('Failed to create database tables.');
        }
        
        // Insert sample data
        $sql = file_get_contents('database/sample_data.sql');
        if (!executeSQLFile($sql)) {
            throw new Exception('Failed to insert sample data.');
        }
        
        // Create config file
        if (!createConfigFile()) {
            throw new Exception('Failed to create configuration file.');
        }
        
        // Create necessary directories
        $dirs = ['logs', 'uploads'];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        return false;
    }
}

function executeSQLFile($sql) {
    $db = $_SESSION['install']['db'];
    
    try {
        $conn = new mysqli($db['host'], $db['user'], $db['pass']);
        
        // Split SQL by semicolon
        $queries = array_filter(
            array_map('trim', explode(';', $sql)),
            function($query) { return !empty($query); }
        );
        
        foreach ($queries as $query) {
            if (!$conn->query($query)) {
                throw new Exception($conn->error);
            }
        }
        
        $conn->close();
        return true;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        return false;
    }
}

function createConfigFile() {
    $db = $_SESSION['install']['db'];
    $site = $_SESSION['install']['site'];
    
    $config = <<<EOT
<?php
// Database Configuration
define('DB_HOST', '{$db['host']}');
define('DB_NAME', '{$db['name']}');
define('DB_USER', '{$db['user']}');
define('DB_PASS', '{$db['pass']}');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', '{$site['name']}');
define('SITE_URL', '{$site['url']}');
define('DEFAULT_TIMEZONE', '{$site['timezone']}');
define('CURRENCY', '{$site['currency']}');

// Security Configuration
define('HASH_ALGO', PASSWORD_ARGON2ID);
define('MIN_PASSWORD_LENGTH', 8);
define('SESSION_LIFETIME', 7200);
define('CSRF_TOKEN_SECRET', ''.bin2hex(random_bytes(32)).'');
define('JWT_SECRET', ''.bin2hex(random_bytes(32)).'');
define('API_KEY_SALT', ''.bin2hex(random_bytes(32)).'');

// Duitku Payment Gateway Configuration
define('DUITKU_MERCHANT_CODE', '');
define('DUITKU_API_KEY', '');
define('DUITKU_CALLBACK_URL', SITE_URL . '/duitku-callback.php');
define('DUITKU_RETURN_URL', SITE_URL . '/payment-success.php');
define('DUITKU_SANDBOX_URL', 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry');
define('DUITKU_PRODUCTION_URL', 'https://api.duitku.com/webapi/api/merchant/v2/inquiry');

// Email Configuration
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_FROM_NAME', SITE_NAME);
define('SMTP_FROM_EMAIL', '');

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 5242880);
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('DEBUG_MODE', true);

// Set default timezone
date_default_timezone_set(DEFAULT_TIMEZONE);
EOT;

    return file_put_contents('includes/config.php', $config) !== false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install SakuraCloud</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #000;
            --text-color: #333;
            --border-color: #eee;
            --error-color: #dc2626;
            --success-color: #059669;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary-color);
        }

        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .step {
            text-align: center;
        }

        .step.active {
            color: var(--primary-color);
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .error {
            color: var(--error-color);
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        .success {
            color: var(--success-color);
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        button {
            background: var(--primary-color);
            color: #fff;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #333;
        }

        .btn-container {
            text-align: right;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Install SakuraCloud</h1>
        
        <div class="steps">
            <?php foreach ($steps as $num => $name): ?>
                <div class="step <?php echo $num === $step ? 'active' : ''; ?>">
                    <?php echo $num . '. ' . $name; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="install.php?step=<?php echo $step; ?>">
            <?php 
            switch ($step) {
                case 1: 
            ?>
                    <div class="form-group">
                        <label for="db_host">Database Host</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Database Name</label>
                        <input type="text" id="db_name" name="db_name" value="sakuracloud" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">Database User</label>
                        <input type="text" id="db_user" name="db_user" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass">Database Password</label>
                        <input type="password" id="db_pass" name="db_pass" required>
                    </div>
                <?php 
                break;
                case 2: 
                ?>
                    <div class="form-group">
                        <label for="email">Admin Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Admin Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                <?php 
                break;
                case 3: 
                ?>
                    <div class="form-group">
                        <label for="site_name">Site Name</label>
                        <input type="text" id="site_name" name="site_name" value="SakuraCloud" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_url">Site URL</label>
                        <input type="url" id="site_url" name="site_url" value="http://localhost:8000" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="timezone">Timezone</label>
                        <select id="timezone" name="timezone" required>
                            <?php foreach (DateTimeZone::listIdentifiers() as $tz): ?>
                                <option value="<?php echo $tz; ?>" <?php echo $tz === 'Asia/Jakarta' ? 'selected' : ''; ?>>
                                    <?php echo $tz; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="currency">Currency</label>
                        <input type="text" id="currency" name="currency" value="IDR" required>
                    </div>
                <?php 
                break;
                case 4: 
                ?>
                    <p>Ready to install SakuraCloud with the following configuration:</p>
                    <ul>
                        <li>Database: <?php echo $_SESSION['install']['db']['name']; ?></li>
                        <li>Admin Email: <?php echo $_SESSION['install']['admin']['email']; ?></li>
                        <li>Site Name: <?php echo $_SESSION['install']['site']['name']; ?></li>
                        <li>Timezone: <?php echo $_SESSION['install']['site']['timezone']; ?></li>
                    </ul>
                    <?php 
                    break;
            } 
            ?>
            
            <div class="btn-container">
                <button type="submit">
                    <?php echo $step === 4 ? 'Install' : 'Next'; ?>
                </button>
            </div>
        </form>
        
        <?php if ($step === 4 && $success): ?>
            <div class="btn-container" style="margin-top: 1rem;">
                <a href="index.php" style="color: var(--primary-color);">Go to Homepage</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
