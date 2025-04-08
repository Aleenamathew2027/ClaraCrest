<?php
session_start(); // Make sure this is at the very top of the file
require_once 'dbconnect.php';

// Check if user is logged in and has manager role
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || 
    ($_SESSION['role'] !== 'manager' && $_SESSION['user_id'] !== 'manager')) {
    // Add debugging
    error_log("Access attempt to add-products.php - Session details: " . print_r($_SESSION, true));
    header("Location: login.php");
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Debug session
error_log("Current session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));

// Add this near the top of your file, after session_start()
$upload_path = 'uploads/products/';
if (!file_exists($upload_path)) {
    mkdir($upload_path, 0777, true);
}
chmod($upload_path, 0777);

// Fetch categories for the dropdown
$categories_query = "SELECT * FROM categories";
$categories_result = $conn->query($categories_query);

// Fetch subcategories for the dropdown
$subcategories_query = "SELECT * FROM subcategories";
$subcategories_result = $conn->query($subcategories_query);

// Initialize variables for error/success messages
$message = '';
$messageType = '';

// Add this function at the top of the file after the database connection
function isProductDuplicate($conn, $name, $brand) {
    $sql = "SELECT id FROM products WHERE LOWER(name) = LOWER(?) AND LOWER(brand) = LOWER(?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $name, $brand);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Debug connection status
        error_log("Database connection status: " . ($conn->ping() ? "connected" : "disconnected"));
        
        // Validate and sanitize input data
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $brand = trim($_POST['brand']);
        $watch_type = trim($_POST['watch_type']);
        $movement = trim($_POST['movement']);
        $water_resistance = trim($_POST['water_resistance']);
        $dial_color = trim($_POST['dial_color']);
        $price = floatval($_POST['price']);
        $stock_quantity = intval($_POST['stock_quantity']);
        $warranty = trim($_POST['warranty']);
        $category_id = intval($_POST['category_id']);
        $subcategory_id = intval($_POST['subcategory_id']);

        // Enhanced validation
        $errors = [];

        // Check for empty required fields
        if (empty($name)) $errors[] = "Product name is required.";
        if (empty($description)) $errors[] = "Product description is required.";
        if (empty($brand)) $errors[] = "Brand is required.";
        if (empty($watch_type)) $errors[] = "Watch type is required.";
        if (empty($movement)) $errors[] = "Movement is required.";

        // Validate price and stock
        if ($price <= 0) $errors[] = "Price must be greater than 0.";
        if ($stock_quantity < 0) $errors[] = "Stock quantity cannot be negative.";
        if ($stock_quantity > 10000) $errors[] = "Stock quantity cannot exceed 10,000 units.";

        // Validate name length
        if (strlen($name) > 255) $errors[] = "Product name is too long (maximum 255 characters).";
        if (strlen($name) < 3) $errors[] = "Product name is too short (minimum 3 characters).";

        // Validate price range
        if ($price > 1000000) $errors[] = "Price cannot exceed ₹1,000,000.";

        // Check for duplicate product
        if (isProductDuplicate($conn, $name, $brand)) {
            $errors[] = "A product with this name and brand already exists.";
        }

        // Validate category and subcategory
        if ($category_id <= 0) $errors[] = "Please select a valid category.";
        if ($subcategory_id <= 0) $errors[] = "Please select a valid subcategory.";

        // Validate file upload
        if (!isset($_FILES['main_image']) || $_FILES['main_image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Main product image is required.";
        } else {
            // Validate image file type and size
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['main_image']['type'], $allowed_types)) {
                $errors[] = "Main image must be JPG, PNG, or GIF format.";
            }
            if ($_FILES['main_image']['size'] > $max_size) {
                $errors[] = "Main image size must be less than 5MB.";
            }
        }

        // If there are any validation errors, throw an exception
        if (!empty($errors)) {
            throw new Exception(implode("<br>", $errors));
        }

        // Handle main product image upload
        if (!isset($_FILES['main_image']) || empty($_FILES['main_image']['name'])) {
            throw new Exception("Main product image is required.");
        }

        // Process main image
        $main_image_path = '';
        if ($_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $file_info = pathinfo($_FILES['main_image']['name']);
            $extension = strtolower($file_info['extension']);
            
            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($extension, $allowed_types)) {
                throw new Exception("Main image must be JPG, JPEG, PNG, or GIF format.");
            }

            $new_filename = 'main_' . uniqid() . '.' . $extension;
            $main_image_path = $upload_path . $new_filename;

            if (!move_uploaded_file($_FILES['main_image']['tmp_name'], $main_image_path)) {
                throw new Exception("Failed to upload main product image.");
            }
        } else {
            throw new Exception("Error uploading main image: " . $_FILES['main_image']['error']);
        }

        // Check for additional images (optional)
        $has_additional_images = isset($_FILES['additional_images']) && !empty($_FILES['additional_images']['name'][0]);

        // Start transaction
        $conn->begin_transaction();
        error_log("Transaction started");

        // Alternative approach - directly insert values
        $sql = "INSERT INTO products (
            name, description, brand, watch_type, movement, 
            water_resistance, dial_color, price, stock_quantity, 
            warranty, category_id, subcategory_id, status, image_url
        ) VALUES (
            '" . $conn->real_escape_string($name) . "',
            '" . $conn->real_escape_string($description) . "',
            '" . $conn->real_escape_string($brand) . "',
            '" . $conn->real_escape_string($watch_type) . "',
            '" . $conn->real_escape_string($movement) . "',
            '" . $conn->real_escape_string($water_resistance) . "',
            '" . $conn->real_escape_string($dial_color) . "',
            " . $price . ",
            " . $stock_quantity . ",
            '" . $conn->real_escape_string($warranty) . "',
            " . $category_id . ",
            " . $subcategory_id . ",
            'active',
            '" . $conn->real_escape_string($main_image_path) . "'
        )";
        
        error_log("Executing SQL: " . $sql);
        
        if (!$conn->query($sql)) {
            throw new Exception("Insert failed: " . $conn->error);
        }
        
        $product_id = $conn->insert_id;
        error_log("Product inserted with ID: " . $product_id);

        // Handle additional image uploads if present
        if ($has_additional_images) {
            // First check if order_number column exists
            $check_column = "SHOW COLUMNS FROM product_images LIKE 'order_number'";
            $column_result = $conn->query($check_column);
            
            // If column doesn't exist, add it
            if ($column_result->num_rows === 0) {
                error_log("Adding missing order_number column to product_images table");
                $add_column = "ALTER TABLE product_images ADD COLUMN order_number INT DEFAULT 0";
                if (!$conn->query($add_column)) {
                    throw new Exception("Failed to add order_number column: " . $conn->error);
                }
                error_log("Successfully added order_number column");
            }
            
            $additional_images_uploaded = false;
            $order_number = 1; // Start with 1 since 0 is for the main image
            
            foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_info = pathinfo($_FILES['additional_images']['name'][$key]);
                    $extension = strtolower($file_info['extension']);
                    
                    // Validate file type
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array($extension, $allowed_types)) {
                        continue;
                    }

                    $new_filename = 'additional_' . uniqid() . '.' . $extension;
                    $image_path = $upload_path . $new_filename;

                    if (move_uploaded_file($tmp_name, $image_path)) {
                        $is_primary = 0; // None of the additional images are primary
                        $image_sql = "INSERT INTO product_images (product_id, image_url, is_primary, order_number) 
                                      VALUES (
                                          " . intval($product_id) . ", 
                                          '" . $conn->real_escape_string($image_path) . "', 
                                          " . $is_primary . ", 
                                          " . $order_number . "
                                      )";
                        
                        if (!$conn->query($image_sql)) {
                            throw new Exception("Error inserting additional image: " . $conn->error);
                        }
                        $order_number++; // Increment order number for next image
                        $additional_images_uploaded = true;
                    }
                }
            }
            
            if (!$additional_images_uploaded && count($_FILES['additional_images']['name']) > 0) {
                error_log("Warning: No valid additional images were uploaded.");
            }
        }

        $conn->commit();
        error_log("Transaction committed successfully");
        $message = "Product added successfully!";
        $messageType = "success";

    } catch (Exception $e) {
        error_log("Error in add-products.php: " . $e->getMessage());
        if (isset($conn) && !$conn->connect_error) {
            $conn->rollback();
            error_log("Transaction rolled back");
        }
        $message = $e->getMessage();
        $messageType = "danger";
    }
}
?>

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

        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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

        .sidebar-menu .logout {
            margin-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
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

            .sidebar h2,
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
        <h2>Manager Dashboard</h2>
        <ul class="sidebar-menu">
            <li>
                <a href="manager-dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="managerreguser.php">
                    <i class="fas fa-user-plus"></i>
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
                <a href="add-products.php">
                    <i class="fas fa-plus"></i>
                    <span>Add Product</span>
                </a>
            </li>
            <li>
                <a href="products.php">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
            <li>
                <a href="manager-vieworders.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>View Orders</span>
                </a>
            </li>
            <li>
                <a href="manager-review.php">
                    <i class="fas fa-star"></i>
                    <span>Reviews</span>
                </a>
            </li>
            <li>
                <a href="manager-payment.php">
                    <i class="fas fa-credit-card"></i>
                    <span>Payment Details</span>
                </a>
            </li>
            <li class="logout">
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
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            

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
        <label for="brand">Brand:</label>
        <input type="text" id="brand" name="brand" required>
    </div>

    <div class="form-group">
        <label for="watch_type">Watch Type:</label>
        <input type="text" id="watch_type" name="watch_type" required>
    </div>

    <div class="form-group">
        <label for="movement">Movement:</label>
        <input type="text" id="movement" name="movement" required>
    </div>

    <div class="form-group">
        <label for="water_resistance">Water Resistance:</label>
        <input type="text" id="water_resistance" name="water_resistance">
    </div>

    <div class="form-group">
        <label for="dial_color">Dial Color:</label>
        <input type="text" id="dial_color" name="dial_color">
    </div>

    <div class="form-group">
        <label for="price">Price:</label>
        <input type="number" id="price" name="price" step="0.01" required>
    </div>

    <div class="form-group">
        <label for="stock_quantity">Stock Quantity:</label>
        <input type="number" id="stock_quantity" name="stock_quantity" required min="0">
    </div>

    <div class="form-group">
        <label for="warranty">Warranty:</label>
        <input type="text" id="warranty" name="warranty">
    </div>

    <div class="form-group">
        <label for="main_image">Main Product Image:</label>
        <input type="file" id="main_image" name="main_image" accept="image/*" required>
        <small>This image will be stored in the products table and displayed in collections.</small>
    </div>

    <div class="form-group">
        <label for="additional_images">Additional Product Images:</label>
        <input type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple>
        <small>You can select multiple additional images. These will be stored in the product_images table.</small>
    </div>

    <div class="form-group">
        <label for="category">Category:</label>
        <select id="category" name="category_id" required>
            <option value="">Select a category</option>
            <?php
            $result = $conn->query("SELECT id, name FROM categories WHERE status = 'active'");
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . htmlspecialchars($row['id']) . "'>" . 
                     htmlspecialchars($row['name']) . "</option>";
            }
            ?>
        </select>
    </div>

    <div class="form-group">
        <label for="subcategory">Subcategory:</label>
        <select id="subcategory" name="subcategory_id" required>
            <option value="">Select a category first</option>
        </select>
    </div>

    <input type="submit" value="Add Product">
