-- Create Database
CREATE DATABASE IF NOT EXISTS shopee_db2;
USE shopee_db2;

-- Users Table (with admin, seller, customer roles)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'seller', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories Table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products Table (removed seller_id - now many-to-many)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Product_Sellers (Many-to-Many: Multiple sellers can sell the same product)
CREATE TABLE product_sellers (
    product_id INT NOT NULL,
    seller_id INT NOT NULL,
    seller_price DECIMAL(10, 2),
    seller_stock INT DEFAULT 0,
    PRIMARY KEY (product_id, seller_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Product_Categories (Many-to-Many)
CREATE TABLE product_categories (
    product_id INT,
    category_id INT,
    PRIMARY KEY (product_id, category_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Cart Table (updated to include seller_id)
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    seller_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id, seller_id)
);

-- Orders Table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    cancelled_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order_Items Table (Many-to-Many with seller info)
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    seller_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (seller_id) REFERENCES users(id)
);

-- Insert Sample Data
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@shopee.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin'),
('seller1', 'seller1@shopee.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Seller One', 'seller'),
('seller2', 'seller2@shopee.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Seller Two', 'seller'),
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'customer');

INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and accessories'),
('Clothing', 'Fashion and apparel'),
('Home & Kitchen', 'Home and kitchen essentials'),
('Books', 'Books and literature'),
('Sports', 'Sports and outdoor equipment');

INSERT INTO products (name, description, price, stock) VALUES
('Smartphone XYZ', 'Latest smartphone with advanced features', 299.99, 50),
('Wireless Headphones', 'Premium wireless headphones', 89.99, 30),
('Cotton T-Shirt', 'Comfortable cotton t-shirt', 19.99, 100),
('Laptop Stand', 'Ergonomic laptop stand', 39.99, 25),
('Programming Book', 'Learn PHP and MySQL', 49.99, 15);

-- Many-to-Many: Multiple sellers selling the same products
INSERT INTO product_sellers (product_id, seller_id, seller_price, seller_stock) VALUES
(1, 2, 299.99, 25),
(1, 3, 289.99, 30),
(2, 2, 89.99, 15),
(2, 3, 85.99, 20),
(3, 2, 19.99, 50),
(4, 3, 39.99, 25),
(5, 2, 49.99, 10),
(5, 3, 45.99, 8);

INSERT INTO product_categories (product_id, category_id) VALUES
(1, 1), (2, 1), (3, 2), (4, 1), (5, 4);

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_expires (user_id, expires_at)
);