<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Require user to be logged in
requireLogin();

$error = '';
$order = null;

try {
    $db = Database::getInstance();
    
    // Get order ID from URL
    $orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
    
    if (!$orderId) {
        throw new Exception('Invalid order ID');
    }
    
    // Get order details
    $order = $db->getRow(
        "SELECT o.*, u.email, u.full_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? AND o.user_id = ?",
        [$orderId, $_SESSION['user_id']]
    );
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Get order items (services)
    $services = $db->getRows(
        "SELECT s.*, p.name as product_name, p.type as product_type 
        FROM services s 
        JOIN products p ON s.product_id = p.id 
        WHERE s.order_id = ?",
        [$orderId]
    );
    
} catch (Exception $e) {
    logError($e->getMessage());
    $error = 'An error occurred while loading the order details.';
}

// Load language file
$lang = loadLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['language']) ? $_SESSION['language'] : DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="success-page">
    <?php if ($error): ?>
        <div class="error-container">
            <div class="alert alert-error"><?php echo $error; ?></div>
            <a href="dashboard.php" class="btn btn-primary">Return to Dashboard</a>
        </div>
    <?php else: ?>
        <div class="success-container">
            <div class="success-header">
                <div class="success-icon">✓</div>
                <h1>Payment Successful!</h1>
                <p class="success-message">
                    Thank you for your order. Your payment has been processed successfully.
                </p>
            </div>

            <div class="order-details">
                <h2>Order Details</h2>
                
                <div class="detail-row">
                    <span class="label">Order ID:</span>
                    <span class="value">#<?php echo $order['id']; ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Date:</span>
                    <span class="value"><?php echo formatDate($order['created_at']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Payment Method:</span>
                    <span class="value"><?php echo ucfirst($order['payment_method']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="label">Status:</span>
                    <span class="value status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
            </div>

            <div class="services-section">
                <h2>Purchased Services</h2>
                
                <div class="services-grid">
                    <?php foreach ($services as $service): ?>
                        <div class="service-card">
                            <div class="service-header">
                                <h3><?php echo htmlspecialchars($service['product_name']); ?></h3>
                                <span class="service-type">
                                    <?php echo PRODUCT_TYPES[$service['product_type']]; ?>
                                </span>
                            </div>
                            
                            <div class="service-details">
                                <div class="detail-item">
                                    <span class="label">Region:</span>
                                    <span class="value"><?php echo $service['region']; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="label">Billing:</span>
                                    <span class="value">
                                        <?php echo ucfirst($service['billing_cycle']); ?>
                                    </span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="label">Status:</span>
                                    <span class="value status-badge status-<?php echo $service['status']; ?>">
                                        <?php echo ucfirst($service['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="label">Expires:</span>
                                    <span class="value">
                                        <?php echo formatDate($service['expiry_date']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="order-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span><?php echo formatMoney($order['total_amount'] + $order['discount_amount']); ?></span>
                </div>
                
                <?php if ($order['discount_amount'] > 0): ?>
                    <div class="summary-row discount">
                        <span>Discount:</span>
                        <span>-<?php echo formatMoney($order['discount_amount']); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="summary-row total">
                    <span>Total Paid:</span>
                    <span><?php echo formatMoney($order['total_amount']); ?></span>
                </div>
            </div>

            <div class="success-actions">
                <a href="services.php" class="btn btn-primary">View My Services</a>
                <a href="dashboard.php" class="btn btn-secondary">Return to Dashboard</a>
            </div>
        </div>
    <?php endif; ?>

    <style>
    .success-page {
        background: #f8f9fa;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .success-container,
    .error-container {
        max-width: 800px;
        width: 100%;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 2rem;
    }

    .error-container {
        text-align: center;
        max-width: 400px;
    }

    .success-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .success-icon {
        width: 64px;
        height: 64px;
        background: #000;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 1.5rem;
    }

    .success-header h1 {
        font-size: 2rem;
        color: #000;
        margin-bottom: 1rem;
    }

    .success-message {
        color: #666;
        font-size: 1.125rem;
    }

    .order-details,
    .services-section {
        margin-bottom: 2rem;
    }

    .order-details h2,
    .services-section h2 {
        font-size: 1.25rem;
        color: #000;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #eee;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .label {
        color: #666;
    }

    .value {
        font-weight: 500;
        color: #000;
    }

    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .service-card {
        border: 1px solid #eee;
        border-radius: 5px;
        padding: 1.5rem;
    }

    .service-header {
        margin-bottom: 1rem;
    }

    .service-header h3 {
        font-size: 1.125rem;
        color: #000;
        margin: 0 0 0.5rem 0;
    }

    .service-type {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        background: #f8f9fa;
        border-radius: 5px;
        font-size: 0.875rem;
        color: #666;
    }

    .service-details {
        display: grid;
        gap: 0.75rem;
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        font-size: 0.875rem;
    }

    .order-summary {
        background: #f8f9fa;
        border-radius: 5px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #eee;
    }

    .summary-row:last-child {
        border-bottom: none;
    }

    .summary-row.discount {
        color: #059669;
    }

    .summary-row.total {
        font-weight: 600;
        font-size: 1.125rem;
        color: #000;
    }

    .success-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 5px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .status-badge.status-paid {
        background: #dcfce7;
        color: #059669;
    }

    .status-badge.status-active {
        background: #dbeafe;
        color: #2563eb;
    }

    .status-badge.status-pending {
        background: #fef9c3;
        color: #ca8a04;
    }

    @media (max-width: 640px) {
        .success-page {
            padding: 1rem;
        }

        .success-container {
            padding: 1.5rem;
        }

        .services-grid {
            grid-template-columns: 1fr;
        }

        .success-actions {
            flex-direction: column;
        }

        .success-actions .btn {
            width: 100%;
        }
    }
    </style>
</body>
</html>