</form>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#category').change(function() {
                var categoryId = $(this).val();
                if (categoryId) {
                    $.ajax({
                        url: 'fetch_subcategories.php',
                        type: 'POST',
                        data: {category_id: categoryId},
                        success: function(response) {
                            $('#subcategory').html(response);
                        }
                    });
                } else {
                    $('#subcategory').html('<option value="">Select a category first</option>');
                }
            });

            // Add form validation
            $('form').submit(function(e) {
                let errors = [];
                
                // Validate product name
                const name = $('#name').val().trim();
                if (name.length < 3) {
                    errors.push("Product name must be at least 3 characters long");
                }

                // Validate price
                const price = parseFloat($('#price').val());
                if (isNaN(price) || price <= 0) {
                    errors.push("Please enter a valid price greater than 0");
                }
                if (price > 1000000) {
                    errors.push("Price cannot exceed ₹1,000,000");
                }

                // Validate stock quantity
                const stock = parseInt($('#stock_quantity').val());
                if (isNaN(stock) || stock < 0) {
                    errors.push("Stock quantity cannot be negative");
                }
                if (stock > 10000) {
                    errors.push("Stock quantity cannot exceed 10,000 units");
                }

                // Validate image
                const mainImage = $('#main_image')[0].files[0];
                if (mainImage) {
                    const fileSize = mainImage.size / 1024 / 1024; // in MB
                    if (fileSize > 5) {
                        errors.push("Main image size must be less than 5MB");
                    }
                    
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!validTypes.includes(mainImage.type)) {
                        errors.push("Main image must be JPG, PNG, or GIF format");
                    }
                }

                // If there are validation errors, prevent form submission
                if (errors.length > 0) {
                    e.preventDefault();
                    alert(errors.join("\n"));
                }
            });
        });
    </script>
</body>
</html>