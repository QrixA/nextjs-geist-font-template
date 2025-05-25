<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Require user to be logged in
requireLogin();

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    // Get user data
    $user = $db->getRow(
        "SELECT * FROM users WHERE id = ?",
        [$userId]
    );
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Get active services
    $activeServices = $db->getRows(
        "SELECT s.*, p.name as product_name, p.type as product_type 
        FROM services s 
        JOIN products p ON s.product_id = p.id 
        WHERE s.user_id = ? AND s.status = 'active' 
        ORDER BY s.created_at DESC",
        [$userId]
    );
    
    // Get recent transactions
    $recentTransactions = $db->getRows(
        "SELECT * FROM transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5",
        [$userId]
    );
    
    // Get recent activities
    $recentActivities = $db->getRows(
        "SELECT * FROM activity_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5",
        [$userId]
    );
    
    // Get announcements
    $announcements = $db->getRows(
        "SELECT * FROM announcements 
        WHERE is_active = 1 
        AND start_date <= NOW() 
        AND end_date >= NOW() 
        ORDER BY priority DESC, created_at DESC"
    );
    
} catch (Exception $e) {
    logError($e->getMessage());
    setFlashMessage('error', 'An error occurred while loading the dashboard.');
    header('Location: error.php');
    exit();
}

