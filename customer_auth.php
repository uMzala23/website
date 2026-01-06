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
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$action = isset($_GET['action']) ? $_GET['action'] : 'login';
$error = '';
$success = '';

// Handle Registration
if (isset($_POST['register'])) {
    $full_name = $conn->real_escape_string(trim($_POST['full_name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if email exists
        $check = $conn->prepare("SELECT id FROM customers WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = "Email already registered";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO customers (full_name, email, password, phone, created_at) VALUES (?, ?, ?, ?, NOW())");
            $insert->bind_param("ssss", $full_name, $email, $hashed_password, $phone);
            
            if ($insert->execute()) {
                $success = "Registration successful! You can now login.";
                $action = 'login';
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

// Handle Login
if (isset($_POST['login'])) {
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = trim($_POST['password']);
    
    $query = $conn->prepare("SELECT id, full_name, email, password, phone, address, city, postal_code FROM customers WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();
    
    if ($result->num_rows === 1) {
        $customer = $result->fetch_assoc();
        
        if (password_verify($password, $customer['password'])) {
            $_SESSION['customer_id'] = $customer['id'];
            $_SESSION['customer_name'] = $customer['full_name'];
            $_SESSION['customer_email'] = $customer['email'];
            $_SESSION['customer_logged_in'] = true;
            
            // Pre-fill customer data
            $_SESSION['customer_phone'] = $customer['phone'];
            $_SESSION['customer_address'] = $customer['address'];
            $_SESSION['customer_city'] = $customer['city'];
            $_SESSION['customer_postal_code'] = $customer['postal_code'];
            
            header("Location: index.php");
            exit();
        }
    }
    
    $error = "Invalid email or password";
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: customer_auth.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $action === 'register' ? 'Register' : 'Login' ?> - Amakha</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFD700;
            padding: 20px;
        }
        
        .auth-container {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 50px rgba(255, 215, 0, 0.2);
        }
        
        .logo {
            text-align: center;
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        
        .subtitle {
            text-align: center;
            color: #ccc;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .tab {
            flex: 1;
            padding: 12px;
            background: #000;
            border: 1px solid #FFD700;
            text-align: center;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s;
            text-decoration: none;
            color: #FFD700;
        }
        
        .tab.active {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            font-weight: bold;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px;
            background: #000;
            border: 1px solid #FFD700;
            color: #FFD700;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #FFA500;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
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
            transition: transform 0.3s;
            margin-top: 10px;
        }
        
        .btn:hover {
            transform: scale(1.02);
        }
        
        .error {
            background: #dc3545;
            color: #fff;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background: #28a745;
            color: #fff;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #FFD700;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .back-link a:hover {
            color: #FFA500;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo">AMAKHA</div>
        <div class="subtitle">Customer Portal</div>
        
        <div class="tabs">
            <a href="?action=login" class="tab <?= $action === 'login' ? 'active' : '' ?>">Login</a>
            <a href="?action=register" class="tab <?= $action === 'register' ? 'active' : '' ?>">Register</a>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($action === 'login'): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required autofocus>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" name="login" class="btn">Login</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required autofocus>
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label>Password (min. 8 characters)</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <button type="submit" name="register" class="btn">Create Account</button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="index.php">← Back to Store</a>
        </div>
    </div>
</body>
</html>