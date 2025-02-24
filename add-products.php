<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Existing styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)),
                        url('/api/placeholder/1920/1080') center/cover fixed;
            display: flex;
        }

        /* Sidebar styles */
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            color: white;
            padding-top: 20px;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main content styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        form {
            background: #ffffff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
        }

        input[type="text"],
        input[type="number"],
        input[type="file"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            margin-bottom: 5px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            border-color: #3498db;
            outline: none;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input[type="file"] {
            padding: 10px;
            background: #f8f9fa;
        }

        input[type="submit"] {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #27ae60;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
            }

            .sidebar-header {
                padding: 10px;
            }

            .sidebar-header h2,
            .sidebar-menu span {
                display: none;
            }

            .sidebar-menu a {
                padding: 15px;
                justify-content: center;
            }

            .sidebar-menu i {
                margin: 0;
            }

            .main-content {
                margin-left: 60px;
            }
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
        </div>
        <ul class="sidebar-menu">
            
            <li>
                <a href="manager-dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="managerreguser.php">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <li>
                <a href="manage-categories.php">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
            </li>
        
            <li>
                <a href="products.php">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
            <li>
                <a href="orders.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>View Orders</span>
                </a>
            </li>
            <li>
                <a href="most-selling.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Most Selling Products</span>
                </a>
            </li>
            <li>
                <a href="payment-details.php">
                    <i class="fas fa-credit-card"></i>
                    <span>Payment Details</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h1>Add New Product</h1>
            <?php
            require_once 'dbconnect.php';
            $db = Database::getInstance();
            $conn = $db->getConnection();

            // First, let's check if we need to insert default categories and subcategories
            try {
                // Check if categories table is empty
                $result = $conn->query("SELECT COUNT(*) as count FROM categories");
                $row = $result->fetch_assoc();
                
                if ($row['count'] == 0) {
                    // Insert default category
                    $stmt = $conn->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
                    $category_name = "Default Category";
                    $category_slug = "default-category";
                    $stmt->bind_param("ss", $category_name, $category_slug);
                    $stmt->execute();
                    $category_id = $conn->insert_id;
                    $stmt->close();
                    
                    // Insert default subcategory
                    $stmt = $conn->prepare("INSERT INTO subcategories (category_id, name, slug) VALUES (?, ?, ?)");
                    $subcategory_name = "Default Subcategory";
                    $subcategory_slug = "default-subcategory";
                    $stmt->bind_param("iss", $category_id, $subcategory_name, $subcategory_slug);
                    $stmt->execute();
                    $stmt->close();
                }
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error setting up default categories: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            // Add form processing logic
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                try {
                    // Validate and sanitize input data
                    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
                    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
                    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
                    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
                    
                    // Validate required fields
                    if (empty($name) || empty($description) || $price === false || empty($category_id)) {
                        throw new Exception("All fields are required and must be valid.");
                    }
                    
                    // Handle file upload
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $file_info = pathinfo($_FILES['image']['name']);
                        $extension = strtolower($file_info['extension']);
                        
                        // Validate file type
                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                        if (!in_array($extension, $allowed_types)) {
                            throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowed_types));
                        }
                        
                        // Generate unique filename
                        $new_filename = uniqid() . '.' . $extension;
                        $upload_path = 'uploads/products/';
                        
                        // Create directory if it doesn't exist
                        if (!file_exists($upload_path)) {
                            mkdir($upload_path, 0777, true);
                        }
                        
                        // Move uploaded file
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path . $new_filename)) {
                            throw new Exception("Failed to upload image.");
                        }
                        
                        $image_url = $upload_path . $new_filename;
                    } else {
                        throw new Exception("Image upload is required.");
                    }
                    
                    // Get the subcategory_id based on the selected category
                    $stmt = $conn->prepare("SELECT id FROM subcategories WHERE category_id = ? LIMIT 1");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param("i", $category_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $subcategory = $result->fetch_assoc();
                    $stmt->close();
                    
                    if (!$subcategory) {
                        throw new Exception("No subcategory found for the selected category.");
                    }
                    
                    $subcategory_id = $subcategory['id'];
                    
                    // Now proceed with product insertion
                    $stmt = $conn->prepare("INSERT INTO products (name, description, price, image_url, category_id, subcategory_id) VALUES (?, ?, ?, ?, ?, ?)");
                    
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param("ssdsii", $name, $description, $price, $image_url, $category_id, $subcategory_id);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                    
                    $stmt->close();
                    
                    echo '<div class="alert alert-success">Product added successfully!</div>';
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
            ?>

            <!-- Modify form action to post to same page -->
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Product Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="description">Product Description:</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Price:</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="image">Image Upload:</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                </div>

                <div class="form-group">
                    <label for="category">Select Category:</label>
                    <select id="category" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php
                        try {
                            $result = $conn->query("SELECT DISTINCT c.id, c.name 
                                                  FROM categories c 
                                                  INNER JOIN subcategories s ON c.id = s.category_id");
                            if ($result) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['name']) . "</option>";
                                }
                            }
                        } catch (Exception $e) {
                            echo "<option value=''>Error loading categories</option>";
                        }
                        ?>
                    </select>
                </div>

                <input type="submit" value="Add Product">
            </form>
        </div>
    </div>
</body>
</html>