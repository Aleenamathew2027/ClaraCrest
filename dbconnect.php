<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'claracrest1');
define('DB_PORT', 3306);

class Database {
    private $connection;
    private static $instance = null;
    
    private function __construct() {
        try {
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
            die("Database connection error: " . $e->getMessage());
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
                role ENUM('admin', 'user') DEFAULT 'user',
                address VARCHAR(255) NULL,
                city VARCHAR(100) NULL,
                district VARCHAR(100) NULL,
                state VARCHAR(100) NULL,
                pincode VARCHAR(10) NULL,
                gender ENUM('male', 'female', 'other') NULL
            )";

            if (!$this->connection->query($users_table)) {
                throw new Exception("Error creating users table: " . $this->connection->error);
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
                price DECIMAL(10,2) NOT NULL,
                image_url VARCHAR(255),
                category_id INT NOT NULL,
                subcategory_id INT NOT NULL,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
            )";

            if (!$this->connection->query($products_table)) {
                throw new Exception("Error creating products table: " . $this->connection->error);
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

            // Commit transaction
            $this->connection->commit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $this->connection->rollback();
            die("Table creation error: " . $e->getMessage());
        }
    }

    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

// Initialize database connection
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Failed to connect to database: " . $e->getMessage());
}
?>