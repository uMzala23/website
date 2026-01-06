<?php
session_start();

// Check if order was completed
if (!isset($_SESSION['order_id'])) {
    header("Location: index.php");
    exit();
}

$order_id = $_SESSION['order_id'];
$order_total = $_SESSION['order_total'];

// Clear session variables
unset($_SESSION['order_id']);
unset($_SESSION['order_total']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Amakha</title>
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
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        header {
            background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
            padding: 20px 0;
            border-bottom: 2px solid #ffaa00ff;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            text-align: center;
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #FFD700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .container {
            max-width: 800px;
            margin: 60px auto;
            padding: 0 20px;
            flex: 1;
        }
        
        .order-success {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 60px 40px;
            text-align: center;
        }
        
        .success-icon {
            font-size: 80px;
            color: #FFD700;
            margin-bottom: 20px;
            animation: scaleIn 0.5s ease-out;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        h1 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        
        .order-number {
            font-size: 24px;
            color: #ccc;
            margin-bottom: 30px;
        }
        
        .order-total {
            font-size: 48px;
            font-weight: bold;
            margin: 30px 0;
        }
        
        .message {
            color: #ccc;
            font-size: 18px;
            line-height: 1.8;
            margin: 20px 0;
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
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            transition: transform 0.3s;
        }
        
        .btn:hover {
            transform: scale(1.05);
        }
        
        .info-box {
            background: #000;
            border: 1px solid #FFD700;
            border-radius: 5px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }
        
        .info-box h3 {
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .info-box ul {
            list-style: none;
            color: #ccc;
            line-height: 2;
        }
        
        .info-box li:before {
            content: "✓ ";
            color: #FFD700;
            font-weight: bold;
            margin-right: 10px;
        }
        
        footer {
            background: #1a1a1a;
            border-top: 2px solid #FFD700;
            padding: 30px 0;
            margin-top: auto;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">AMAKHA</div>
        </div>
    </header>
    
    <div class="container">
        <div class="order-success">
            <div class="success-icon">✓</div>
            <h1>Order Confirmed!</h1>
            <p class="order-number">Order Number: #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></p>
            
            <div class="order-total">R<?= number_format($order_total, 2) ?></div>
            
            <p class="message">
                Thank you for your purchase! We've received your order and will begin processing it shortly.
            </p>
            
            <div class="info-box">
                <h3>What happens next?</h3>
                <ul>
                    <li>You'll receive an order confirmation email shortly</li>
                    <li>We'll prepare your items for shipping</li>
                    <li>You'll get a tracking number once shipped</li>
                    <li>Expected delivery: 3-5 business days</li>
                </ul>
            </div>
            
            <p class="message">
                A confirmation email has been sent to your email address with your order details.
                If you don't see it, please check your spam folder.
            </p>
            
            <div style="margin-top: 40px;">
                <a href="index.php" class="btn">Continue Shopping</a>
                <a href="index.php?page=admin" class="btn">Track Order</a>
            </div>
        </div>
    </div>
    
    <footer>
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <p style="font-size: 18px; margin-bottom: 10px;">&copy; <?= date('Y') ?> Amakha. The Smell of Quality in a Bottle.</p>
            <p style="color: #d5cf21ff;">Premium Car Perfumes, Colognes & Branded Apparel</p>
        </div>
    </footer>
</body>
</html>