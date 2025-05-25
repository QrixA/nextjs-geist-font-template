<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Require user to be logged in
requireLogin();

// Initialize variables
$error = '';
$success = '';
$cartItems = [];
$subtotal = 0;
$discount = 0;
$total = 0;

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
    
    // Calculate cart totals
    foreach ($_SESSION['cart'] as $item) {
        $product = $db->getRow(
            "SELECT * FROM products WHERE id = ? AND is_available = 1",
            [$item['product_id']]
        );
        
        if ($product) {
            $price = match($item['billing_cycle']) {
                'hourly' => $product['hourly_price'],
                'yearly' => $product['yearly_price'],
                default => $product['monthly_price']
            };
            
            $cartItems[] = [
                'product' => $product,
                'billing_cycle' => $item['billing_cycle'],
                'price' => $price
            ];
            
            $subtotal += $price;
        }
    }
    
    // Apply promo code if exists
    if (isset($_SESSION['promo_code'])) {
        $promo = validatePromoCode($_SESSION['promo_code']);
        if ($promo) {
            $discount = $subtotal * ($promo['discount_percentage'] / 100);
        }
    }
    
    $total = $subtotal - $discount;
    
    // Handle checkout process
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }
        
        // Validate cart is not empty
        if (empty($cartItems)) {
            throw new Exception('Your cart is empty');
        }
        
        // Check if user has sufficient balance
        if ($user['account_balance'] >= $total) {
            $db->beginTransaction();
            
            try {
                // Create order
                $orderId = $db->insert('orders', [
                    'user_id' => $userId,
                    'total_amount' => $total,
                    'promo_code' => $_SESSION['promo_code'] ?? null,
                    'discount_amount' => $discount,
                    'status' => 'paid',
                    'payment_method' => 'balance',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                // Create services
                foreach ($cartItems as $item) {
                    createService(
                        $orderId,
                        $userId,
                        $item['product']['id'],
                        $item['billing_cycle']
                    );
                }
                
                // Deduct balance
                $db->update('users',
                    ['account_balance' => $user['account_balance'] - $total],
                    'id = ?',
                    [$userId]
                );
                
                // Log transaction
                $db->insert('transactions', [
                    'user_id' => $userId,
                    'invoice_id' => 'INV-' . date('Ymd') . '-' . $orderId,
                    'amount' => $total,
                    'status' => 'paid',
                    'payment_method' => 'balance',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $db->commit();
                
                // Clear cart and promo code
                $_SESSION['cart'] = [];
                unset($_SESSION['promo_code']);
                
                // Redirect to success page
                header('Location: payment-success.php?order_id=' . $orderId);
                exit();
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
        }
        // If insufficient balance, redirect to payment gateway
        else {
            // Create pending order
            $orderId = $db->insert('orders', [
                'user_id' => $userId,
                'total_amount' => $total,
                'promo_code' => $_SESSION['promo_code'] ?? null,
                'discount_amount' => $discount,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Initialize payment
            $paymentResponse = initializeDuitkuPayment($total, $orderId, $user);
            
            if ($paymentResponse && isset($paymentResponse['paymentUrl'])) {
                header('Location: ' . $paymentResponse['paymentUrl']);
                exit();
            } else {
                throw new Exception('Failed to initialize payment');
            }
        }
    }
    
} catch (Exception $e) {
    logError($e->getMessage());
    $error = 'An error occurred: ' . $e->getMessage();
}

// Load language file
$lang = loadLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['language']) ? $_SESSION['language'] : DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="checkout-page">
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="index.php"><?php echo SITE_NAME; ?></a>
        </div>
    </nav>

    <div class="checkout-container">
        <h1>Checkout</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Add some products to your cart before checking out.</p>
                <a href="product_catalog.php" class="btn btn-primary">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="checkout-grid">
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    
                    <div class="order-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="order-item">
                                <div class="item-info">
                                    <h3><?php echo htmlspecialchars($item['product']['name']); ?></h3>
                                    <p class="item-type">
                                        <?php echo PRODUCT_TYPES[$item['product']['type']]; ?>
                                    </p>
                                    <p class="item-billing">
                                        <?php echo ucfirst($item['billing_cycle']); ?> billing
                                    </p>
                                </div>
                                <div class="item-price">
                                    <?php echo formatMoney($item['price']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-totals">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span><?php echo formatMoney($subtotal); ?></span>
                        </div>

                        <?php if ($discount > 0): ?>
                            <div class="total-row discount">
                                <span>Discount:</span>
                                <span>-<?php echo formatMoney($discount); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="total-row final">
                            <span>Total:</span>
                            <span><?php echo formatMoney($total); ?></span>
                        </div>
                    </div>
                </div>

                <div class="payment-section">
                    <h2>Payment Method</h2>
                    
                    <div class="balance-info">
                        <h3>Account Balance</h3>
                        <p class="balance-amount">
                            <?php echo formatMoney($user['account_balance']); ?>
                        </p>
                        
                        <?php if ($user['account_balance'] < $total): ?>
                            <div class="alert alert-warning">
                                Insufficient balance. You will be redirected to our payment gateway.
                            </div>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="checkout.php" class="checkout-form">
                        <input type="hidden" name="csrf_token" 
                               value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="form-actions">
                            <a href="cart.php" class="btn btn-secondary">
                                Back to Cart
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <?php echo $user['account_balance'] >= $total ? 
                                    'Pay with Balance' : 'Proceed to Payment'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <style>
    .checkout-page {
        background: #f8f9fa;
        min-height: 100vh;
        padding-bottom: 2rem;
    }

    .checkout-container {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 0 2rem;
    }

    .checkout-container h1 {
        font-size: 2rem;
        color: #000;
        margin-bottom: 2rem;
    }

    .checkout-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 2rem;
    }

    .order-summary,
    .payment-section {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 1.5rem;
    }

    .order-summary h2,
    .payment-section h2 {
        font-size: 1.25rem;
        color: #000;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }

    .order-items {
        margin-bottom: 1.5rem;
    }

    .order-item {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 1rem 0;
        border-bottom: 1px solid #eee;
    }

    .order-item:last-child {
        border-bottom: none;
    }

    .item-info h3 {
        font-size: 1rem;
        color: #000;
        margin: 0 0 0.5rem 0;
    }

    .item-type,
    .item-billing {
        color: #666;
        font-size: 0.875rem;
        margin: 0;
    }

    .item-price {
        font-weight: 600;
        color: #000;
    }

    .order-totals {
        border-top: 2px solid #eee;
        padding-top: 1rem;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
    }

    .total-row.discount {
        color: #059669;
    }

    .total-row.final {
        font-weight: 600;
        font-size: 1.125rem;
        color: #000;
        border-top: 1px solid #eee;
        margin-top: 0.5rem;
        padding-top: 1rem;
    }

    .balance-info {
        margin-bottom: 2rem;
    }

    .balance-info h3 {
        font-size: 1rem;
        color: #000;
        margin-bottom: 0.5rem;
    }

    .balance-amount {
        font-size: 1.5rem;
        font-weight: 600;
        color: #000;
        margin-bottom: 1rem;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
    }

    @media (max-width: 768px) {
        .checkout-container {
            padding: 1rem;
        }

        .checkout-grid {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
        }
    }
    </style>
</body>
</html>
