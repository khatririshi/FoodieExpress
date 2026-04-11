-- ============================================================
-- FoodieExpress - Full Database Schema
-- BCA College Project | Run this in phpMyAdmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS foodieexpress;
USE foodieexpress;

-- USERS TABLE
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    profile_pic VARCHAR(255) DEFAULT 'default.png',
    weight_goal ENUM('lose','gain','maintain') DEFAULT 'maintain',
    health_condition VARCHAR(100) DEFAULT 'none',
    daily_calories INT DEFAULT 2000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ADMIN TABLE
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (username: admin, password: password)
-- Hash generated with password_hash('password', PASSWORD_DEFAULT)
INSERT INTO admin (username, password, email) VALUES ('admin', '$2y$10$TKh8H1.PfbuNkLSsZ7n9NO.6346640GfPjKkMT1VIBa7H1fMq2qWy', 'admin@foodieexpress.com');

-- RESTAURANTS TABLE
CREATE TABLE restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    cuisine_type VARCHAR(100),
    address TEXT,
    phone VARCHAR(15),
    email VARCHAR(100),
    rating DECIMAL(2,1) DEFAULT 4.0,
    delivery_time INT DEFAULT 30,
    min_order DECIMAL(8,2) DEFAULT 0.00,
    delivery_fee DECIMAL(8,2) DEFAULT 30.00,
    image VARCHAR(255) DEFAULT 'restaurant_default.jpg',
    is_open TINYINT(1) DEFAULT 1,
    opens_at TIME DEFAULT '08:00:00',
    closes_at TIME DEFAULT '23:00:00',
    is_late_night TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- FOOD ITEMS TABLE
CREATE TABLE food_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(8,2) NOT NULL,
    category VARCHAR(100),
    is_veg TINYINT(1) DEFAULT 1,
    spice_level ENUM('mild','medium','spicy','very_spicy') DEFAULT 'medium',
    calories INT DEFAULT 0,
    protein DECIMAL(5,2) DEFAULT 0,
    carbs DECIMAL(5,2) DEFAULT 0,
    fat DECIMAL(5,2) DEFAULT 0,
    tags VARCHAR(255),
    image VARCHAR(255) DEFAULT 'food_default.jpg',
    rating DECIMAL(2,1) DEFAULT 4.0,
    is_available TINYINT(1) DEFAULT 1,
    is_healthy TINYINT(1) DEFAULT 0,
    health_tags VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- CART TABLE
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT DEFAULT 1,
    session_id VARCHAR(100),
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES food_items(id) ON DELETE CASCADE
);

-- ORDERS TABLE
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    group_order_id VARCHAR(50),
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(8,2) DEFAULT 30.00,
    discount DECIMAL(8,2) DEFAULT 0.00,
    final_amount DECIMAL(10,2) NOT NULL,
    delivery_address TEXT NOT NULL,
    payment_method ENUM('cod','online','upi') DEFAULT 'cod',
    payment_status ENUM('pending','paid','failed') DEFAULT 'pending',
    order_status ENUM('placed','confirmed','preparing','out_for_delivery','delivered','cancelled') DEFAULT 'placed',
    estimated_delivery INT DEFAULT 30,
    special_instructions TEXT,
    is_acknowledged TINYINT(1) DEFAULT 0,
    is_rated TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ORDER ITEMS TABLE
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_id INT DEFAULT NULL,
    quantity INT NOT NULL,
    price DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES food_items(id) ON DELETE SET NULL
);

-- PAYMENTS TABLE
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method ENUM('cod','upi','card','netbanking','wallet') DEFAULT 'cod',
    payment_status ENUM('pending','processing','success','failed') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    upi_id VARCHAR(100),
    card_last_four VARCHAR(4),
    bank_name VARCHAR(50),
    wallet_name VARCHAR(50),
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- GROUP ORDERS TABLE
CREATE TABLE group_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_code VARCHAR(20) UNIQUE NOT NULL,
    creator_id INT NOT NULL,
    name VARCHAR(100),
    status ENUM('open','placed','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id)
);

