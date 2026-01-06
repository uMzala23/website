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

// Payment Gateway Configuration
$payment_config = [
    'paypal' => [
        'client_id' => 'YOUR_PAYPAL_CLIENT_ID', // Get from https://developer.paypal.com
        'client_secret' => 'YOUR_PAYPAL_CLIENT_SECRET',
        'mode' => 'sandbox', // 'sandbox' or 'live'
    ],
    'stripe' => [
        'publishable_key' => 'YOUR_STRIPE_PUBLISHABLE_KEY', // Get from https://dashboard.stripe.com
        'secret_key' => 'YOUR_STRIPE_SECRET_KEY',
    ]
];

// Get order details from session
if (!isset($_SESSION['pending_order_id'])) {
    header("Location: index.php");
    exit();
}

$order_id = $_SESSION['pending_order_id'];
$order = $conn->query("SELECT * FROM orders WHERE id = $order_id")->fetch_assoc();
$order_items = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id");

$payment_method = isset($_GET['method']) ? $_GET['method'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Amakha</title>
    
    <!-- PayPal SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?= $payment_config['paypal']['client_id'] ?>&currency=PHP"></script>
    
    <!-- Stripe SDK -->
    <script src="https://js.stripe.com/v3/"></script>
    
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
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .payment-container {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 40px;
        }
        
        h2 {
            margin-bottom: 30px;
            font-size: 28px;
        }
        
        .order-summary {
            background: #000;
            border: 1px solid #FFD700;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #ccc;
        }
        
        .order-total {
            font-size: 24px;
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #FFD700;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .payment-option {
            background: #000;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #FFD700;
        }
        
        .payment-option:hover, .payment-option.active {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            transform: scale(1.05);
        }
        
        .payment-option img {
            width: 80px;
            height: 50px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        
        #paypal-button-container, #stripe-card-element {
            margin-top: 20px;
            min-height: 150px;
        }
        
        .btn {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            padding: 15px 40px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        
        .alert {
            background: #155724;
            border: 1px solid #FFD700;
            color: #FFD700;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error {
            background: #dc3545;
            color: #fff;
        }
        
        #card-errors {
            color: #dc3545;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">AMAKHA</div>
        </div>
        
        <div class="payment-container">
            <h2>Complete Your Payment</h2>
            
            <div class="order-summary">
                <h3>Order Summary</h3>
                <?php while ($item = $order_items->fetch_assoc()): ?>
                    <div class="order-item">
                        <span><?= htmlspecialchars($item['product_name']) ?> × <?= $item['quantity'] ?></span>
                        <span>R<?= number_format($item['subtotal'], 2) ?></span>
                    </div>
                <?php endwhile; ?>
                
                <div class="order-total">
                    Total: R<?= number_format($order['total_amount'], 2) ?>
                </div>
            </div>
            
            <?php if (empty($payment_method)): ?>
                <h3>Select Payment Method</h3>
                <div class="payment-methods">
                    <a href="?method=paypal" class="payment-option">
                        <svg width="80" height="50" viewBox="0 0 100 32"><text x="10" y="20" fill="#FFD700" font-size="16" font-weight="bold">PayPal</text></svg>
                        <p>Pay with PayPal</p>
                    </a>
                    <a href="?method=stripe" class="payment-option">
                        <svg width="80" height="50" viewBox="0 0 100 32"><text x="10" y="20" fill="#FFD700" font-size="16" font-weight="bold">Stripe</text></svg>
                        <p>Pay with Card</p>
                    </a>
                    <a href="?method=cod" class="payment-option">
                        <svg width="80" height="50" viewBox="0 0 100 32"><text x="15" y="20" fill="#FFD700" font-size="16" font-weight="bold">COD</text></svg>
                        <p>Cash</p>
                    </a>
                </div>
            <?php elseif ($payment_method === 'paypal'): ?>
                <h3>PayPal Payment</h3>
                <div id="paypal-button-container"></div>
                
                <script>
                    paypal.Buttons({
                        createOrder: function(data, actions) {
                            return actions.order.create({
                                purchase_units: [{
                                    amount: {
                                        value: '<?= number_format($order['total_amount'], 2, '.', '') ?>',
                                        currency_code: 'PHP'
                                    },
                                    description: 'Amakha Order #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?>'
                                }]
                            });
                        },
                        onApprove: function(data, actions) {
                            return actions.order.capture().then(function(details) {
                                // Payment successful
                                window.location.href = 'payment_success.php?order_id=<?= $order_id ?>&payment_id=' + details.id + '&method=paypal';
                            });
                        },
                        onError: function(err) {
                            alert('Payment failed. Please try again.');
                            console.error(err);
                        }
                    }).render('#paypal-button-container');
                </script>
                
            <?php elseif ($payment_method === 'stripe'): ?>
                <h3>Credit/Debit Card Payment</h3>
                <form id="payment-form">
                    <div id="stripe-card-element" style="background: #000; padding: 15px; border: 1px solid #FFD700; border-radius: 5px;"></div>
                    <div id="card-errors" role="alert"></div>
                    <button type="submit" class="btn">Pay R<?= number_format($order['total_amount'], 2) ?></button>
                </form>
                
                <script>
                    const stripe = Stripe('<?= $payment_config['stripe']['publishable_key'] ?>');
                    const elements = stripe.elements();
                    
                    const cardElement = elements.create('card', {
                        style: {
                            base: {
                                color: '#FFD700',
                                fontSize: '16px',
                                '::placeholder': {
                                    color: '#ccc'
                                }
                            },
                            invalid: {
                                color: '#dc3545'
                            }
                        }
                    });
                    
                    cardElement.mount('#stripe-card-element');
                    
                    cardElement.on('change', function(event) {
                        const displayError = document.getElementById('card-errors');
                        if (event.error) {
                            displayError.textContent = event.error.message;
                        } else {
                            displayError.textContent = '';
                        }
                    });
                    
                    const form = document.getElementById('payment-form');
                    form.addEventListener('submit', async function(event) {
                        event.preventDefault();
                        
                        const {token, error} = await stripe.createToken(cardElement);
                        
                        if (error) {
                            document.getElementById('card-errors').textContent = error.message;
                        } else {
                            // Send token to server
                            window.location.href = 'payment_success.php?order_id=<?= $order_id ?>&payment_id=' + token.id + '&method=stripe';
                        }
                    });
                </script>
                
            <?php elseif ($payment_method === 'cod'): ?>
                <div class="alert">
                    <h3>Cash on Delivery Selected</h3>
                    <p>You will pay when your order is delivered.</p>
                </div>
                
                <form action="payment_success.php" method="GET">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    <input type="hidden" name="method" value="cod">
                    <button type="submit" class="btn">Confirm Order</button>
                </form>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="payment_gateway.php" style="color: #FFD700;">← Change Payment Method</a>
            </div>
        </div>
    </div>
</body>
</html>