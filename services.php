<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Require login
requireLogin();

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    // Get user data
    $user = getUserById($userId);
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Handle service renewal
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['renew_service'])) {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Invalid request. Please try again.');
        } else {
            $serviceId = (int)$_POST['service_id'];
            
            // Get service details
            $stmt = $db->query(
                "SELECT s.*, p.name, p.hourly_price, p.monthly_price, p.yearly_price 
                FROM services s 
                JOIN products p ON s.product_id = p.id 
                WHERE s.id = ? AND s.user_id = ?",
                [$serviceId, $userId]
            );
            $service = $stmt->get_result()->fetch_assoc();
            
            if ($service) {
                // Calculate renewal price
                $price = 0;
                switch ($service['billing_cycle']) {
                    case 'hourly':
                        $price = $service['hourly_price'];
                        break;
                    case 'monthly':
                        $price = $service['monthly_price'];
                        break;
                    case 'yearly':
                        $price = $service['yearly_price'];
                        break;
                }
                
                // Check balance
                if ($price > $user['account_balance']) {
                    setFlashMessage('error', 'Insufficient balance for renewal.');
                } else {
                    // Start transaction
                    $db->getConnection()->begin_transaction();
                    
                    try {
                        // Create renewal order
                        $stmt = $db->query(
                            "INSERT INTO orders (user_id, total_amount) VALUES (?, ?)",
                            [$userId, $price]
                        );
                        $orderId = $db->getConnection()->insert_id;
                        
                        // Update service expiry
                        $newExpiry = '';
                        switch ($service['billing_cycle']) {
                            case 'hourly':
                                $newExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                                break;
                            case 'monthly':
                                $newExpiry = date('Y-m-d H:i:s', strtotime('+1 month'));
                                break;
                            case 'yearly':
                                $newExpiry = date('Y-m-d H:i:s', strtotime('+1 year'));
                                break;
                        }
                        
                        $db->query(
                            "UPDATE services SET 
                            expiry_date = ?, 
                            status = 'active',
                            order_id = ? 
                            WHERE id = ?",
                            [$newExpiry, $orderId, $serviceId]
                        );
                        
                        // Deduct balance
                        $db->query(
                            "UPDATE users SET account_balance = account_balance - ? WHERE id = ?",
                            [$price, $userId]
                        );
                        
                        $db->getConnection()->commit();
                        setFlashMessage('success', 'Service renewed successfully.');
                        
                    } catch (Exception $e) {
                        $db->getConnection()->rollback();
                        throw $e;
                    }
                }
            }
        }
        
        header('Location: services.php');
        exit();
    }
    
    // Get all services
    $stmt = $db->query(
        "SELECT s.*, p.name as product_name, p.specs, p.region,
        o.total_amount, o.order_date
        FROM services s 
        JOIN products p ON s.product_id = p.id 
        JOIN orders o ON s.order_id = o.id
        WHERE s.user_id = ?
        ORDER BY s.created_at DESC",
        [$userId]
    );
    $services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    logError("Services error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Services - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="services-body">
    <header class="header">
        <nav class="nav">
            <div class="logo">
                <a href="index.php"><h1><?php echo SITE_NAME; ?></h1></a>
            </div>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="product_catalog.php">Products</a>
                <a href="cart.php">Cart</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <main class="services-main">
        <div class="services-container">
            <h1>My Services</h1>

            <!-- Flash Messages -->
            <?php $flashMessage = getFlashMessage(); ?>
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?php echo $flashMessage['type']; ?>">
                    <?php echo $flashMessage['message']; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($services)): ?>
                <div class="empty-services">
                    <p>You don't have any active services.</p>
                    <a href="product_catalog.php" class="btn btn-primary">Browse Products</a>
                </div>
            <?php else: ?>
                <div class="services-grid">
                    <?php foreach ($services as $service): ?>
                    <div class="service-card">
                        <div class="service-header">
                            <h2><?php echo htmlspecialchars($service['product_name']); ?></h2>
                            <span class="status-badge status-<?php echo $service['status']; ?>">
                                <?php echo ucfirst($service['status']); ?>
                            </span>
                        </div>

                        <div class="service-details">
                            <div class="detail-row">
                                <span class="detail-label">Specs:</span>
                                <span class="detail-value">
                                    <?php echo nl2br(htmlspecialchars($service['specs'])); ?>
                                </span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Region:</span>
                                <span class="detail-value">
                                    <?php echo htmlspecialchars($service['region']); ?>
                                </span>
                            </div>
                            
                            <?php if ($service['ip_address']): ?>
                            <div class="detail-row">
                                <span class="detail-label">IP Address:</span>
                                <span class="detail-value">
                                    <?php echo htmlspecialchars($service['ip_address']); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="detail-row">
                                <span class="detail-label">Billing Cycle:</span>
                                <span class="detail-value">
                                    <?php echo ucfirst($service['billing_cycle']); ?>
                                </span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Start Date:</span>
                                <span class="detail-value">
                                    <?php echo formatDate($service['start_date']); ?>
                                </span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Expiry Date:</span>
                                <span class="detail-value <?php echo strtotime($service['expiry_date']) < time() ? 'text-danger' : ''; ?>">
                                    <?php echo formatDate($service['expiry_date']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="service-actions">
                            <?php if ($service['status'] !== 'active'): ?>
                                <form method="POST" action="services.php" class="renew-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <button type="submit" name="renew_service" class="btn btn-primary btn-block">
                                        Renew Service
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <a href="new_ticket.php?service_id=<?php echo $service['id']; ?>" 
                               class="btn btn-secondary btn-block">
                                Open Support Ticket
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <style>
    .services-body {
        background-color: #f8f9fa;
        padding-top: 64px;
    }

    .services-main {
        padding: 2rem;
    }

    .services-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .services-container h1 {
        color: #000;
        font-size: 2rem;
        margin-bottom: 2rem;
    }

    .empty-services {
        text-align: center;
        padding: 4rem 2rem;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .empty-services p {
        color: #666;
        margin-bottom: 1.5rem;
    }

    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 1.5rem;
    }

    .service-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 1.5rem;
    }

    .service-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .service-header h2 {
        color: #000;
        font-size: 1.25rem;
        margin: 0;
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .status-active {
        background: #ecfdf5;
        color: #059669;
    }

    .status-suspended {
        background: #fff7ed;
        color: #c2410c;
    }

    .status-expired {
        background: #fef2f2;
        color: #dc2626;
    }

    .service-details {
        margin-bottom: 1.5rem;
    }

    .detail-row {
        display: flex;
        margin-bottom: 0.75rem;
    }

    .detail-label {
        width: 120px;
        color: #666;
        font-size: 0.875rem;
    }

    .detail-value {
        flex: 1;
        color: #000;
        font-size: 0.875rem;
    }

    .text-danger {
        color: #dc2626;
    }

    .service-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .btn-block {
        width: 100%;
    }

    @media (max-width: 768px) {
        .services-grid {
            grid-template-columns: 1fr;
        }

        .service-card {
            padding: 1rem;
        }

        .detail-row {
            flex-direction: column;
        }

        .detail-label {
            width: auto;
            margin-bottom: 0.25rem;
        }
    }
    </style>
</body>
</html>
