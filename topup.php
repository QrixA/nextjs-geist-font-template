<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Require user to be logged in
requireLogin();

$error = '';
$success = '';

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
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }
        
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        
        if (!$amount || $amount < 10000) { // Minimum IDR 10,000
            throw new Exception('Minimum top-up amount is ' . formatMoney(10000));
        }
        
        if ($amount > 10000000) { // Maximum IDR 10,000,000
            throw new Exception('Maximum top-up amount is ' . formatMoney(10000000));
        }
        
        // Create pending transaction
        $transactionId = $db->insert('transactions', [
            'user_id' => $userId,
            'invoice_id' => 'TOP-' . date('Ymd') . '-' . uniqid(),
            'amount' => $amount,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Initialize payment
        $paymentResponse = initializeDuitkuPayment($amount, $transactionId, $user);
        
        if ($paymentResponse && isset($paymentResponse['paymentUrl'])) {
            header('Location: ' . $paymentResponse['paymentUrl']);
            exit();
        } else {
            throw new Exception('Failed to initialize payment');
        }
    }
    
    // Get recent transactions
    $recentTransactions = $db->getRows(
        "SELECT * FROM transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10",
        [$userId]
    );
    
} catch (Exception $e) {
    logError($e->getMessage());
    $error = $e->getMessage();
}

// Load language file
$lang = loadLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['language']) ? $_SESSION['language'] : DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Up Balance - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body class="topup-page">
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
            </div>
        </div>
    </nav>

    <div class="topup-container">
        <div class="topup-grid">
            <div class="topup-form-section">
                <h1>Top Up Balance</h1>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="balance-card">
                    <div class="balance-label">Current Balance</div>
                    <div class="balance-amount">
                        <?php echo formatMoney($user['account_balance']); ?>
                    </div>
                </div>

                <form method="POST" action="topup.php" class="topup-form">
                    <input type="hidden" name="csrf_token" 
                           value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="amount">Top Up Amount</label>
                        <div class="amount-input">
                            <span class="currency">IDR</span>
                            <input type="number" id="amount" name="amount" 
                                   min="10000" max="10000000" step="10000" 
                                   value="100000" required>
                        </div>
                        <small class="form-text">
                            Minimum: IDR 10,000 | Maximum: IDR 10,000,000
                        </small>
                    </div>

                    <div class="quick-amounts">
                        <button type="button" class="amount-btn" data-amount="50000">
                            IDR 50,000
                        </button>
                        <button type="button" class="amount-btn" data-amount="100000">
                            IDR 100,000
                        </button>
                        <button type="button" class="amount-btn" data-amount="500000">
                            IDR 500,000
                        </button>
                        <button type="button" class="amount-btn" data-amount="1000000">
                            IDR 1,000,000
                        </button>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Proceed to Payment
                    </button>
                </form>
            </div>

            <div class="transactions-section">
                <h2>Recent Transactions</h2>
                
                <?php if (empty($recentTransactions)): ?>
                    <div class="empty-state">
                        <p>No recent transactions</p>
                    </div>
                <?php else: ?>
                    <div class="transactions-list">
                        <?php foreach ($recentTransactions as $transaction): ?>
                            <div class="transaction-item">
                                <div class="transaction-info">
                                    <div class="transaction-id">
                                        #<?php echo $transaction['invoice_id']; ?>
                                    </div>
                                    <div class="transaction-date">
                                        <?php echo formatDate($transaction['created_at']); ?>
                                    </div>
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
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const amountInput = document.getElementById('amount');
        const quickAmountButtons = document.querySelectorAll('.amount-btn');
        
        quickAmountButtons.forEach(button => {
            button.addEventListener('click', function() {
                const amount = this.dataset.amount;
                amountInput.value = amount;
                
                // Remove active class from all buttons
                quickAmountButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
            });
        });
    });
    </script>

    <style>
    .topup-page {
        background: #f8f9fa;
        min-height: 100vh;
        padding-bottom: 2rem;
    }

    .topup-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 2rem;
    }

    .topup-grid {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 2rem;
    }

    .topup-form-section {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 2rem;
    }

    .topup-form-section h1 {
        font-size: 1.5rem;
        color: #000;
        margin-bottom: 2rem;
    }

    .balance-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        text-align: center;
    }

    .balance-label {
        color: #666;
        margin-bottom: 0.5rem;
    }

    .balance-amount {
        font-size: 2rem;
        font-weight: 700;
        color: #000;
    }

    .topup-form .form-group {
        margin-bottom: 1.5rem;
    }

    .amount-input {
        display: flex;
        align-items: center;
        border: 2px solid #eee;
        border-radius: 5px;
        overflow: hidden;
    }

    .currency {
        background: #f8f9fa;
        padding: 0.75rem 1rem;
        color: #666;
        border-right: 2px solid #eee;
    }

    .amount-input input {
        flex: 1;
        border: none;
        padding: 0.75rem;
        font-size: 1rem;
    }

    .amount-input input:focus {
        outline: none;
    }

    .form-text {
        display: block;
        margin-top: 0.5rem;
        color: #666;
        font-size: 0.875rem;
    }

    .quick-amounts {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .amount-btn {
        background: #fff;
        border: 2px solid #eee;
        border-radius: 5px;
        padding: 0.75rem;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .amount-btn:hover,
    .amount-btn.active {
        background: #000;
        border-color: #000;
        color: #fff;
    }

    .transactions-section {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 2rem;
    }

    .transactions-section h2 {
        font-size: 1.25rem;
        color: #000;
        margin-bottom: 1.5rem;
    }

    .transactions-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .transaction-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border: 1px solid #eee;
        border-radius: 5px;
    }

    .transaction-info {
        flex: 1;
    }

    .transaction-id {
        font-weight: 500;
        color: #000;
    }

    .transaction-date {
        font-size: 0.875rem;
        color: #666;
    }

    .transaction-amount {
        font-weight: 600;
        color: #000;
        margin: 0 2rem;
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

    .status-badge.status-pending {
        background: #fef9c3;
        color: #ca8a04;
    }

    .status-badge.status-failed {
        background: #fee2e2;
        color: #dc2626;
    }

    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #666;
    }

    @media (max-width: 768px) {
        .topup-container {
            padding: 1rem;
        }

        .topup-grid {
            grid-template-columns: 1fr;
        }

        .quick-amounts {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>
</html>
