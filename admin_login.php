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

// Handle Login
if (isset($_POST['login'])) {
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = trim($_POST['password']);
    
    $query = $conn->prepare("SELECT id, username, password, full_name, email, role FROM admin_users WHERE username = ? AND status = 'active'");
    $query->bind_param("s", $username);
    $query->execute();
    $result = $query->get_result();
    
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        
        if (password_verify($password, $admin['password'])) {
            // Set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_logged_in'] = true;
            
            // Update last login
            $update = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $update->bind_param("i", $admin['id']);
            $update->execute();
            
            header("Location: admin_dashboard.php");
            exit();
        }
    }
    
    $error = "Invalid username or password";
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Amakha</title>
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
        }
        
        .login-container {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 10px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
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
            margin-bottom: 40px;
            font-size: 14px;
        }
        
        .stars-decoration {
            text-align: center;
            font-size: 24px;
            margin-bottom: 30px;
        }
        
        .star {
            display: inline-block;
            animation: twinkle 1.5s infinite;
            margin: 0 5px;
        }
        
        .star:nth-child(2) { animation-delay: 0.5s; }
        .star:nth-child(3) { animation-delay: 1s; }
        
        @keyframes twinkle {
            0%, 100% { opacity: 1; transform: scale(1) rotate(0deg); }
            50% { opacity: 0.5; transform: scale(1.2) rotate(180deg); }
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 15px;
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
    <div class="login-container">
        <div class="logo">AMAKHA</div>
        <div class="subtitle">Admin Control Panel</div>
        <div class="stars-decoration">
            <span class="star">★</span>
            <span class="star">★</span>
            <span class="star">★</span>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" name="login" class="btn">Login to Dashboard</button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Back to Store</a>
        </div>
    </div>
</body>
</html>