<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Database Configuration
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'amakha_store'
];

$conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$page = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Handle Product Image Upload
if (isset($_POST['upload_image'])) {
    $product_id = (int)$_POST['product_id'];
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['product_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'product_' . $product_id . '_' . time() . '.' . $ext;
            $upload_path = 'uploads/products/' . $new_filename;
            
            if (!is_dir('uploads/products')) {
                mkdir('uploads/products', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                $update = $conn->prepare("UPDATE products SET image_url = ? WHERE id = ?");
                $update->bind_param("si", $upload_path, $product_id);
                $update->execute();
                $_SESSION['message'] = "Image uploaded successfully!";
            }
        }
    }
}

// Handle Order Status Update
if (isset($_POST['update_order_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $conn->real_escape_string($_POST['status']);
    
    $update = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $update->bind_param("si", $status, $order_id);
    $update->execute();
    $_SESSION['message'] = "Order status updated!";
}

// Handle Product Add/Edit
if (isset($_POST['save_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    
    if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        $update = $conn->prepare("UPDATE products SET name=?, category=?, description=?, price=?, stock=? WHERE id=?");
        $update->bind_param("sssdii", $name, $category, $description, $price, $stock, $product_id);
        $update->execute();
        $_SESSION['message'] = "Product updated successfully!";
    } else {
        $insert = $conn->prepare("INSERT INTO products (name, category, description, price, stock, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $insert->bind_param("sssdi", $name, $category, $description, $price, $stock);
        $insert->execute();
        $_SESSION['message'] = "Product added successfully!";
    }
}

// Fetch Statistics
$stats = [];
$stats['total_orders'] = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$stats['total_revenue'] = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status != 'cancelled'")->fetch_assoc()['revenue'] ?? 0;
$stats['pending_orders'] = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'];
$stats['total_products'] = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'")->fetch_assoc()['count'];
$stats['total_customers'] = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
$stats['low_stock'] = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock < 10 AND status = 'active'")->fetch_assoc()['count'];

// Monthly Sales Data for Chart
$monthly_sales = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as total
    FROM orders 
    WHERE status != 'cancelled' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Amakha</title>
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
        
        /* Header */
        .admin-header {
            background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
            padding: 20px 0;
            border-bottom: 2px solid #FFD700;
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .btn-logout {
            background: #dc3545;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }
        
        /* Sidebar */
        .admin-container {
            display: flex;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .sidebar {
            width: 250px;
            background: #1a1a1a;
            min-height: calc(100vh - 80px);
            border-right: 1px solid #FFD700;
            padding: 20px 0;
        }
        
        .sidebar a {
            display: block;
            padding: 15px 20px;
            color: #FFD700;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255, 215, 0, 0.1);
            border-left-color: #FFD700;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
        }
        
        .alert {
            background: #155724;
            border: 1px solid #FFD700;
            color: #FFD700;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #ccc;
            font-size: 14px;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            overflow: hidden;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #FFD700;
        }
        
        th {
            background: #000;
            font-weight: bold;
        }
        
        tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }
        
        /* Forms */
        .form-container {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 30px;
            max-width: 800px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            background: #000;
            border: 1px solid #FFD700;
            color: #FFD700;
            border-radius: 5px;
            font-size: 16px;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 14px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: #fff;
        }
        
        /* Chart Container */
        .chart-container {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        h2 {
            margin-bottom: 20px;
            font-size: 28px;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-pending { background: #ffc107; color: #000; }
        .badge-processing { background: #17a2b8; color: #fff; }
        .badge-shipped { background: #007bff; color: #fff; }
        .badge-delivered { background: #28a745; color: #fff; }
        .badge-cancelled { background: #dc3545; color: #fff; }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="header-content">
            <div class="logo">AMAKHA Admin</div>
            <div class="admin-info">
                <span>Welcome, <?= htmlspecialchars($_SESSION['admin_name']) ?></span>
                <a href="index.php" class="btn">View Store</a>
                <a href="admin_login.php?logout" class="btn-logout">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="admin-container">
        <div class="sidebar">
            <a href="?section=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
            <a href="?section=orders" class="<?= $page === 'orders' ? 'active' : '' ?>">📦 Orders</a>
            <a href="?section=products" class="<?= $page === 'products' ? 'active' : '' ?>">🛍️ Products</a>
            <a href="?section=customers" class="<?= $page === 'customers' ? 'active' : '' ?>">👥 Customers</a>
            <a href="?section=inventory" class="<?= $page === 'inventory' ? 'active' : '' ?>">📋 Inventory</a>
            <a href="?section=reports" class="<?= $page === 'reports' ? 'active' : '' ?>">📈 Reports</a>
            <a href="?section=reviews" class="<?= $page === 'reviews' ? 'active' : '' ?>">⭐ Reviews</a>
            <a href="?section=settings" class="<?= $page === 'settings' ? 'active' : '' ?>">⚙️ Settings</a>
        </div>
        
        <div class="main-content">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert"><?= htmlspecialchars($_SESSION['message']) ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if ($page === 'dashboard'): ?>
                <h2>Dashboard Overview</h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['total_orders'] ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">R<?= number_format($stats['total_revenue'], 2) ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['pending_orders'] ?></div>
                        <div class="stat-label">Pending Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['total_products'] ?></div>
                        <div class="stat-label">Active Products</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['total_customers'] ?></div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['low_stock'] ?></div>
                        <div class="stat-label">Low Stock Items</div>
                    </div>
                </div>
                
                <h2>Recent Orders</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_orders = $conn->query("SELECT o.*, c.full_name FROM orders o JOIN customers c ON o.customer_id = c.id ORDER BY o.created_at DESC LIMIT 10");
                        while ($order = $recent_orders->fetch_assoc()):
                        ?>
                        <tr>
                            <td>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($order['full_name']) ?></td>
                            <td>R<?= number_format($order['total_amount'], 2) ?></td>
                            <td><span class="badge badge-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                            <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                            <td><a href="?section=orders&view=<?= $order['id'] ?>" class="btn btn-sm">View</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
            <?php elseif ($page === 'products'): ?>
                <h2>Product Management</h2>
                <button onclick="document.getElementById('addProductForm').style.display='block'" class="btn">Add New Product</button>
                
                <div id="addProductForm" style="display:none; margin-top: 20px;">
                    <div class="form-container">
                        <h3>Add New Product</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Product Name</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category" required>
                                    <option value="perfume">Car Perfume</option>
                                    <option value="cologne">Cologne</option>
                                    <option value="clothing">Clothing</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Price (R)</label>
                                <input type="number" name="price" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label>Stock Quantity</label>
                                <input type="number" name="stock" required>
                            </div>
                            <button type="submit" name="save_product" class="btn">Save Product</button>
                        </form>
                    </div>
                </div>
                
                <table style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $products = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
                        while ($product = $products->fetch_assoc()):
                        ?>
                        <tr>
                            <td><img src="<?= htmlspecialchars($product['image_url']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;"></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= ucfirst($product['category']) ?></td>
                            <td>R<?= number_format($product['price'], 2) ?></td>
                            <td><?= $product['stock'] ?></td>
                            <td>
                                <form method="POST" enctype="multipart/form-data" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="file" name="product_image" accept="image/*" style="width: auto; padding: 5px;">
                                    <button type="submit" name="upload_image" class="btn btn-sm">Upload Image</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
            <?php elseif ($page === 'orders'): ?>
                <h2>Order Management</h2>
                
                <?php if (isset($_GET['view'])): 
                    $order_id = (int)$_GET['view'];
                    $order = $conn->query("SELECT o.*, c.full_name FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = $order_id")->fetch_assoc();
                    $items = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id");
                ?>
                    <div class="form-container">
                        <h3>Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                        <p><strong>Customer:</strong> <?= htmlspecialchars($order['full_name']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?> <?= htmlspecialchars($order['postal_code']) ?></p>
                        <p><strong>Date:</strong> <?= date('F d, Y H:i', strtotime($order['created_at'])) ?></p>
                        
                        <h4 style="margin-top: 20px;">Order Items</h4>
                        <table>
                            <tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
                            <?php while ($item = $items->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td>R<?= number_format($item['price'], 2) ?></td>
                                <td>R<?= number_format($item['subtotal'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <tr>
                                <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                                <td><strong>R<?= number_format($order['total_amount'], 2) ?></strong></td>
                            </tr>
                        </table>
                        
                        <form method="POST" style="margin-top: 20px;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <div class="form-group">
                                <label>Update Status</label>
                                <select name="status">
                                    <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" name="update_order_status" class="btn">Update Status</button>
                            <a href="?section=orders" class="btn">Back to Orders</a>
                        </form>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $all_orders = $conn->query("SELECT o.*, c.full_name FROM orders o JOIN customers c ON o.customer_id = c.id ORDER BY o.created_at DESC");
                            while ($order = $all_orders->fetch_assoc()):
                            ?>
                            <tr>
                                <td>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($order['full_name']) ?></td>
                                <td><?= htmlspecialchars($order['email']) ?></td>
                                <td>R<?= number_format($order['total_amount'], 2) ?></td>
                                <td><span class="badge badge-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                <td><a href="?section=orders&view=<?= $order['id'] ?>" class="btn btn-sm">View Details</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
            <?php else: ?>
                <h2><?= ucfirst($page) ?></h2>
                <p style="color: #ccc;">This section is under development.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>