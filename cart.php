<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Require user to be logged in
requireLogin();

// Initialize cart if not exists
initCart();

$error = '';
$success = '';

try {
    $db = Database::getInstance();
    
    // Handle cart actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }
        
        if (isset($_POST['add_to_cart'])) {
            $productId = (int)$_POST['product_id'];
            $billingCycle = $_POST['billing_cycle'];
            
            // Validate product exists and is available
            $product = $db->getRow(
                "SELECT * FROM products WHERE id = ? AND is_available = 1",
                [$productId]
            );
            
            if ($product) {
                addToCart($productId, $billingCycle);
                $success = 'Product added to cart successfully.';
            } else {
                $error = 'Product not found or unavailable.';
            }
        }
        
        elseif (isset($_POST['remove_from_cart'])) {
            $index = (int)$_POST['cart_index'];
            removeFromCart($index);
            $success = 'Item removed from cart successfully.';
        }
        
        elseif (isset($_POST['update_cart'])) {
            foreach ($_POST['billing_cycle'] as $index => $cycle) {
                if (isset($_SESSION['cart'][$index])) {
                    $_SESSION['cart'][$index]['billing_cycle'] = $cycle;
                }
            }
            $success = 'Cart updated successfully.';
        }
    }
    
    // Calculate cart totals
    $cartItems = [];
    $subtotal = 0;
    
    foreach ($_SESSION['cart'] as $index => $item) {
        $product = $db->getRow(
            "SELECT * FROM products WHERE id = ?",
            [$item['product_id']]
        );
        
        if ($product) {
            $price = match($item['billing_cycle']) {
                'hourly' => $product['hourly_price'],
                'yearly' => $product['yearly_price'],
                default => $product['monthly_price']
            };
            
            $cartItems[] = [
                'index' => $index,
                'product' => $product,
                'billing_cycle' => $item['billing_cycle'],
                'price' => $price
            ];
            
            $subtotal += $price;
        }
    }
    
    // Apply promo code if exists
    $discount = 0;
    if (isset($_SESSION['promo_code'])) {
        $promo = validatePromoCode($_SESSION['promo_code']);
        if ($promo) {
            $discount = $subtotal * ($promo['discount_percentage'] / 100);
        }
    }
    
    $total = $subtotal - $discount;
    
} catch (Exception $e) {
    logError($e->getMessage());
    $error = 'An error occurred. Please try again later.';
}

// Load language file
$lang = loadLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['language']) ? $_SESSION['language'] : DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="cart-page">
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="index.php"><?php echo SITE_NAME; ?></a>
        </div>
        <div class="navbar-menu">
            <a href="product_catalog.php">Products</a>
            <a href="services.php">Services</a>
            <a href="support.php">Support</a>
        </div>
    </nav>

    <div class="cart-container">
        <h1>Shopping Cart</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Browse our products and add some items to your cart.</p>
                <a href="product_catalog.php" class="btn btn-primary">Browse Products</a>
            </div>
        <?php else: ?>
            <form method="POST" action="cart.php" class="cart-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <div class="item-info">
                                <h3><?php echo htmlspecialchars($item['product']['name']); ?></h3>
                                <p class="item-type">
                                    <?php echo PRODUCT_TYPES[$item['product']['type']]; ?>
                                </p>
                                <p class="item-region">
                                    Region: <?php echo REGIONS[$item['product']['region']]; ?>
                                </p>
                            </div>

                            <div class="item-billing">
                                <select name="billing_cycle[<?php echo $item['index']; ?>]" 
                                        class="billing-select">
                                    <option value="hourly" 
                                            <?php echo $item['billing_cycle'] === 'hourly' ? 'selected' : ''; ?>>
                                        Hourly (<?php echo formatMoney($item['product']['hourly_price']); ?>/hr)
                                    </option>
                                    <option value="monthly" 
                                            <?php echo $item['billing_cycle'] === 'monthly' ? 'selected' : ''; ?>>
                                        Monthly (<?php echo formatMoney($item['product']['monthly_price']); ?>/mo)
                                    </option>
                                    <option value="yearly" 
                                            <?php echo $item['billing_cycle'] === 'yearly' ? 'selected' : ''; ?>>
                                        Yearly (<?php echo formatMoney($item['product']['yearly_price']); ?>/yr)
                                    </option>
                                </select>
                            </div>

                            <div class="item-price">
                                <?php echo formatMoney($item['price']); ?>
                            </div>

                            <div class="item-actions">
                                <button type="submit" name="remove_from_cart" 
                                        value="<?php echo $item['index']; ?>" 
                                        class="btn btn-sm btn-danger">
                                    Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span><?php echo formatMoney($subtotal); ?></span>
                    </div>

                    <?php if ($discount > 0): ?>
                        <div class="summary-row discount">
                            <span>Discount:</span>
                            <span>-<?php echo formatMoney($discount); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="summary-row total">
                        <span>Total:</span>
                        <span><?php echo formatMoney($total); ?></span>
                    </div>

                    <div class="cart-actions">
                        <button type="submit" name="update_cart" class="btn btn-secondary">
                            Update Cart
                        </button>
                        <a href="checkout.php" class="btn btn-primary">
                            Proceed to Checkout
                        </a>
                    </div>
                </div>
            </form>

            <?php if (!isset($_SESSION['promo_code'])): ?>
                <div class="promo-code">
                    <form method="POST" action="cart.php" class="promo-form">
                        <input type="hidden" name="csrf_token" 
                               value="<?php echo generateCSRFToken(); ?>">
                        <input type="text" name="promo_code" 
                               placeholder="Enter promo code" required>
                        <button type="submit" name="apply_promo" 
                                class="btn btn-secondary">Apply</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <style>
    .cart-page {
        background: #f8f9fa;
        min-height: 100vh;
        padding-bottom: 2rem;
    }

    .cart-container {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 0 2rem;
    }

    .cart-container h1 {
        font-size: 2rem;
        color: #000;
        margin-bottom: 2rem;
    }

    .empty-cart {
        text-align: center;
        padding: 4rem 2rem;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .empty-cart h2 {
        color: #000;
        margin-bottom: 1rem;
    }

    .empty-cart p {
        color: #666;
        margin-bottom: 2rem;
    }

    .cart-items {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }

    .cart-item {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 2rem;
        align-items: center;
        padding: 1.5rem;
        border-bottom: 1px solid #eee;
    }

    .cart-item:last-child {
        border-bottom: none;
    }

    .item-info h3 {
        font-size: 1.125rem;
        color: #000;
        margin: 0 0 0.5rem 0;
    }

    .item-type,
    .item-region {
        color: #666;
        font-size: 0.875rem;
        margin: 0;
    }

    .billing-select {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #eee;
        border-radius: 5px;
        font-size: 0.875rem;
    }

    .item-price {
        font-weight: 600;
        color: #000;
    }

    .cart-summary {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 1.5rem;
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

    .summary-row.total {
        font-weight: 600;
        font-size: 1.125rem;
        color: #000;
    }

    .summary-row.discount {
        color: #059669;
    }

    .cart-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .promo-code {
        margin-top: 2rem;
    }

    .promo-form {
        display: flex;
        gap: 1rem;
        max-width: 400px;
    }

    .promo-form input {
        flex: 1;
        padding: 0.75rem;
        border: 2px solid #eee;
        border-radius: 5px;
        font-size: 1rem;
    }

    @media (max-width: 768px) {
        .cart-container {
            padding: 1rem;
        }

        .cart-item {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .cart-actions {
            flex-direction: column;
        }

        .promo-form {
            flex-direction: column;
        }
    }
    </style>
</body>
</html>
