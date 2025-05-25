<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Require user to be logged in
requireLogin();

try {
    $db = Database::getInstance();
    
    // Get filter parameters
    $region = $_GET['region'] ?? '';
    $type = $_GET['type'] ?? '';
    $sort = $_GET['sort'] ?? 'price_asc';
    
    // Build query
    $sql = "SELECT * FROM products WHERE is_available = 1";
    $params = [];
    
    if ($region) {
        $sql .= " AND region = ?";
        $params[] = $region;
    }
    
    if ($type) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    
    // Add sorting
    switch ($sort) {
        case 'price_desc':
            $sql .= " ORDER BY monthly_price DESC";
            break;
        case 'name_asc':
            $sql .= " ORDER BY name ASC";
            break;
        case 'name_desc':
            $sql .= " ORDER BY name DESC";
            break;
        default: // price_asc
            $sql .= " ORDER BY monthly_price ASC";
    }
    
    // Get products
    $products = $db->getRows($sql, $params);
    
    // Get available regions and types for filters
    $regions = $db->getRows(
        "SELECT DISTINCT region FROM products WHERE is_available = 1"
    );
    
    $types = $db->getRows(
        "SELECT DISTINCT type FROM products WHERE is_available = 1"
    );
    
} catch (Exception $e) {
    logError($e->getMessage());
    setFlashMessage('error', 'An error occurred while loading products.');
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
    <title>Products - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="products-page">
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="index.php"><?php echo SITE_NAME; ?></a>
        </div>
        <div class="navbar-menu">
            <a href="product_catalog.php" class="active">Products</a>
            <a href="services.php">Services</a>
            <a href="support.php">Support</a>
        </div>
        <div class="navbar-end">
            <a href="cart.php" class="cart-link">
                Cart
                <?php if (!empty($_SESSION['cart'])): ?>
                    <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                <?php endif; ?>
            </a>
        </div>
    </nav>

    <div class="products-container">
        <div class="filters">
            <form method="GET" action="product_catalog.php" class="filter-form">
                <div class="filter-group">
                    <label for="region">Region</label>
                    <select name="region" id="region">
                        <option value="">All Regions</option>
                        <?php foreach ($regions as $r): ?>
                            <option value="<?php echo $r['region']; ?>" 
                                    <?php echo $region === $r['region'] ? 'selected' : ''; ?>>
                                <?php echo REGIONS[$r['region']]; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="type">Type</label>
                    <select name="type" id="type">
                        <option value="">All Types</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?php echo $t['type']; ?>"
                                    <?php echo $type === $t['type'] ? 'selected' : ''; ?>>
                                <?php echo PRODUCT_TYPES[$t['type']]; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="sort">Sort By</label>
                    <select name="sort" id="sort">
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>
                            Price: Low to High
                        </option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>
                            Price: High to Low
                        </option>
                        <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>
                            Name: A to Z
                        </option>
                        <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>
                            Name: Z to A
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </form>
        </div>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <h2>No products found</h2>
                <p>Try adjusting your filters or check back later for new products.</p>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-header">
                            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                            <span class="product-type">
                                <?php echo PRODUCT_TYPES[$product['type']]; ?>
                            </span>
                        </div>

                        <div class="product-specs">
                            <?php $specs = json_decode($product['specs'], true); ?>
                            <div class="spec-item">
                                <span class="spec-label">CPU</span>
                                <span class="spec-value"><?php echo $specs['cpu']; ?></span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">RAM</span>
                                <span class="spec-value"><?php echo $specs['ram']; ?></span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Storage</span>
                                <span class="spec-value"><?php echo $specs['storage']; ?></span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Bandwidth</span>
                                <span class="spec-value"><?php echo $specs['bandwidth']; ?></span>
                            </div>
                        </div>

                        <div class="product-location">
                            <span class="location-label">Region:</span>
                            <span class="location-value"><?php echo REGIONS[$product['region']]; ?></span>
                        </div>

                        <div class="product-pricing">
                            <div class="price-option">
                                <span class="price-label">Hourly</span>
                                <span class="price-value">
                                    <?php echo formatMoney($product['hourly_price']); ?>/hr
                                </span>
                            </div>
                            <div class="price-option">
                                <span class="price-label">Monthly</span>
                                <span class="price-value">
                                    <?php echo formatMoney($product['monthly_price']); ?>/mo
                                </span>
                            </div>
                            <div class="price-option">
                                <span class="price-label">Yearly</span>
                                <span class="price-value">
                                    <?php echo formatMoney($product['yearly_price']); ?>/yr
                                </span>
                            </div>
                        </div>

                        <div class="product-actions">
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                
                                <select name="billing_cycle" class="billing-select">
                                    <option value="hourly">Hourly</option>
                                    <option value="monthly" selected>Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                                
                                <button type="submit" name="add_to_cart" class="btn btn-primary btn-block">
                                    Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <style>
    .products-page {
        background: #f8f9fa;
        min-height: 100vh;
        padding-bottom: 2rem;
    }

    .products-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 2rem;
    }

    .filters {
        background: #fff;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }

    .filter-form {
        display: flex;
        gap: 1.5rem;
        align-items: flex-end;
    }

    .filter-group {
        flex: 1;
    }

    .filter-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #000;
    }

    .filter-group select {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #eee;
        border-radius: 5px;
        font-size: 1rem;
        background: #fff;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
    }

    .product-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .product-header {
        border-bottom: 1px solid #eee;
        padding-bottom: 1rem;
    }

    .product-header h2 {
        font-size: 1.25rem;
        color: #000;
        margin: 0;
    }

    .product-type {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        background: #f8f9fa;
        border-radius: 5px;
        font-size: 0.875rem;
        color: #666;
        margin-top: 0.5rem;
    }

    .product-specs {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .spec-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .spec-label {
        font-size: 0.875rem;
        color: #666;
    }

    .spec-value {
        font-weight: 500;
        color: #000;
    }

    .product-location {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }

    .location-label {
        color: #666;
    }

    .location-value {
        font-weight: 500;
        color: #000;
    }

    .product-pricing {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        padding: 1rem 0;
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
    }

    .price-option {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
    }

    .price-label {
        font-size: 0.875rem;
        color: #666;
    }

    .price-value {
        font-weight: 600;
        color: #000;
    }

    .product-actions {
        margin-top: auto;
    }

    .billing-select {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #eee;
        border-radius: 5px;
        font-size: 1rem;
        margin-bottom: 1rem;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .empty-state h2 {
        color: #000;
        margin-bottom: 1rem;
    }

    .empty-state p {
        color: #666;
    }

    @media (max-width: 768px) {
        .products-container {
            padding: 1rem;
        }

        .filter-form {
            flex-direction: column;
            gap: 1rem;
        }

        .products-grid {
            grid-template-columns: 1fr;
        }

        .product-pricing {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>
</html>
