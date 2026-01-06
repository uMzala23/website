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

$order = null;
$order_items = null;
$error = '';

// Track by Order ID and Email
if (isset($_POST['track_order'])) {
    $order_number = $conn->real_escape_string(trim($_POST['order_number']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    
    // Remove # if present
    $order_number = str_replace('#', '', $order_number);
    $order_id = (int)$order_number;
    
    $query = $conn->prepare("SELECT * FROM orders WHERE id = ? AND email = ?");
    $query->bind_param("is", $order_id, $email);
    $query->execute();
    $result = $query->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        $order_items = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id");
    } else {
        $error = "Order not found. Please check your order number and email.";
    }
}

// If customer is logged in, show their orders
$customer_orders = null;
if (isset($_SESSION['customer_logged_in'])) {
    $customer_email = $_SESSION['customer_email'];
    $customer_orders = $conn->query("SELECT * FROM orders WHERE email = '$customer_email' ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order - Amakha</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #000;
            color: #FFD700;
        }
        
        header {
            background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
            padding: 20px 0;
            border-bottom: 2px solid #FFD700;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .tracking-form {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 40px;
            max-width: 600px;
            margin: 0 auto 40px;
        }
        
        h2 {
            margin-bottom: 30px;
            font-size: 28px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input {
            width: 100%;
            padding: 12px;
            background: #000;
            border: 1px solid #FFD700;
            color: #FFD700;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .btn {
            width: 100%;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            padding: 15px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .error {
            background: #dc3545;
            color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .order-details {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 40px;
            margin-bottom: 40px;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #FFD700;
        }
        
        .order-number {
            font-size: 32px;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }
        
        .status-pending { background: #ffc107; color: #000; }
        .status-processing { background: #17a2b8; color: #fff; }
        .status-shipped { background: #007bff; color: #fff; }
        .status-delivered { background: #28a745; color: #fff; }
        .status-cancelled { background: #dc3545; color: #fff; }
        
        .tracking-timeline {
            margin: 40px 0;
            position: relative;
        }
        
        .timeline-item {
            display: flex;
            margin-bottom: 30px;
            position: relative;
        }
        
        .timeline-dot {
            width: 30px;
            height: 30px;
            background: #FFD700;
            border-radius: 50%;
            margin-right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #000;
            flex-shrink: 0;
        }
        
        .timeline-dot.inactive {
            background: #666;
        }
        
        .timeline-content {
            flex: 1;
        }
        
        .timeline-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .timeline-date {
            color: #ccc;
            font-size: 14px;
        }
        
        .order-items {
            background: #000;
            border: 1px solid #FFD700;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #333;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .info-group {
            background: #000;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #FFD700;
        }
        
        .info-label {
            color: #ccc;
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
        }
        
        .orders-list {
            margin-top: 40px;
        }
        
        .order-card {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #FFD700;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">AMAKHA</div>
            <a href="index.php" style="color: #FFD700; text-decoration: none;">← Back to Store</a>
        </div>
    </header>
    
    <div class="container">
        <?php if (!isset($_SESSION['customer_logged_in'])): ?>
            <div class="tracking-form">
                <h2>Track Your Order</h2>
                
                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Order Number</label>
                        <input type="text" name="order_number" placeholder="#000001" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="your@email.com" required>
                    </div>
                    
                    <button type="submit" name="track_order" class="btn">Track Order</button>
                </form>
                
                <div class="back-link">
                    <a href="customer_auth.php">Have an account? Login here</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($order): ?>
            <div class="order-details">
                <div class="order-header">
                    <div>
                        <div class="order-number">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></div>
                        <p style="color: #ccc;">Placed on <?= date('F d, Y', strtotime($order['created_at'])) ?></p>
                    </div>
                    <div class="status-badge status-<?= $order['status'] ?>">
                        <?= ucfirst($order['status']) ?>
                    </div>
                </div>
                
                <div class="tracking-timeline">
                    <h3>Order Status</h3>
                    <div class="timeline-item">
                        <div class="timeline-dot <?= in_array($order['status'], ['pending', 'processing', 'shipped', 'delivered']) ? '' : 'inactive' ?>">✓</div>
                        <div class="timeline-content">
                            <div class="timeline-title">Order Placed</div>
                            <div class="timeline-date"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></div>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-dot <?= in_array($order['status'], ['processing', 'shipped', 'delivered']) ? '' : 'inactive' ?>">✓</div>
                        <div class="timeline-content">
                            <div class="timeline-title">Processing</div>
                            <div class="timeline-date"><?= $order['status'] === 'processing' || $order['status'] === 'shipped' || $order['status'] === 'delivered' ? 'In progress' : 'Pending' ?></div>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-dot <?= in_array($order['status'], ['shipped', 'delivered']) ? '' : 'inactive' ?>">✓</div>
                        <div class="timeline-content">
                            <div class="timeline-title">Shipped</div>
                            <div class="timeline-date"><?= $order['status'] === 'shipped' || $order['status'] === 'delivered' ? 'On the way' : 'Waiting' ?></div>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-dot <?= $order['status'] === 'delivered' ? '' : 'inactive' ?>">✓</div>
                        <div class="timeline-content">
                            <div class="timeline-title">Delivered</div>
                            <div class="timeline-date"><?= $order['status'] === 'delivered' ? 'Completed' : 'Expected in 3-5 days' ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="order-info">
                    <div class="info-group">
                        <div class="info-label">Delivery Address</div>
                        <div class="info-value"><?= htmlspecialchars($order['address']) ?><br><?= htmlspecialchars($order['city']) ?> <?= htmlspecialchars($order['postal_code']) ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Contact</div>
                        <div class="info-value"><?= htmlspecialchars($order['phone']) ?><br><?= htmlspecialchars($order['email']) ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Total Amount</div>
                        <div class="info-value" style="font-size: 24px; font-weight: bold;">R<?= number_format($order['total_amount'], 2) ?></div>
                    </div>
                </div>
                
                <h3>Order Items</h3>
                <div class="order-items">
                    <?php while ($item = $order_items->fetch_assoc()): ?>
                        <div class="order-item">
                            <div>
                                <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                <p style="color: #ccc; font-size: 14px;">Quantity: <?= $item['quantity'] ?></p>
                            </div>
                            <div>R<?= number_format($item['subtotal'], 2) ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($customer_orders && $customer_orders->num_rows > 0): ?>
            <div class="orders-list">
                <h2>Your Orders</h2>
                <?php while ($customer_order = $customer_orders->fetch_assoc()): ?>
                    <div class="order-card">
                        <div>
                            <div style="font-size: 20px; font-weight: bold;">Order #<?= str_pad($customer_order['id'], 6, '0', STR_PAD_LEFT) ?></div>
                            <p style="color: #ccc;">Placed on <?= date('M d, Y', strtotime($customer_order['created_at'])) ?></p>
                            <p style="margin-top: 5px;">Total: R<?= number_format($customer_order['total_amount'], 2) ?></p>
                        </div>
                        <div>
                            <div class="status-badge status-<?= $customer_order['status'] ?>" style="margin-bottom: 10px;">
                                <?= ucfirst($customer_order['status']) ?>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="order_number" value="<?= $customer_order['id'] ?>">
                                <input type="hidden" name="email" value="<?= $customer_order['email'] ?>">
                                <button type="submit" name="track_order" class="btn" style="width: auto; padding: 10px 20px; font-size: 14px;">Track Order</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>