<?php
// Database configuration
define('DB_SERVER', 'localhost');  // or try '127.0.0.1' if 'localhost' doesn't work
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'claracrest1');
define('DB_PORT', 3306);

class Database {
    private $connection;
    private static $instance = null;
    
    private function __construct() {
        try {
            // First, check if MySQL service is running
            $socket = @fsockopen(DB_SERVER, DB_PORT, $errno, $errstr, 5);
            if (!$socket) {
                throw new Exception("MySQL server is not running. Please start MySQL in XAMPP Control Panel.");
            }
            @fclose($socket);

            // Attempt database connection
            $this->connection = new mysqli(
                DB_SERVER, 
                DB_USERNAME, 
                DB_PASSWORD, 
                DB_NAME, 
                DB_PORT
            );

            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }

            $this->connection->set_charset("utf8mb4");
            $this->createTables();
            
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage() . 
                "<br>Please ensure: <br>" .
                "1. XAMPP is running<br>" .
                "2. MySQL service is started<br>" .
                "3. Database 'claracrest1' exists");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    private function createTables() {
        try {
            // Start transaction
            $this->connection->begin_transaction();

            // Create users table if it doesn't exist
            $users_table = "CREATE TABLE IF NOT EXISTS users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                fullname VARCHAR(255) NOT NULL,
                username VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                phone VARCHAR(20) NOT NULL,
                password VARCHAR(255) NOT NULL,
                reset_token VARCHAR(255) NULL,
                reset_expiry DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                role ENUM('admin', 'user', 'manager') DEFAULT 'user',
                status BOOLEAN DEFAULT TRUE,
                address VARCHAR(255) NULL,
                city VARCHAR(100) NULL,
                district VARCHAR(100) NULL,
                state VARCHAR(100) NULL,
                pincode VARCHAR(10) NULL,
                gender ENUM('male', 'female', 'other') NULL,
                profile_image VARCHAR(255) NULL
            )";

            if (!$this->connection->query($users_table)) {
                throw new Exception("Error creating users table: " . $this->connection->error);
            }

            // Add status column if it doesn't exist
            $check_status_column = "SHOW COLUMNS FROM users LIKE 'status'";
            $result = $this->connection->query($check_status_column);
            if ($result->num_rows === 0) {
                $add_status_column = "ALTER TABLE users ADD COLUMN status BOOLEAN DEFAULT TRUE";
                if (!$this->connection->query($add_status_column)) {
                    throw new Exception("Error adding status column: " . $this->connection->error);
                }
            }

            // Create login_logs table if it doesn't exist
            $login_logs = "CREATE TABLE IF NOT EXISTS login_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";

            if (!$this->connection->query($login_logs)) {
                throw new Exception("Error creating login_logs table: " . $this->connection->error);
            }

            // Create categories table if it doesn't exist
            $categories_table = "CREATE TABLE IF NOT EXISTS categories (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";

            if (!$this->connection->query($categories_table)) {
                throw new Exception("Error creating categories table: " . $this->connection->error);
            }

            // Create subcategories table if it doesn't exist
            $subcategories_table = "CREATE TABLE IF NOT EXISTS subcategories (
                id INT PRIMARY KEY AUTO_INCREMENT,
                category_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            )";

            if (!$this->connection->query($subcategories_table)) {
                throw new Exception("Error creating subcategories table: " . $this->connection->error);
            }

