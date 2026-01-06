<?php
session_start();

// Database Configuration
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'amakha_store'
];

$conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);
$conn->set_charset("utf8mb4");

// Get payment details
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$payment_method = isset($_GET['method']) ? $_GET['method'] : '';
$payment_id = isset($_GET['payment_id']) ? $_GET['payment_id'] : '';

if ($order_id === 0) {
    header("Location: index.php");
    exit();
}

// Get order details
$order = $conn->query("SELECT * FROM orders WHERE id = $order_id")->fetch_assoc();
$order_items = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id");

if (!$order) {
    header("Location: index.php");
    exit();
}

// Record payment transaction
$insert_payment = $conn->prepare("INSERT INTO payment_transactions (order_id, payment_method, transaction_id, amount, currency, status, created_at) VALUES (?, ?, ?, ?, 'PHP', 'completed', NOW())");
$insert_payment->bind_param("issd", $order_id, $payment_method, $payment_id, $order['total_amount']);
$insert_payment->execute();

// Update order status to processing
$conn->query("UPDATE orders SET status = 'processing' WHERE id = $order_id");

// Send confirmation email
sendOrderConfirmationEmail($order_id, $order['email'], $order['full_name'], $order_items, $order['total_amount'], $payment_method);

// Clear cart and order session
unset($_SESSION['cart']);
$_SESSION['cart'] = [];
unset($_SESSION['pending_order_id']);

// Set success message
$_SESSION['order_id'] = $order_id;
$_SESSION['order_total'] = $order['total_amount'];

// Function to send order confirmation email
function sendOrderConfirmationEmail($order_id, $email, $name, $items_result, $total, $payment_method) {
    $subject = "Order Confirmation - Amakha Store #" . str_pad($order_id, 6, '0', STR_PAD_LEFT);
    
    $payment_text = '';
    switch($payment_method) {
        case 'paypal':
            $payment_text = 'PayPal';
            break;
        case 'stripe':
            $payment_text = 'Credit/Debit Card';
            break;
        case 'cod':
            $payment_text = 'Cash on Delivery';
            break;
        default:
            $payment_text = ucfirst($payment_method);
    }
    
    $items_html = '';
    while ($item = $items_result->fetch_assoc()) {
        $items_html .= "
                <div class='item'>
                    <span>" . htmlspecialchars($item['product_name']) . " × " . $item['quantity'] . "</span>
                    <span>₱" . number_format($item['subtotal'], 2) . "</span>
                </div>";
    }
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
            .email-container { max-width: 600px; margin: 0 auto; background: #fff; border: 2px solid #FFD700; border-radius: 10px; padding: 30px; }
            .header { text-align: center; color: #FFD700; background: #000; padding: 20px; border-radius: 5px; margin-bottom: 30px; }
            .header h1 { margin: 0; font-size: 32px; }
            .order-details { background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #ddd; }
            .total { font-size: 24px; font-weight: bold; text-align: right; margin-top: 20px; padding-top: 20px; border-top: 2px solid #FFD700; }
            .footer { text-align: center; color: #666; margin-top: 30px; font-size: 14px; }
            .payment-info { background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>AMAKHA</h1>
                <p>Order Confirmation</p>
            </div>
            
            <p>Dear " . htmlspecialchars($name) . ",</p>
            <p>Thank you for your order! We've received your payment and will process your order soon.</p>
            
            <div class='payment-info'>
                <strong>Payment Method:</strong> $payment_text<br>
                <strong>Payment Status:</strong> Completed ✓
            </div>
            
            <div class='order-details'>
                <h3>Order #" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . "</h3>
                <p><strong>Date:</strong> " . date('F d, Y') . "</p>
                
                <h4>Items Ordered:</h4>
                $items_html
                
                <div class='total'>Total: ₱" . number_format($total, 2) . "</div>
            </div>
            
            <p>We will send you another email with tracking information once your order has been shipped.</p>
            <p><strong>Track your order:</strong> <a href='http://yourdomain.com/order_tracking.php'>Click here</a></p>
            <p>If you have any questions, please don't hesitate to contact us.</p>
            
            <div class='footer'>
                <p>&copy; " . date('Y') . " Amakha. All Rights Reserved.</p>
                <p>Premium Car Perfumes, Colognes & Branded Apparel</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Amakha Store <noreply@amakha.com>" . "\r\n";
    
    // Send email
    @mail($email, $subject, $message, $headers);
    
    // Also send notification to admin
    $admin_email = "admin@amakha.com";
    $admin_subject = "New Order Received - #" . str_pad($order_id, 6, '0', STR_PAD_LEFT);
    @mail($admin_email, $admin_subject, $message, $headers);
}

$conn->close();

// Redirect to order success page
header("Location: order_success.php");
exit();
?>