// Load language file
$lang = loadLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['language']) ? $_SESSION['language'] : DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="dashboard">
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="index.php"><?php echo SITE_NAME; ?></a>
        </div>
        <div class="navbar-menu">
            <a href="product_catalog.php">Products</a>
            <a href="services.php">Services</a>
            <a href="support.php">Support</a>
        </div>
        <div class="navbar-end">
            <div class="balance">
                Balance: <?php echo formatMoney($user['account_balance']); ?>
                <a href="topup.php" class="btn btn-sm btn-secondary">Top Up</a>
            </div>
            <div class="user-menu">
                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                <div class="dropdown-menu">
                    <a href="profile.php">Profile</a>
                    <a href="settings.php">Settings</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <?php if (!empty($announcements)): ?>
            <div class="announcements">
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement alert alert-<?php echo $announcement['type']; ?>">
                        <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
                        <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Active Services -->
            <div class="dashboard-card services-card">
                <div class="card-header">
                    <h2>Active Services</h2>
                    <a href="services.php" class="btn btn-sm btn-secondary">View All</a>
                </div>
                <?php if (empty($activeServices)): ?>
                    <div class="empty-state">
                        <p>No active services</p>
                        <a href="product_catalog.php" class="btn btn-primary">Browse Products</a>
                    </div>
                <?php else: ?>
                    <div class="services-grid">
                        <?php foreach ($activeServices as $service): ?>
                            <div class="service-card">
                                <div class="service-header">
                                    <h3><?php echo htmlspecialchars($service['product_name']); ?></h3>
                                    <span class="badge badge-success">Active</span>
                                </div>
                                <div class="service-info">
                                    <p>Type: <?php echo ucfirst($service['product_type']); ?></p>
                                    <p>Region: <?php echo $service['region']; ?></p>
                                    <p>Expires: <?php echo formatDate($service['expiry_date']); ?></p>
                                </div>
                                <div class="service-actions">
                                    <a href="service_details.php?id=<?php echo $service['id']; ?>" 
                                       class="btn btn-sm btn-secondary">Manage</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Transactions -->
            <div class="dashboard-card transactions-card">
                <div class="card-header">
                    <h2>Recent Transactions</h2>
                    <a href="transactions.php" class="btn btn-sm btn-secondary">View All</a>
                </div>
                <?php if (empty($recentTransactions)): ?>
                    <div class="empty-state">
                        <p>No recent transactions</p>
                    </div>
                <?php else: ?>
                    <div class="transactions-list">
                        <?php foreach ($recentTransactions as $transaction): ?>
                            <div class="transaction-item">
                                <div class="transaction-info">
                                    <span class="transaction-id">#<?php echo $transaction['invoice_id']; ?></span>
                                    <span class="transaction-date">
                                        <?php echo formatDate($transaction['created_at']); ?>
                                    </span>
                                </div>
                                <div class="transaction-amount">
                                    <?php echo formatMoney($transaction['amount']); ?>
                                </div>
                                <div class="transaction-status">
                                    <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activities -->
            <div class="dashboard-card activities-card">
                <div class="card-header">
                    <h2>Recent Activities</h2>
                </div>
                <?php if (empty($recentActivities)): ?>
                    <div class="empty-state">
                        <p>No recent activities</p>
                    </div>
                <?php else: ?>
                    <div class="activities-list">
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php
                                    switch ($activity['action']) {
                                        case 'login':
                                            echo '🔑';
                                            break;
                                        case 'payment':
                                            echo '💳';
                                            break;
                                        case 'service_activation':
                                            echo '🚀';
                                            break;
                                        default:
                                            echo '📝';
                                    }
                                    ?>
                                </div>
                                <div class="activity-details">
                                    <span class="activity-action">
                                        <?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?>
                                    </span>
                                    <span class="activity-date">
                                        <?php echo formatDate($activity['created_at']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
    .dashboard {
        background: #f8f9fa;
        min-height: 100vh;
    }

    .navbar {
        background: #fff;
        padding: 1rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .navbar-brand a {
        font-size: 1.25rem;
        font-weight: 700;
        color: #000;
        text-decoration: none;
    }

    .navbar-menu {
        display: flex;
        gap: 2rem;
    }

    .navbar-menu a {
        color: #666;
        text-decoration: none;
        font-weight: 500;
    }

    .navbar-menu a:hover {
        color: #000;
    }

    .navbar-end {
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .balance {
        display: flex;
        align-items: center;
        gap: 1rem;
        font-weight: 500;
    }

    .user-menu {
        position: relative;
        padding: 0.5rem;
        cursor: pointer;
    }

    .user-menu:hover .dropdown-menu {
        display: block;
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: #fff;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 0.5rem 0;
        min-width: 150px;
    }

    .dropdown-menu a {
        display: block;
        padding: 0.5rem 1rem;
        color: #666;
        text-decoration: none;
    }

    .dropdown-menu a:hover {
        background: #f8f9fa;
        color: #000;
    }

    .dashboard-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 2rem;
    }

    .announcements {
        margin-bottom: 2rem;
    }

    .announcement {
        margin-bottom: 1rem;
    }

    .announcement:last-child {
        margin-bottom: 0;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .dashboard-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 1.5rem;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .card-header h2 {
        font-size: 1.25rem;
        color: #000;
        margin: 0;
    }

    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #666;
    }

    .services-grid {
        display: grid;
        gap: 1rem;
    }

    .service-card {
        border: 1px solid #eee;
        border-radius: 5px;
        padding: 1rem;
    }

    .service-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .service-header h3 {
        font-size: 1rem;
        margin: 0;
    }

    .service-info {
        margin-bottom: 1rem;
    }

    .service-info p {
        margin: 0.25rem 0;
        color: #666;
        font-size: 0.875rem;
    }

    .transactions-list,
    .activities-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .transaction-item,
    .activity-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.5rem;
        border-radius: 5px;
    }

    .transaction-item:hover,
    .activity-item:hover {
        background: #f8f9fa;
    }

    .transaction-info {
        flex: 1;
    }

    .transaction-id {
        display: block;
        font-weight: 500;
    }

    .transaction-date,
    .activity-date {
        font-size: 0.875rem;
        color: #666;
    }

    .activity-icon {
        font-size: 1.5rem;
    }

    .activity-details {
        flex: 1;
    }

    .activity-action {
        display: block;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .navbar {
            flex-direction: column;
            gap: 1rem;
            padding: 1rem;
        }

        .navbar-menu {
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
        }

        .navbar-end {
            width: 100%;
            justify-content: space-between;
        }

        .dashboard-container {
            padding: 1rem;
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>
</html>