            // Create products table if it doesn't exist
            $products_table = "CREATE TABLE IF NOT EXISTS products (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                brand VARCHAR(100) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                stock_quantity INT NOT NULL DEFAULT 0,
                warranty VARCHAR(100),
                image_url VARCHAR(255),
                category_id INT NOT NULL,
                subcategory_id INT NOT NULL,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                watch_type VARCHAR(100) NOT NULL,
                movement VARCHAR(100) NOT NULL,
                water_resistance VARCHAR(50),
                dial_color VARCHAR(50),
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
            )";

            if (!$this->connection->query($products_table)) {
                throw new Exception("Error creating products table: " . $this->connection->error);
            }

            // Create product_images table for multiple images
            $product_images_table = "CREATE TABLE IF NOT EXISTS product_images (
                id INT PRIMARY KEY AUTO_INCREMENT,
                product_id INT NOT NULL,
                image_url VARCHAR(255) NOT NULL,
                is_primary BOOLEAN DEFAULT FALSE,
                order_number INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )";

            if (!$this->connection->query($product_images_table)) {
                throw new Exception("Error creating product_images table: " . $this->connection->error);
            }

            // Now create admin user if it doesn't exist
            $admin_email = 'aleenamathew305@gmail.com';
            $check_admin = "SELECT id FROM users WHERE email = ?";
            $stmt = $this->connection->prepare($check_admin);
            
            if (!$stmt) {
                throw new Exception("Error preparing admin check statement: " . $this->connection->error);
            }

            $stmt->bind_param("s", $admin_email);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if ($result->num_rows == 0) {
                $admin_password = password_hash("admin@123", PASSWORD_DEFAULT);
                $admin_name = "Admin User";
                $admin_phone = "1234567890";
                
                $insert_admin = "INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, 'admin')";
                $stmt = $this->connection->prepare($insert_admin);
                
                if (!$stmt) {
                    throw new Exception("Error preparing admin insert statement: " . $this->connection->error);
                }

                $stmt->bind_param("ssss", $admin_name, $admin_email, $admin_phone, $admin_password);
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting admin user: " . $stmt->error);
                }
                $stmt->close();
            }

            // Create cart_items table if it doesn't exist
            $cart_items_table = "CREATE TABLE IF NOT EXISTS cart_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )";

            if (!$this->connection->query($cart_items_table)) {
                throw new Exception("Error creating cart_items table: " . $this->connection->error);
            }

            // Create wishlist table if it doesn't exist
            $wishlist_table = "CREATE TABLE IF NOT EXISTS wishlist (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY user_product (user_id, product_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )";

            if (!$this->connection->query($wishlist_table)) {
                throw new Exception("Error creating wishlist table: " . $this->connection->error);
            }

            // Create payments table if it doesn't exist
            $payments_table = "CREATE TABLE IF NOT EXISTS payments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                order_id VARCHAR(255) NOT NULL,
                razorpay_payment_id VARCHAR(255),
                amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )";

            if (!$this->connection->query($payments_table)) {
                throw new Exception("Error creating payments table: " . $this->connection->error);
            }

            // Create orders table if it doesn't exist
            $orders_table = "CREATE TABLE IF NOT EXISTS orders (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                order_id VARCHAR(255) NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                price_at_time DECIMAL(10,2) NOT NULL,
                order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (product_id) REFERENCES products(id)
            )";

            if (!$this->connection->query($orders_table)) {
                throw new Exception("Error creating orders table: " . $this->connection->error);
            }

            // Create insurance_payment table if it doesn't exist
            $sql = "CREATE TABLE IF NOT EXISTS insurance_payment (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) NOT NULL,
                plan_type VARCHAR(50) NOT NULL,
                plan_amount DECIMAL(10,2) NOT NULL,
                payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expiry_date DATE NOT NULL,
                payment_method VARCHAR(50) NOT NULL,
                transaction_id VARCHAR(100),
                payment_status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
                watch_details TEXT,
                insurance_policy_number VARCHAR(50),
                is_renewal TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

            if (!$this->connection->query($sql)) {
                throw new Exception("Error creating insurance_payment table: " . $this->connection->error);
            }

            // Create product_reviews table if it doesn't exist
            $product_reviews_table = "CREATE TABLE IF NOT EXISTS product_reviews (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                order_id VARCHAR(255) NOT NULL,
                rating INT NOT NULL,
                review_text TEXT,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )";

            if (!$this->connection->query($product_reviews_table)) {
                throw new Exception("Error creating product_reviews table: " . $this->connection->error);
            }

            // Create review_replies table if it doesn't exist
            $review_replies_table = "CREATE TABLE IF NOT EXISTS review_replies (
                id INT PRIMARY KEY AUTO_INCREMENT,
                review_id INT NOT NULL,
                user_id INT NOT NULL,
                reply_text TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";

            if (!$this->connection->query($review_replies_table)) {
                throw new Exception("Error creating review_replies table: " . $this->connection->error);
            }

            // Add this code to create the necessary tables if they don't exist
            $review_tables_sql = "
            -- For reviews
            CREATE TABLE IF NOT EXISTS reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                order_id INT NOT NULL,
                rating INT NOT NULL,
                review_text TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (product_id) REFERENCES products(id),
                FOREIGN KEY (order_id) REFERENCES orders(order_id)
            );

            -- For review replies
            CREATE TABLE IF NOT EXISTS review_replies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                review_id INT NOT NULL,
                user_id INT NOT NULL,
                reply_text TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (review_id) REFERENCES reviews(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
            ";

            // Execute the SQL to create tables
            $this->connection->multi_query($review_tables_sql);
            while ($this->connection->more_results() && $this->connection->next_result()) {
                // Clear any result sets
                if ($result = $this->connection->store_result()) {
                    $result->free();
                }
            }

            // Commit transaction
            $this->connection->commit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $this->connection->rollback();
            die("Table creation error: " . $e->getMessage());
        }
    }

    public function __destruct() {
        // Remove or comment out the connection closing
        // if ($this->connection) {
        //     $this->connection->close();
        // }
    }
}

// Initialize database connection with better error handling
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Failed to connect to database: " . $e->getMessage() . 
        "<br>Please check your XAMPP services and ensure MySQL is running.");
}

// Don't automatically access user_id - remove these lines
// session_start();
// $user_id = $_SESSION['user_id']; // This line is causing the error
?>