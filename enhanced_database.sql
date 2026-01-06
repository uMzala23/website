-- Enhanced Amakha Store Database - Complete System
-- Run this to add all new features

USE amakha_store;

-- Add password field to customers table for authentication
ALTER TABLE customers ADD COLUMN IF NOT EXISTS password VARCHAR(255) AFTER email;

-- Product Reviews Table
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    customer_id INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment Transactions Table
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method ENUM('paypal', 'stripe', 'cod', 'bank_transfer') NOT NULL,
    transaction_id VARCHAR(255),
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'PHP',
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order (order_id),
    INDEX idx_status (status),
    INDEX idx_transaction (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Tracking/History Table
CREATE TABLE IF NOT EXISTS order_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL,
    notes TEXT,
    location VARCHAR(255),
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Marketing Subscribers Table
CREATE TABLE IF NOT EXISTS email_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    full_name VARCHAR(255),
    status ENUM('active', 'unsubscribed') DEFAULT 'active',
    source VARCHAR(100),
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Campaigns Table
CREATE TABLE IF NOT EXISTS email_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('draft', 'scheduled', 'sent') DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    total_sent INT DEFAULT 0,
    total_opened INT DEFAULT 0,
    total_clicked INT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory Logs Table
CREATE TABLE IF NOT EXISTS inventory_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    change_type ENUM('restock', 'sale', 'adjustment', 'return') NOT NULL,
    quantity_change INT NOT NULL,
    previous_stock INT NOT NULL,
    new_stock INT NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_type (change_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Currency Rates Table (for multi-currency support)
CREATE TABLE IF NOT EXISTS currency_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    currency_code VARCHAR(10) NOT NULL UNIQUE,
    currency_name VARCHAR(100) NOT NULL,
    exchange_rate DECIMAL(10, 6) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_currency (currency_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wishlist Table
CREATE TABLE IF NOT EXISTS customer_wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (customer_id, product_id),
    INDEX idx_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coupons/Discounts Table
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_type ENUM('percentage', 'fixed_amount') NOT NULL,
    discount_value DECIMAL(10, 2) NOT NULL,
    min_purchase_amount DECIMAL(10, 2) DEFAULT 0,
    max_uses INT DEFAULT NULL,
    times_used INT DEFAULT 0,
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sales Reports View
CREATE OR REPLACE VIEW sales_by_category AS
SELECT 
    p.category,
    COUNT(DISTINCT oi.order_id) as total_orders,
    SUM(oi.quantity) as total_units_sold,
    SUM(oi.subtotal) as total_revenue,
    AVG(oi.price) as avg_price
FROM products p
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id
WHERE o.status != 'cancelled'
GROUP BY p.category;

CREATE OR REPLACE VIEW monthly_revenue AS
SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as total_orders,
    SUM(total_amount) as revenue,
    AVG(total_amount) as avg_order_value
FROM orders
WHERE status != 'cancelled'
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY month DESC;

CREATE OR REPLACE VIEW top_customers AS
SELECT 
    c.id,
    c.full_name,
    c.email,
    COUNT(o.id) as total_orders,
    SUM(o.total_amount) as total_spent,
    MAX(o.created_at) as last_order_date
FROM customers c
JOIN orders o ON c.id = o.customer_id
WHERE o.status != 'cancelled'
GROUP BY c.id
ORDER BY total_spent DESC;

CREATE OR REPLACE VIEW product_performance AS
SELECT 
    p.id,
    p.name,
    p.category,
    p.price,
    p.stock,
    COALESCE(SUM(oi.quantity), 0) as units_sold,
    COALESCE(SUM(oi.subtotal), 0) as revenue,
    COALESCE(AVG(pr.rating), 0) as avg_rating,
    COUNT(DISTINCT pr.id) as review_count
FROM products p
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
GROUP BY p.id;

-- Insert default currency rates
INSERT INTO currency_rates (currency_code, currency_name, exchange_rate, is_active) VALUES
('PHP', 'Philippine Peso', 1.000000, TRUE),
('USD', 'US Dollar', 0.018000, TRUE),
('EUR', 'Euro', 0.017000, TRUE),
('GBP', 'British Pound', 0.014000, TRUE),
('JPY', 'Japanese Yen', 2.600000, TRUE),
('AUD', 'Australian Dollar', 0.027000, TRUE),
('CAD', 'Canadian Dollar', 0.024000, TRUE),
('SGD', 'Singapore Dollar', 0.024000, TRUE)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Sample Email Subscribers
INSERT INTO email_subscribers (email, full_name, source) VALUES
('subscriber1@example.com', 'John Subscriber', 'website_footer'),
('subscriber2@example.com', 'Jane Marketing', 'checkout_page')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

-- Sample Coupons
INSERT INTO coupons (code, discount_type, discount_value, min_purchase_amount, max_uses, valid_from, valid_until, status) VALUES
('WELCOME10', 'percentage', 10.00, 500.00, 100, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active'),
('FIRSTBUY', 'fixed_amount', 100.00, 1000.00, 50, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'active'),
('FREESHIP', 'fixed_amount', 150.00, 1500.00, NULL, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'active')
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Stored Procedures

DELIMITER //

-- Procedure to apply coupon
CREATE PROCEDURE apply_coupon(
    IN coupon_code VARCHAR(50),
    IN order_total DECIMAL(10, 2),
    OUT discount_amount DECIMAL(10, 2),
    OUT is_valid BOOLEAN
)
BEGIN
    DECLARE v_discount_type VARCHAR(20);
    DECLARE v_discount_value DECIMAL(10, 2);
    DECLARE v_min_purchase DECIMAL(10, 2);
    DECLARE v_max_uses INT;
    DECLARE v_times_used INT;
    DECLARE v_valid_until DATE;
    
    SET is_valid = FALSE;
    SET discount_amount = 0;
    
    SELECT discount_type, discount_value, min_purchase_amount, max_uses, times_used, valid_until
    INTO v_discount_type, v_discount_value, v_min_purchase, v_max_uses, v_times_used, v_valid_until
    FROM coupons
    WHERE code = coupon_code AND status = 'active' AND CURDATE() BETWEEN valid_from AND valid_until;
    
    IF v_discount_value IS NOT NULL THEN
        IF order_total >= v_min_purchase AND (v_max_uses IS NULL OR v_times_used < v_max_uses) THEN
            SET is_valid = TRUE;
            
            IF v_discount_type = 'percentage' THEN
                SET discount_amount = order_total * (v_discount_value / 100);
            ELSE
                SET discount_amount = v_discount_value;
            END IF;
            
            UPDATE coupons SET times_used = times_used + 1 WHERE code = coupon_code;
        END IF;
    END IF;
END //

-- Procedure to get sales report
CREATE PROCEDURE get_sales_report(
    IN start_date DATE,
    IN end_date DATE
)
BEGIN
    SELECT 
        DATE(o.created_at) as sale_date,
        COUNT(DISTINCT o.id) as total_orders,
        SUM(o.total_amount) as daily_revenue,
        AVG(o.total_amount) as avg_order_value,
        COUNT(DISTINCT o.customer_id) as unique_customers,
        SUM(oi.quantity) as total_items_sold
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.status != 'cancelled'
    AND DATE(o.created_at) BETWEEN start_date AND end_date
    GROUP BY DATE(o.created_at)
    ORDER BY sale_date DESC;
END //

-- Procedure to update inventory
CREATE PROCEDURE update_inventory(
    IN p_product_id INT,
    IN p_quantity_change INT,
    IN p_change_type VARCHAR(50),
    IN p_notes TEXT,
    IN p_admin_id INT
)
BEGIN
    DECLARE v_current_stock INT;
    DECLARE v_new_stock INT;
    
    SELECT stock INTO v_current_stock FROM products WHERE id = p_product_id;
    
    SET v_new_stock = v_current_stock + p_quantity_change;
    
    UPDATE products SET stock = v_new_stock WHERE id = p_product_id;
    
    INSERT INTO inventory_logs (product_id, change_type, quantity_change, previous_stock, new_stock, notes, created_by)
    VALUES (p_product_id, p_change_type, p_quantity_change, v_current_stock, v_new_stock, p_notes, p_admin_id);
END //

-- Procedure to send email campaign
CREATE PROCEDURE send_email_campaign(
    IN campaign_id INT
)
BEGIN
    DECLARE v_total_sent INT DEFAULT 0;
    
    SELECT COUNT(*) INTO v_total_sent
    FROM email_subscribers
    WHERE status = 'active';
    
    UPDATE email_campaigns
    SET status = 'sent',
        sent_at = NOW(),
        total_sent = v_total_sent
    WHERE id = campaign_id;
END //

DELIMITER ;

-- Triggers

DELIMITER //

-- Trigger to create order tracking entry
CREATE TRIGGER after_order_status_update
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO order_tracking (order_id, status, notes)
        VALUES (NEW.id, NEW.status, CONCAT('Status changed from ', OLD.status, ' to ', NEW.status));
    END IF;
END //

-- Trigger to log inventory changes on order
CREATE TRIGGER after_order_item_insert
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    DECLARE v_current_stock INT;
    
    SELECT stock INTO v_current_stock FROM products WHERE id = NEW.product_id;
    
    INSERT INTO inventory_logs (product_id, change_type, quantity_change, previous_stock, new_stock, notes)
    VALUES (NEW.product_id, 'sale', -NEW.quantity, v_current_stock + NEW.quantity, v_current_stock, 
            CONCAT('Order #', NEW.order_id));
END //

DELIMITER ;

-- Create Indexes for Performance
CREATE INDEX idx_order_customer ON orders(customer_id, status);
CREATE INDEX idx_order_date_status ON orders(created_at, status);
CREATE INDEX idx_product_category_status ON products(category, status, stock);
CREATE INDEX idx_review_product_status ON product_reviews(product_id, status, rating);

-- Display Setup Confirmation
SELECT 'Enhanced database setup completed!' as Status;
SELECT 'New features added:' as Info;
SELECT '- Customer Authentication' as Feature UNION ALL
SELECT '- Product Reviews & Ratings' UNION ALL
SELECT '- Payment Gateway Integration' UNION ALL
SELECT '- Order Tracking System' UNION ALL
SELECT '- Email Marketing' UNION ALL
SELECT '- Inventory Management' UNION ALL
SELECT '- Multi-Currency Support' UNION ALL
SELECT '- Wishlist Functionality' UNION ALL
SELECT '- Coupons & Discounts' UNION ALL
SELECT '- Advanced Analytics & Reports';