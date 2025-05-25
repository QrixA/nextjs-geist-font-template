<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Disable error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

try {
    // Get callback data
    $merchantCode = $_POST['merchantCode'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $merchantOrderId = $_POST['merchantOrderId'] ?? '';
    $productDetail = $_POST['productDetail'] ?? '';
    $additionalParam = $_POST['additionalParam'] ?? '';
    $paymentCode = $_POST['paymentCode'] ?? '';
    $resultCode = $_POST['resultCode'] ?? '';
    $merchantUserId = $_POST['merchantUserId'] ?? '';
    $reference = $_POST['reference'] ?? '';
    $signature = $_POST['signature'] ?? '';
    
    // Validate required parameters
    if (empty($merchantCode) || empty($amount) || empty($merchantOrderId) || empty($signature)) {
        throw new Exception('Missing required parameters');
    }
    
    // Validate merchant code
    if ($merchantCode !== DUITKU_MERCHANT_CODE) {
        throw new Exception('Invalid merchant code');
    }
    
    // Validate signature
    if (!validateDuitkuCallback($merchantCode, $amount, $merchantOrderId, $signature)) {
        throw new Exception('Invalid signature');
    }
    
    $db = Database::getInstance();
    
    // Get order details
    $order = $db->getRow(
        "SELECT * FROM orders WHERE id = ? AND status = 'pending'",
        [$merchantOrderId]
    );
    
    if (!$order) {
        throw new Exception('Order not found or already processed');
    }
    
    // Validate amount
    if ((float)$amount !== (float)$order['total_amount']) {
        throw new Exception('Invalid amount');
    }
    
    // Process based on result code
    if ($resultCode === '00') { // Payment success
        $db->beginTransaction();
        
        try {
            // Update order status
            $db->update('orders',
                [
                    'status' => 'paid',
                    'payment_method' => $paymentCode,
                    'payment_reference' => $reference,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = ?',
                [$merchantOrderId]
            );
            
            // Create transaction record
            $db->insert('transactions', [
                'user_id' => $order['user_id'],
                'invoice_id' => 'INV-' . date('Ymd') . '-' . $merchantOrderId,
                'amount' => $amount,
                'status' => 'paid',
                'payment_method' => $paymentCode,
                'reference' => $reference,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Get cart items from session (stored in order metadata)
            $cartItems = json_decode($order['metadata'] ?? '[]', true);
            
            // Create services
            foreach ($cartItems as $item) {
                createService(
                    $merchantOrderId,
                    $order['user_id'],
                    $item['product_id'],
                    $item['billing_cycle']
                );
            }
            
            // Log activity
            logActivity($order['user_id'], 'payment_success', [
                'order_id' => $merchantOrderId,
                'amount' => $amount,
                'payment_method' => $paymentCode
            ]);
            
            $db->commit();
            
            // Send success notification email
            $user = $db->getRow(
                "SELECT * FROM users WHERE id = ?",
                [$order['user_id']]
            );
            
            if ($user) {
                $emailBody = "Dear " . $user['full_name'] . ",\n\n" .
                            "Your payment of " . formatMoney($amount) . " has been successfully processed.\n" .
                            "Order ID: #" . $merchantOrderId . "\n" .
                            "Payment Reference: " . $reference . "\n\n" .
                            "You can view your services in your dashboard:\n" .
                            SITE_URL . "/services.php\n\n" .
                            "Thank you for choosing " . SITE_NAME . "!";
                
                sendEmail($user['email'], 'Payment Successful', $emailBody);
            }
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    elseif (in_array($resultCode, ['01', '02'])) { // Payment pending or failed
        $db->update('orders',
            [
                'status' => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'id = ?',
            [$merchantOrderId]
        );
        
        // Log activity
        logActivity($order['user_id'], 'payment_failed', [
            'order_id' => $merchantOrderId,
            'result_code' => $resultCode
        ]);
    }
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => true,
        'message' => 'Callback processed successfully'
    ]);
    
} catch (Exception $e) {
    // Log error
    logError('Duitku callback error: ' . $e->getMessage(), [
        'post_data' => $_POST,
        'trace' => $e->getTraceAsString()
    ]);
    
    // Return error response
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => false,
        'message' => 'Error processing callback: ' . $e->getMessage()
    ]);
}
