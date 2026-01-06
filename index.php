<?php
// config.php - Database Configuration
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'amakha_store'
];

// Connect to database
try {
    $conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

session_start();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Fetch products from database
$products_query = "SELECT p.*, COALESCE(AVG(pr.rating), 0) as avg_rating, COUNT(pr.id) as review_count 
                   FROM products p 
                   LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
                   WHERE p.status = 'active' 
                   GROUP BY p.id
                   ORDER BY p.id";
$products_result = $conn->query($products_query);
$products = [];

if ($products_result && $products_result->num_rows > 0) {
    while ($row = $products_result->fetch_assoc()) {
        $products[$row['id']] = $row;
    }
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    if (isset($products[$product_id]) && $quantity > 0) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        $_SESSION['message'] = "Product added to cart successfully!";
    }
}

// Handle remove from cart
if (isset($_POST['remove_from_cart'])) {
    $product_id = (int)$_POST['product_id'];
    unset($_SESSION['cart'][$product_id]);
    $_SESSION['message'] = "Product removed from cart.";
}

// Handle checkout
$checkout_mode = isset($_POST['checkout']) || isset($_GET['checkout']);

// Calculate cart total
$cart_total = 0;
$cart_items = [];
foreach ($_SESSION['cart'] as $product_id => $quantity) {
    if (isset($products[$product_id])) {
        $cart_items[] = [
            'product' => $products[$product_id],
            'quantity' => $quantity,
            'subtotal' => $products[$product_id]['price'] * $quantity
        ];
        $cart_total += $products[$product_id]['price'] * $quantity;
    }
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amakha - Luxury Car Perfumes, Colognes & Apparel</title>
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
        }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
            padding: 20px 0;
            border-bottom: 2px solid #FFD700;
            position: relative;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #FFD700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .user-info {
            color: #FFD700;
            font-size: 14px;
        }
        
        .header-btn {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        /* Admin Button with Animated Stars */
        .admin-button {
            position: relative;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            overflow: hidden;
        }
        
        .star {
            position: absolute;
            color: #fff;
            font-size: 20px;
            animation: twinkle 1.5s infinite;
        }
        
        .star:nth-child(1) {
            top: 5px;
            left: 10px;
            animation-delay: 0s;
        }
        
        .star:nth-child(2) {
            top: 5px;
            left: 50%;
            transform: translateX(-50%);
            animation-delay: 0.5s;
        }
        
        .star:nth-child(3) {
            top: 5px;
            right: 10px;
            animation-delay: 1s;
        }
        
        @keyframes twinkle {
            0%, 100% { opacity: 1; transform: scale(1) rotate(0deg); }
            50% { opacity: 0.5; transform: scale(1.2) rotate(180deg); }
        }
        
        /* Navigation */
        nav {
            background: #1a1a1a;
            border-bottom: 1px solid #FFD700;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        nav ul {
            list-style: none;
            display: flex;
            gap: 30px;
            padding: 15px 0;
            flex-wrap: wrap;
        }
        
        nav a {
            color: #FFD700;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            padding: 5px 10px;
        }
        
        nav a:hover, nav a.active {
            background: #FFD700;
            color: #000;
            border-radius: 3px;
        }
        
        /* Cart Badge */
        .cart-badge {
            background: #FFD700;
            color: #000;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        /* Alert Messages */
        .alert {
            max-width: 1200px;
            margin: 20px auto;
            padding: 15px 20px;
            border-radius: 5px;
            text-align: center;
        }
        
        .alert-success {
            background: #155724;
            border: 1px solid #FFD700;
            color: #FFD700;
        }
        
        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #1a1a1a 0%, #000 100%);
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 60px 40px;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        
        .hero p {
            font-size: 20px;
            color: #ccc;
            margin-bottom: 30px;
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
            transition: transform 0.3s;
        }
        
        .btn:hover {
            transform: scale(1.05);
        }
        
        /* Product Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .product-card {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3);
        }
        
        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
            background: #000;
        }
        
        .product-rating {
            color: #FFD700;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .product-card h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }
        
        .product-card p {
            color: #ccc;
            margin-bottom: 15px;
        }
        
        .price {
            font-size: 28px;
            font-weight: bold;
            color: #FFD700;
            margin-bottom: 20px;
        }
        
        .product-form {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }
        
        .quantity-input {
            width: 60px;
            padding: 8px;
            background: #000;
            border: 1px solid #FFD700;
            color: #FFD700;
            border-radius: 3px;
            text-align: center;
        }
        
        .view-reviews {
            display: block;
            margin-top: 10px;
            color: #FFD700;
            text-decoration: none;
            font-size: 14px;
        }
        
        /* Cart Styles */
        .cart-container {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 30px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #000;
            border: 1px solid #FFD700;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .cart-item-info {
            flex: 1;
        }
        
        .cart-total {
            font-size: 32px;
            text-align: right;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #FFD700;
        }
        
        /* Checkout Form */
        .checkout-form {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            background: #000;
            border: 1px solid #FFD700;
            color: #FFD700;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        /* Footer */
        footer {
            background: #1a1a1a;
            border-top: 2px solid #FFD700;
            padding: 30px 0;
            margin-top: 60px;
            text-align: center;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #ccc;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">AMAKHA</div>
            <div class="header-actions">
                <?php if (isset($_SESSION['customer_logged_in'])): ?>
                    <span class="user-info">Welcome, <?= htmlspecialchars($_SESSION['customer_name']) ?>!</span>
                    <a href="order_tracking.php" class="header-btn">My Orders</a>
                    <a href="?logout" class="header-btn">Logout</a>
                <?php else: ?>
                    <a href="customer_auth.php" class="header-btn">Login / Register</a>
                <?php endif; ?>
                <a href="admin_dashboard.php" class="admin-button">
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star">★</span>
                    Admin Panel
                </a>
            </div>
        </div>
    </header>
    
    <nav>
        <div class="nav-container">
            <ul>
                <li><a href="?page=home" class="<?= $page === 'home' ? 'active' : '' ?>">Home</a></li>
                <li><a href="?page=perfumes" class="<?= $page === 'perfumes' ? 'active' : '' ?>">Car Perfumes</a></li>
                <li><a href="?page=colognes" class="<?= $page === 'colognes' ? 'active' : '' ?>">Colognes</a></li>
                <li><a href="?page=clothing" class="<?= $page === 'clothing' ? 'active' : '' ?>">Clothing</a></li>
                <li><a href="?page=cart" class="<?= $page === 'cart' ? 'active' : '' ?>">
                    Shopping Cart
                    <?php if (count($_SESSION['cart']) > 0): ?>
                        <span class="cart-badge"><?= array_sum($_SESSION['cart']) ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="order_tracking.php">Track Order</a></li>
            </ul>
        </div>
    </nav>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['message']) ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <div class="container">
        <?php if ($page === 'home'): ?>
            <div class="hero">
                <h1>Welcome to Amakha</h1>
                <p>Premium Car Perfumes, Luxury Colognes & Exclusive Branded Apparel</p>
                <a href="?page=perfumes" class="btn">Shop Now</a>
            </div>
            
            <h2 style="text-align: center; margin-bottom: 30px; font-size: 36px;">Featured Products</h2>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                        
                        <?php if ($product['review_count'] > 0): ?>
                            <div class="product-rating">
                                <?php 
                                $rating = round($product['avg_rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '★' : '☆';
                                }
                                ?>
                                <span style="color: #ccc; font-size: 14px;">(<?= $product['review_count'] ?>)</span>
                            </div>
                        <?php endif; ?>
                        
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p><?= htmlspecialchars($product['description']) ?></p>
                        <div class="price">R<?= number_format($product['price'], 2) ?></div>
                        
                        <form method="POST" class="product-form">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" class="quantity-input">
                            <button type="submit" name="add_to_cart" class="btn">Add to Cart</button>
                        </form>
                        
                        <a href="product_reviews.php?product_id=<?= $product['id'] ?>" class="view-reviews">
                            <?= $product['review_count'] > 0 ? 'View Reviews' : 'Be the first to review' ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php elseif ($page === 'perfumes' || $page === 'colognes' || $page === 'clothing'): ?>
            <h2 style="text-align: center; margin-bottom: 30px; font-size: 36px;">
                <?= ucfirst($page === 'perfumes' ? 'Car Perfumes' : $page) ?>
            </h2>
            <div class="products-grid">
                <?php 
                $category = $page === 'perfumes' ? 'perfume' : ($page === 'colognes' ? 'cologne' : 'clothing');
                foreach ($products as $product): 
                    if ($product['category'] === $category):
                ?>
                    <div class="product-card">
                        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                        
                        <?php if ($product['review_count'] > 0): ?>
                            <div class="product-rating">
                                <?php 
                                $rating = round($product['avg_rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '★' : '☆';
                                }
                                ?>
                                <span style="color: #ccc; font-size: 14px;">(<?= $product['review_count'] ?>)</span>
                            </div>
                        <?php endif; ?>
                        
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p><?= htmlspecialchars($product['description']) ?></p>
                        <div class="price">R<?= number_format($product['price'], 2) ?></div>
                        
                        <form method="POST" class="product-form">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" class="quantity-input">
                            <button type="submit" name="add_to_cart" class="btn">Add to Cart</button>
                        </form>
                        
                        <a href="product_reviews.php?product_id=<?= $product['id'] ?>" class="view-reviews">
                            <?= $product['review_count'] > 0 ? 'View Reviews' : 'Be the first to review' ?>
                        </a>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
            
        <?php elseif ($page === 'cart'): ?>
            <h2 style="text-align: center; margin-bottom: 30px; font-size: 36px;">Shopping Cart</h2>
            
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <h3 style="color: #FFD700;">Your cart is empty</h3>
                    <p>Start shopping to add items to your cart</p>
                    <a href="?page=home" class="btn" style="margin-top: 20px;">Continue Shopping</a>
                </div>
            <?php elseif ($checkout_mode): ?>
                <div class="checkout-form">
                    <h3 style="margin-bottom: 30px; font-size: 28px;">Delivery Information</h3>
                    <form method="POST" action="process_order.php">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" value="<?= isset($_SESSION['customer_name']) ? htmlspecialchars($_SESSION['customer_name']) : '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" value="<?= isset($_SESSION['customer_email']) ? htmlspecialchars($_SESSION['customer_email']) : '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="phone" value="<?= isset($_SESSION['customer_phone']) ? htmlspecialchars($_SESSION['customer_phone']) : '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Delivery Address *</label>
                            <textarea name="address" required placeholder="Street Address, Building/House No., etc."><?= isset($_SESSION['customer_address']) ? htmlspecialchars($_SESSION['customer_address']) : '' ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>City *</label>
                            <input type="text" name="city" value="<?= isset($_SESSION['customer_city']) ? htmlspecialchars($_SESSION['customer_city']) : '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Postal Code *</label>
                            <input type="text" name="postal_code" value="<?= isset($_SESSION['customer_postal_code']) ? htmlspecialchars($_SESSION['customer_postal_code']) : '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Additional Notes</label>
                            <textarea name="notes" placeholder="Special delivery instructions, etc."></textarea>
                        </div>
                        
                        <div style="background: #000; border: 1px solid #FFD700; border-radius: 5px; padding: 20px; margin: 20px 0;">
                            <h4 style="margin-bottom: 15px;">Order Summary</h4>
                            <?php foreach ($cart_items as $item): ?>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #ccc;">
                                    <span><?= htmlspecialchars($item['product']['name']) ?> × <?= $item['quantity'] ?></span>
                                    <span>R<?= number_format($item['subtotal'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="cart-total">
                            Total: R<?= number_format($cart_total, 2) ?>
                        </div>
                        <button type="submit" name="place_order" class="btn" style="width: 100%; margin-top: 20px;">Continue to Payment</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="cart-container">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-info">
                                <h3><?= htmlspecialchars($item['product']['name']) ?></h3>
                                <p>Quantity: <?= $item['quantity'] ?> × R<?= number_format($item['product']['price'], 2) ?></p>
                            </div>
                            <div style="text-align: right;">
                                <div class="price">R<?= number_format($item['subtotal'], 2) ?></div>
                                <form method="POST" style="margin-top: 10px;">
                                    <input type="hidden" name="product_id" value="<?= $item['product']['id'] ?>">
                                    <button type="submit" name="remove_from_cart" class="btn" style="background: #dc3545; padding: 8px 20px;">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="cart-total">
                        Total: R<?= number_format($cart_total, 2) ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <form method="POST">
                            <button type="submit" name="checkout" class="btn" style="font-size: 20px; padding: 15px 60px;">Proceed to Checkout</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <footer>
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <p style="font-size: 18px; margin-bottom: 10px;">&copy; <?= date('Y') ?> Amakha. All Rights Reserved.Since 2020</p>
            <p style="color: #ccc;">Premium Car Perfumes, Colognes & Branded Apparel</p>
        </div>
    </footer>
</body>
</html>