-- GROUP ORDER MEMBERS TABLE
CREATE TABLE group_order_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_code VARCHAR(20) NOT NULL,
    user_id INT NOT NULL,
    user_name VARCHAR(100),
    amount_due DECIMAL(8,2) DEFAULT 0.00,
    is_paid TINYINT(1) DEFAULT 0,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- FEEDBACK TABLE
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100),
    email VARCHAR(100),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    rating INT DEFAULT 5,
    status ENUM('new','read','replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- RATINGS TABLE
CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rating (user_id, food_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES food_items(id) ON DELETE CASCADE
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

INSERT INTO restaurants (name, description, cuisine_type, address, phone, rating, delivery_time, min_order, delivery_fee, is_open, opens_at, closes_at, is_late_night) VALUES
('Spice Garden', 'Authentic Indian cuisine with rich flavors', 'Indian', '12 MG Road, Pune', '9876543210', 4.5, 25, 150.00, 30.00, 1, '10:00:00', '23:00:00', 0),
('Pizza Paradise', 'Best wood-fired pizzas in town', 'Italian', '45 FC Road, Pune', '9876543211', 4.3, 35, 200.00, 40.00, 1, '11:00:00', '23:30:00', 0),
('Burger Barn', 'Juicy burgers and loaded fries', 'Fast Food', '7 Koregaon Park, Pune', '9876543212', 4.2, 20, 100.00, 25.00, 1, '09:00:00', '00:00:00', 1),
('Green Bowl', 'Healthy salads and smoothie bowls', 'Healthy', '23 Baner Road, Pune', '9876543213', 4.7, 30, 120.00, 20.00, 1, '08:00:00', '22:00:00', 0),
('Midnight Bites', 'Open all night for late cravings', 'Multi-Cuisine', '89 Viman Nagar, Pune', '9876543214', 4.1, 20, 80.00, 15.00, 1, '22:00:00', '06:00:00', 1);

INSERT INTO food_items (restaurant_id, name, description, price, category, is_veg, spice_level, calories, protein, carbs, fat, tags, image, rating, is_healthy, health_tags) VALUES
(1, 'Dal Makhani', 'Slow cooked black lentils in butter and cream', 220.00, 'Main Course', 1, 'mild', 380, 14.0, 42.0, 16.0, 'protein,comfort', 'images/foods/dal-makhani-signature.jpg', 4.6, 1, 'high-protein,vegetarian'),
(1, 'Chicken Biryani', 'Aromatic basmati rice with tender chicken', 320.00, 'Main Course', 0, 'medium', 520, 28.0, 60.0, 12.0, 'popular,rice', 'images/foods/chicken-biryani.jpg', 4.8, 0, 'high-protein'),
(1, 'Palak Paneer', 'Cottage cheese in spinach gravy', 240.00, 'Main Course', 1, 'mild', 310, 18.0, 22.0, 14.0, 'healthy,iron', 'images/foods/palak-paneer.jpg', 4.5, 1, 'high-protein,iron-rich,pcos-friendly'),
(1, 'Butter Chicken', 'Creamy tomato curry with tandoori chicken pieces', 340.00, 'Main Course', 0, 'medium', 540, 29.0, 18.0, 28.0, 'popular,curry', 'images/foods/butter-chicken.jpg', 4.7, 0, 'high-protein'),
(1, 'Garlic Naan', 'Soft naan finished with garlic butter and herbs', 70.00, 'Main Course', 1, 'mild', 210, 6.0, 31.0, 7.0, 'bread,side', 'images/foods/garlic-naan.jpg', 4.5, 0, 'vegetarian'),
(1, 'Paneer Tikka Roll', 'Smoky paneer tikka wrapped with onions and mint sauce', 190.00, 'Snacks', 1, 'medium', 360, 17.0, 34.0, 14.0, 'roll,street-food', 'images/foods/paneer-kathi-roll.jpg', 4.4, 0, 'high-protein'),
(1, 'Tandoori Paneer Bowl', 'Tandoori paneer, rice, salad and yogurt dressing', 280.00, 'Bowls', 1, 'medium', 430, 20.0, 44.0, 15.0, 'bowl,balanced', 'images/foods/protein-bowl.jpg', 4.6, 1, 'high-protein,vegetarian'),
(1, 'Mango Lassi', 'Chilled mango yogurt drink with a smooth, creamy finish', 120.00, 'Beverages', 1, 'mild', 220, 6.0, 32.0, 6.0, 'drink,refreshing', 'images/foods/mango-lassi.jpg', 4.5, 0, 'vegetarian'),
(1, 'Gulab Jamun', 'Soft milk dumplings soaked in warm cardamom syrup', 150.00, 'Desserts', 1, 'mild', 310, 6.0, 52.0, 8.0, 'dessert,indian-sweet', 'images/foods/gulab-jamun.jpg', 4.7, 0, 'vegetarian'),
(1, 'Chocolate Lava Cake', 'Warm chocolate cake with a rich molten center', 210.00, 'Desserts', 1, 'mild', 390, 5.0, 48.0, 18.0, 'dessert,chocolate', 'images/foods/choco-lava-cake.jpg', 4.6, 0, 'vegetarian'),
(1, 'Mango Kulfi', 'Dense mango kulfi served cold for a classic Indian dessert finish', 170.00, 'Desserts', 1, 'mild', 260, 5.0, 30.0, 12.0, 'dessert,frozen', 'images/foods/mango-kulfi.jpg', 4.6, 0, 'vegetarian'),
(1, 'Greek Yogurt Fruit Cup', 'Greek yogurt layered with seasonal fruit and crunchy granola', 180.00, 'Desserts', 1, 'mild', 240, 11.0, 28.0, 8.0, 'dessert,fruit', 'images/foods/greek-yogurt-bowl.jpg', 4.5, 1, 'high-protein,vegetarian'),
(2, 'Margherita Pizza', 'Classic tomato and mozzarella', 280.00, 'Pizza', 1, 'mild', 620, 20.0, 72.0, 22.0, 'classic,cheese', 'images/foods/pizza-photo.jpg', 4.4, 0, ''),
(2, 'Veggie Supreme Pizza', 'Loaded with fresh vegetables', 320.00, 'Pizza', 1, 'medium', 580, 18.0, 68.0, 19.0, 'vegetables,healthy', 'images/foods/pizza-photo.jpg', 4.3, 1, 'low-calorie'),
(3, 'Classic Burger', 'Juicy grilled patty with lettuce, tomato and cheese', 180.00, 'Burgers', 0, 'mild', 450, 22.0, 38.0, 24.0, 'popular,fast-food', 'images/foods/burgers-photo.jpg', 4.2, 0, ''),
(3, 'Veggie Burger', 'Plant-based patty with fresh veggies', 160.00, 'Burgers', 1, 'mild', 380, 16.0, 42.0, 12.0, 'healthy,plant-based', 'images/foods/burgers-photo.jpg', 4.1, 1, 'low-fat,vegetarian'),
(4, 'Quinoa Power Bowl', 'Quinoa, roasted veggies, tahini dressing', 280.00, 'Bowls', 1, 'mild', 420, 16.0, 48.0, 14.0, 'superfood,protein', 'images/foods/quinoa-bowl.jpg', 4.8, 1, 'high-protein,low-sugar,pcos-friendly,diabetic-friendly'),
(4, 'Green Goddess Salad', 'Kale, avocado, seeds, lemon dressing', 240.00, 'Salads', 1, 'mild', 320, 12.0, 28.0, 18.0, 'detox,fresh', 'images/foods/salads-photo.jpg', 4.7, 1, 'low-calorie,pcos-friendly,diabetic-friendly'),
(5, 'Masala Maggi', 'Spicy instant noodles with vegetables', 80.00, 'Snacks', 1, 'spicy', 280, 8.0, 42.0, 6.0, 'latenight,quick', 'images/foods/masala-maggi.jpg', 4.0, 0, ''),
(5, 'Grilled Sandwich', 'Cheese and veggie grilled sandwich', 120.00, 'Snacks', 1, 'mild', 340, 12.0, 45.0, 10.0, 'quick,latenight', 'images/foods/grilled-sandwich.jpg', 4.2, 0, '');
