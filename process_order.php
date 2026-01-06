<?php
session_start();

// Database Configuration
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

// Check if order is being placed
if (isset($_POST['place_order'])) {
    
    // Validate cart
    if (empty($_SESSION['cart'])) {
        $_SESSION['message'] = "Your cart is empty!";
        header("Location: index.php?page=cart");
        exit();
    }
    
    // Sanitize and validate input
    $full_name = $conn->real_escape_string(trim($_POST['full_name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $address = $conn->real_escape_string(trim($_POST['address']));
    $city = $conn->real_escape_string(trim($_POST['city']));
    $postal_code = $conn->real_escape_string(trim($_POST['postal_code']));
    $notes = $conn->real_escape_string(trim($_POST['notes']));
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email address!";
        header("Location: index.php?page=cart&checkout=1");
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if customer exists, if not create new customer
        $customer_check = $conn->prepare("SELECT id FROM customers WHERE email = ?");
        $customer_check->bind_param("s", $email);
        $customer_check->execute();
        $customer_result = $customer_check->get_result();
        
        if ($customer_result->num_rows > 0) {
            $customer_id = $customer_result->fetch_assoc()['id'];
            
            // Update customer information
            $update_customer = $conn->prepare("UPDATE customers SET full_name = ?, phone = ?, address = ?, city = ?, postal_code = ? WHERE id = ?");
            $update_customer->bind_param("sssssi", $full_name, $phone, $address, $city, $postal_code, $customer_id);
            $update_customer->execute();
        } else {
            // Insert new customer (without password for guest checkout)
            $insert_customer = $conn->prepare("INSERT INTO customers (full_name, email, phone, address, city, postal_code, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $insert_customer->bind_param("ssssss", $full_name, $email, $phone, $address, $city, $postal_code);
            $insert_customer->execute();
            $customer_id = $conn->insert_id;
        }
        
        // Calculate order total
        $order_total = 0;
        $order_items = [];
        
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            // Fetch product details
            $product_query = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id = ? AND status = 'active'");
            $product_query->bind_param("i", $product_id);
            $product_query->execute();
            $product = $product_query->get_result()->fetch_assoc();
            
            if ($product) {
                // Check stock availability
                if ($product['stock'] < $quantity) {
                    throw new Exception("Insufficient stock for " . $product['name']);
                }
                
                $subtotal = $product['price'] * $quantity;
                $order_total += $subtotal;
                
                $order_items[] = [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'quantity' => $quantity,
                    'price' => $product['price'],
                    'subtotal' => $subtotal
                ];
            }
        }
        
        if (empty($order_items)) {
            throw new Exception("No valid items in cart");
        }
        
        // Insert order
        $insert_order = $conn->prepare("INSERT INTO orders (customer_id, full_name, email, phone, address, city, postal_code, notes, total_amount, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $insert_order->bind_param("isssssssd", $customer_id, $full_name, $email, $phone, $address, $city, $postal_code, $notes, $order_total);
        $insert_order->execute();
        $order_id = $conn->insert_id;
        
        // Insert order items and update stock
        $insert_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        $update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        
        foreach ($order_items as $item) {
            $insert_item->bind_param("iisidd", $order_id, $item['product_id'], $item['product_name'], $item['quantity'], $item['price'], $item['subtotal']);
            $insert_item->execute();
            
            $update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
            $update_stock->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Store order info in session for payment
        $_SESSION['pending_order_id'] = $order_id;
        $_SESSION['order_total'] = $order_total;
        $_SESSION['order_items'] = $order_items;
        
        // Redirect to payment gateway
        header("Location: payment_gateway.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['message'] = "Error processing order: " . $e->getMessage();
        header("Location: index.php?page=cart&checkout=1");
        exit();
    }
} else {
    // If accessed directly without POST
    header("Location: index.php");
    exit();
}

$conn->close();
?>