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

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Get product details
$product = $conn->query("SELECT * FROM products WHERE id = $product_id")->fetch_assoc();
if (!$product) {
    die("Product not found");
}

// Handle Review Submission
if (isset($_POST['submit_review']) && isset($_SESSION['customer_logged_in'])) {
    $customer_id = $_SESSION['customer_id'];
    $rating = (int)$_POST['rating'];
    $review_text = $conn->real_escape_string(trim($_POST['review_text']));
    $customer_name = $_SESSION['customer_name'];
    
    // Check if customer has purchased this product
    $purchase_check = $conn->query("
        SELECT COUNT(*) as count 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.customer_id = $customer_id 
        AND oi.product_id = $product_id 
        AND o.status = 'delivered'
    ")->fetch_assoc();
    
    if ($purchase_check['count'] > 0) {
        // Check if already reviewed
        $existing = $conn->query("SELECT id FROM product_reviews WHERE customer_id = $customer_id AND product_id = $product_id");
        
        if ($existing->num_rows > 0) {
            // Update existing review
            $update = $conn->prepare("UPDATE product_reviews SET rating = ?, review_text = ?, updated_at = NOW() WHERE customer_id = ? AND product_id = ?");
            $update->bind_param("isii", $rating, $review_text, $customer_id, $product_id);
            $update->execute();
            $_SESSION['message'] = "Review updated successfully!";
        } else {
            // Insert new review
            $insert = $conn->prepare("INSERT INTO product_reviews (product_id, customer_id, customer_name, rating, review_text, status, created_at) VALUES (?, ?, ?, ?, ?, 'approved', NOW())");
            $insert->bind_param("iisis", $product_id, $customer_id, $customer_name, $rating, $review_text);
            $insert->execute();
            $_SESSION['message'] = "Review submitted successfully!";
        }
    } else {
        $_SESSION['message'] = "You can only review products you have purchased and received.";
    }
    
    header("Location: product_reviews.php?product_id=$product_id");
    exit();
}

// Get Reviews
$reviews = $conn->query("
    SELECT * FROM product_reviews 
    WHERE product_id = $product_id AND status = 'approved' 
    ORDER BY created_at DESC
");

// Get Rating Statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM product_reviews 
    WHERE product_id = $product_id AND status = 'approved'
")->fetch_assoc();

// Check if customer can review (has purchased and received)
$can_review = false;
if (isset($_SESSION['customer_logged_in'])) {
    $customer_id = $_SESSION['customer_id'];
    $can_review_check = $conn->query("
        SELECT COUNT(*) as count 
        FROM order_items oi 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.customer_id = $customer_id 
        AND oi.product_id = $product_id 
        AND o.status = 'delivered'
    ")->fetch_assoc();
    
    $can_review = $can_review_check['count'] > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - <?= htmlspecialchars($product['name']) ?></title>
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
        
        .product-header {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .product-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #FFD700;
        }
        
        .product-info h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .rating-summary {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .rating-overview {
            display: flex;
            gap: 40px;
            align-items: center;
        }
        
        .rating-score {
            text-align: center;
        }
        
        .rating-number {
            font-size: 64px;
            font-weight: bold;
        }
        
        .rating-stars {
            font-size: 24px;
            color: #FFD700;
            margin: 10px 0;
        }
        
        .rating-count {
            color: #ccc;
            font-size: 14px;
        }
        
        .rating-bars {
            flex: 1;
        }
        
        .rating-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .rating-bar-label {
            width: 60px;
            font-size: 14px;
        }
        
        .rating-bar-bg {
            flex: 1;
            height: 20px;
            background: #333;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .rating-bar-fill {
            height: 100%;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        }
        
        .rating-bar-count {
            width: 50px;
            text-align: right;
            color: #ccc;
            font-size: 14px;
        }
        
        .review-form {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .star-rating {
            display: flex;
            gap: 10px;
            font-size: 32px;
            margin: 20px 0;
        }
        
        .star {
            cursor: pointer;
            color: #666;
            transition: color 0.2s;
        }
        
        .star.active, .star:hover {
            color: #FFD700;
        }
        
        textarea {
            width: 100%;
            padding: 15px;
            background: #000;
            border: 1px solid #FFD700;
            color: #FFD700;
            border-radius: 5px;
            font-size: 16px;
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
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
            margin-top: 15px;
        }
        
        .reviews-list {
            margin-top: 30px;
        }
        
        .review-card {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .reviewer-name {
            font-size: 18px;
            font-weight: bold;
        }
        
        .review-date {
            color: #ccc;
            font-size: 14px;
        }
        
        .review-rating {
            color: #FFD700;
            font-size: 18px;
        }
        
        .review-text {
            color: #ccc;
            line-height: 1.6;
            margin-top: 10px;
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
        
        .empty-state {
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
        </div>
    </header>
    
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <div class="product-header">
            <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
            <div class="product-info">
                <h1><?= htmlspecialchars($product['name']) ?></h1>
                <p style="color: #ccc; margin: 10px 0;"><?= htmlspecialchars($product['description']) ?></p>
                <p style="font-size: 24px; font-weight: bold;">R<?= number_format($product['price'], 2) ?></p>
                <a href="index.php?page=home" style="color: #FFD700; text-decoration: none;">← Back to Store</a>
            </div>
        </div>
        
        <div class="rating-summary">
            <h2 style="margin-bottom: 30px;">Customer Reviews</h2>
            
            <?php if ($stats['total_reviews'] > 0): ?>
                <div class="rating-overview">
                    <div class="rating-score">
                        <div class="rating-number"><?= number_format($stats['avg_rating'], 1) ?></div>
                        <div class="rating-stars">
                            <?php 
                            $avg_rating = round($stats['avg_rating']);
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $avg_rating ? '★' : '☆';
                            }
                            ?>
                        </div>
                        <div class="rating-count"><?= $stats['total_reviews'] ?> reviews</div>
                    </div>
                    
                    <div class="rating-bars">
                        <?php 
                        $ratings = [5 => $stats['five_star'], 4 => $stats['four_star'], 3 => $stats['three_star'], 2 => $stats['two_star'], 1 => $stats['one_star']];
                        foreach ($ratings as $star => $count):
                            $percentage = $stats['total_reviews'] > 0 ? ($count / $stats['total_reviews']) * 100 : 0;
                        ?>
                            <div class="rating-bar">
                                <div class="rating-bar-label"><?= $star ?> stars</div>
                                <div class="rating-bar-bg">
                                    <div class="rating-bar-fill" style="width: <?= $percentage ?>%"></div>
                                </div>
                                <div class="rating-bar-count"><?= $count ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No reviews yet. Be the first to review this product!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_SESSION['customer_logged_in'])): ?>
            <?php if ($can_review): ?>
                <div class="review-form">
                    <h3>Write a Review</h3>
                    <form method="POST">
                        <div class="star-rating" id="starRating">
                            <span class="star" data-rating="1">★</span>
                            <span class="star" data-rating="2">★</span>
                            <span class="star" data-rating="3">★</span>
                            <span class="star" data-rating="4">★</span>
                            <span class="star" data-rating="5">★</span>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="5" required>
                        <textarea name="review_text" placeholder="Share your experience with this product..." required></textarea>
                        <button type="submit" name="submit_review" class="btn">Submit Review</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert" style="background: #856404;">
                    You can only review products you have purchased and received.
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert" style="background: #856404;">
                <a href="customer_auth.php" style="color: #FFD700;">Login</a> to write a review
            </div>
        <?php endif; ?>
        
        <div class="reviews-list">
            <h3 style="margin-bottom: 20px;">All Reviews</h3>
            
            <?php if ($reviews->num_rows > 0): ?>
                <?php while ($review = $reviews->fetch_assoc()): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div>
                                <div class="reviewer-name"><?= htmlspecialchars($review['customer_name']) ?></div>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?= $i <= $review['rating'] ? '★' : '☆' ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="review-date">
                                <?= date('F d, Y', strtotime($review['created_at'])) ?>
                            </div>
                        </div>
                        <div class="review-text">
                            <?= nl2br(htmlspecialchars($review['review_text'])) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No reviews yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Star Rating Selection
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');
        let selectedRating = 5;
        
        // Initialize all stars as active
        stars.forEach(star => star.classList.add('active'));
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                selectedRating = parseInt(this.dataset.rating);
                ratingInput.value = selectedRating;
                
                stars.forEach(s => {
                    if (parseInt(s.dataset.rating) <= selectedRating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const hoverRating = parseInt(this.dataset.rating);
                stars.forEach(s => {
                    if (parseInt(s.dataset.rating) <= hoverRating) {
                        s.style.color = '#FFD700';
                    } else {
                        s.style.color = '#666';
                    }
                });
            });
        });
        
        document.getElementById('starRating').addEventListener('mouseleave', function() {
            stars.forEach(s => {
                if (parseInt(s.dataset.rating) <= selectedRating) {
                    s.style.color = '#FFD700';
                } else {
                    s.style.color = '#666';
                }
            });
        });
    </script>
</body>
</html>