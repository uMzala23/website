-- Amakha Store Database Setup
-- Run this SQL file to create the database structure

CREATE DATABASE IF NOT EXISTS amakha_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE amakha_store;

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category ENUM('perfume', 'cologne', 'clothing') NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    image_url VARCHAR(500),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customers Table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    postal_code VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    postal_code VARCHAR(20),
    notes TEXT,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items Table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Users Table (for future admin panel authentication)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    email VARCHAR(255) NOT NULL UNIQUE,
    role ENUM('admin', 'manager') DEFAULT 'manager',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Sample Products with Realistic Images
INSERT INTO products (name, category, description, price, stock, image_url) VALUES
-- Car Perfumes
('Amakha Black Oud Car Perfume', 'perfume', 'Luxurious oriental fragrance with notes of oud, amber and sandalwood. Long-lasting premium car air freshener.', 1299.00, 50, 'https://images.unsplash.com/photo-1541643600914-78b084683601?w=400'),
('Amakha Ocean Breeze Car Perfume', 'perfume', 'Fresh aquatic scent with hints of sea salt and citrus. Perfect for a clean, refreshing atmosphere.', 999.00, 75, 'https://images.unsplash.com/photo-1592428143497-c1d3c4e07c7f?w=400'),
('Amakha Leather & Wood Car Perfume', 'perfume', 'Sophisticated blend of rich leather and cedarwood. Masculine and elegant fragrance.', 1399.00, 40, 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=400'),

-- Colognes
('Amakha Signature Cologne', 'cologne', 'Premium eau de parfum with notes of bergamot, jasmine and musk. Our signature scent for the modern individual.', 2499.00, 30, 'https://images.unsplash.com/photo-1541643600914-78b084683601?w=400'),
('Amakha Royal Oud Cologne', 'cologne', 'Luxurious oud-based cologne with amber and rose. Long-lasting oriental fragrance.', 2999.00, 25, 'https://images.unsplash.com/photo-1592428143497-c1d3c4e07c7f?w=400'),
('Amakha Fresh Sport Cologne', 'cologne', 'Energizing citrus cologne with mint and marine notes. Perfect for active lifestyles.', 2199.00, 45, 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=400'),

-- Clothing
('Amakha Premium Black T-Shirt', 'clothing', 'High-quality cotton t-shirt with embroidered gold Amakha logo. Available in sizes S-XXL.', 899.00, 100, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400'),
('Amakha Gold Edition T-Shirt', 'clothing', 'Limited edition t-shirt with metallic gold print. Premium fabric blend for maximum comfort.', 1099.00, 60, 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=400'),
('Amakha Classic Polo Shirt', 'clothing', 'Elegant polo shirt with subtle Amakha branding. Perfect for casual elegance.', 1299.00, 80, 'https://images.unsplash.com/photo-1586790170083-2f9ceadc732d?w=400'),
('Amakha Luxury Hoodie', 'clothing', 'Premium fleece hoodie with embroidered logo. Comfortable and stylish.', 1899.00, 50, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=400');

-- Insert Sample Admin User (password: admin123 - hashed with password_hash)
INSERT INTO admin_users (username, password, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amakha Administrator', 'admin@amakha.com', 'admin');

-- Insert Sample Customer for Testing
INSERT INTO customers (full_name, email, phone, address, city, postal_code) VALUES
('John Doe', 'john.doe@example.com', '+63 912 345 6789', '123 Main Street, Barangay Sample', 'Manila', '1000');

-- Create Views for Reporting
CREATE OR REPLACE VIEW order_summary AS
SELECT 
    o.id as order_id,
    o.customer_id,
    c.full_name,
    c.email,
    o.total_amount,
    o.status,
    o.created_at,
    COUNT(oi.id) as item_count
FROM orders o
JOIN customers c ON o.customer_id = c.id
LEFT JOIN order_items oi ON o.id = oi.order_id
GROUP BY o.id;

CREATE OR REPLACE VIEW product_sales AS
SELECT 
    p.id as product_id,
    p.name as product_name,
    p.category,
    p.price,
    COALESCE(SUM(oi.quantity), 0) as total_sold,
    COALESCE(SUM(oi.subtotal), 0) as total_revenue
FROM products p
LEFT JOIN order_items oi ON p.id = oi.product_id
GROUP BY p.id;

-- Create Stored Procedures
DELIMITER //

-- Procedure to get low stock products
CREATE PROCEDURE get_low_stock_products(IN threshold INT)
BEGIN
    SELECT id, name, category, stock, price
    FROM products
    WHERE stock <= threshold AND status = 'active'
    ORDER BY stock ASC;
END //

-- Procedure to get customer order history
CREATE PROCEDURE get_customer_orders(IN customer_email VARCHAR(255))
BEGIN
    SELECT 
        o.id,
        o.total_amount,
        o.status,
        o.created_at,
        GROUP_CONCAT(oi.product_name SEPARATOR ', ') as products
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.email = customer_email
    GROUP BY o.id
    ORDER BY o.created_at DESC;
END //

-- Procedure to update order status
CREATE PROCEDURE update_order_status(
    IN order_id INT,
    IN new_status VARCHAR(50)
)
BEGIN
    UPDATE orders 
    SET status = new_status, updated_at = CURRENT_TIMESTAMP
    WHERE id = order_id;
END //

DELIMITER ;

-- Create Triggers
DELIMITER //

-- Trigger to validate stock before order
CREATE TRIGGER check_stock_before_order_item
BEFORE INSERT ON order_items
FOR EACH ROW
BEGIN
    DECLARE current_stock INT;
    SELECT stock INTO current_stock FROM products WHERE id = NEW.product_id;
    
    IF current_stock < NEW.quantity THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Insufficient stock for this product';
    END IF;
END //

-- Trigger to log price changes
CREATE TABLE IF NOT EXISTS product_price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    old_price DECIMAL(10, 2),
    new_price DECIMAL(10, 2),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci //

CREATE TRIGGER log_price_change
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    IF OLD.price != NEW.price THEN
        INSERT INTO product_price_history (product_id, old_price, new_price)
        VALUES (NEW.id, OLD.price, NEW.price);
    END IF;
END //

DELIMITER ;

-- Create Indexes for Performance
CREATE INDEX idx_order_date ON orders(created_at DESC);
CREATE INDEX idx_product_category_status ON products(category, status);
CREATE INDEX idx_customer_email ON customers(email);

-- Grant Permissions (adjust as needed for your setup)
-- GRANT ALL PRIVILEGES ON amakha_store.* TO 'amakha_user'@'localhost' IDENTIFIED BY 'your_secure_password';
-- FLUSH PRIVILEGES;

-- Display confirmation message
SELECT 'Database setup completed successfully!' as Status;
SELECT 'Sample products, customer, and admin user created.' as Info;
SELECT 'Admin credentials - Username: admin, Password: admin123 (CHANGE THIS!)' as Security;