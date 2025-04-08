<?php
session_start();
// Remove or comment out the following lines
// echo '<pre>';
// print_r($_SESSION);
// echo '</pre>';

// Include database connection
require_once 'dbconnect.php';

// Function to fetch products with filtering
function fetchProducts($filters = []) {
    global $conn;
    
    // Start with base query - updated to include both image sources
    $query = "SELECT p.*, pi.image_url AS primary_image_url 
              FROM products p 
              JOIN subcategories s ON p.subcategory_id = s.id 
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
              WHERE p.status = 'active'";
    
    $params = [];
    $types = "";
    
    // Add search filter
    if (!empty($filters['search'])) {
        $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $searchTerm = "%" . $filters['search'] . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }
    
    // Add category filter
    if (!empty($filters['category'])) {
        $query .= " AND p.category_id = ?";
        $params[] = $filters['category'];
        $types .= "i";
    }
    
    // Add brand filter
    if (!empty($filters['brand'])) {
        // Get the brand name from the selected subcategory ID
        $brand_query = "SELECT name FROM subcategories WHERE id = ?";
        $stmt = $conn->prepare($brand_query);
        $stmt->bind_param("i", $filters['brand']);
        $stmt->execute();
        $result = $stmt->get_result();
        $brand_row = $result->fetch_assoc();
        $stmt->close();
        
        if ($brand_row) {
            // Find all subcategories with this name
            $query .= " AND s.name = ?";
            $params[] = $brand_row['name'];
            $types .= "s";
        }
    }
    
    // Add price range filter
    if (!empty($filters['min_price'])) {
        $query .= " AND p.price >= ?";
        $params[] = $filters['min_price'];
        $types .= "d";
    }
    
    if (!empty($filters['max_price'])) {
        $query .= " AND p.price <= ?";
        $params[] = $filters['max_price'];
        $types .= "d";
    }
    
    // Add sorting
    if (!empty($filters['sort'])) {
        switch ($filters['sort']) {
            case 'price_low_high':
                $query .= " ORDER BY p.price ASC";
                break;
            case 'price_high_low':
                $query .= " ORDER BY p.price DESC";
                break;
            case 'newest':
                $query .= " ORDER BY p.created_at DESC";
                break;
            case 'featured':
                // Check if featured column exists
                $check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'featured'");
                if ($check_column && $check_column->num_rows > 0) {
                    $query .= " ORDER BY p.featured DESC, p.id ASC";
                } else {
                    $query .= " ORDER BY p.id ASC"; // Fallback if column doesn't exist
                }
                break;
            default:
                $query .= " ORDER BY p.id ASC"; // Default sorting
        }
    } else {
        // Default sorting - check if featured column exists
        $check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'featured'");
        if ($check_column && $check_column->num_rows > 0) {
            $query .= " ORDER BY p.featured DESC, p.id ASC";
        } else {
            $query .= " ORDER BY p.id ASC"; // Fallback if column doesn't exist
        }
    }
    
    // Debug the final query
    error_log("Final SQL Query: " . $query);
    
    // Prepare and execute the query
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            error_log("Error preparing statement: " . $conn->error);
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $result = $conn->query($query);
        if (!$result) {
            error_log("Error executing query: " . $conn->error);
            return [];
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Function to fetch all categories
function fetchCategories() {
    global $conn;
    $query = "SELECT * FROM categories WHERE status = 'active'";
    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Function to fetch unique brands (subcategories)
function fetchBrands() {
    global $conn;
    
    // Get unique subcategory names
    $query = "SELECT DISTINCT name, id FROM subcategories WHERE status = 'active' GROUP BY name ORDER BY name";
    $result = $conn->query($query);
    
    if (!$result) {
        return [];
    }
    
    // Create an array of unique brands
    $brands = [];
    while ($row = $result->fetch_assoc()) {
        // Check if this brand name already exists in our array
        $brandExists = false;
        foreach ($brands as $brand) {
            if (strtolower($brand['name']) === strtolower($row['name'])) {
                $brandExists = true;
                break;
            }
        }
        
        // If brand doesn't exist yet, add it
        if (!$brandExists) {
            $brands[] = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }
    }
    
    return $brands;
}

// Get filter parameters from URL
$filters = [
    'search' => isset($_GET['search']) ? $_GET['search'] : '',
    'category' => isset($_GET['category']) ? $_GET['category'] : '',
    'brand' => isset($_GET['brand']) ? $_GET['brand'] : '',
    'min_price' => isset($_GET['min_price']) ? $_GET['min_price'] : '',
    'max_price' => isset($_GET['max_price']) ? $_GET['max_price'] : '',
    'sort' => isset($_GET['sort']) ? $_GET['sort'] : 'featured'
];

// Fetch products based on filters
$products = fetchProducts($filters);

// Process image paths for products
foreach ($products as &$product) {
    // First check if we have a primary image from product_images table
    if (!empty($product['primary_image_url'])) {
        $product['image_url'] = $product['primary_image_url'];
    }
    // If not, use the image from products table if it exists
    else if (empty($product['image_url']) && !empty($product['image'])) {
        $product['image_url'] = $product['image'];
    }
    
    // Check if image_url exists and fix path if needed
    if (!empty($product['image_url'])) {
        // If the path doesn't start with http or /, assume it's a relative path
        if (strpos($product['image_url'], 'http') !== 0 && strpos($product['image_url'], '/') !== 0) {
            // Add a leading slash if needed
            if (strpos($product['image_url'], './') === 0) {
                $product['image_url'] = substr($product['image_url'], 1);
            } elseif (strpos($product['image_url'], 'uploads/') === 0) {
                // Path is already relative to root, no change needed
            } else {
                // Assume it's in the uploads directory
                $product['image_url'] = 'uploads/' . $product['image_url'];
            }
        }
        
        error_log("Processed image URL for product ID " . $product['id'] . ": " . $product['image_url']);
    } else {
        error_log("No image URL for product ID: " . $product['id']);
    }
}
unset($product); // Break the reference

// Add this near the products loop for debugging
foreach ($products as $product) {
    error_log("Product ID: " . $product['id'] . " - Image URL: " . $product['image_url']);
    if (!empty($product['image_url']) && !file_exists($product['image_url'])) {
        error_log("Image file does not exist: " . $product['image_url']);
    }
}

// Fetch categories for filter sidebar
$categories = fetchCategories();

// Fetch brands (subcategories) for filter sidebar
$brands = fetchBrands();

// Get current URL without query parameters for form action
$current_url = strtok($_SERVER["REQUEST_URI"], '?');

// Include header
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Collection</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .product-collection {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .collection-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .collection-header h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .view-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .view-options {
            display: flex;
            gap: 20px;
        }

        .view-option {
            color: #666;
            text-decoration: none;
            padding-bottom: 5px;
            border-bottom: 2px solid transparent;
        }

        .view-option.active {
            color: #006039;
            border-bottom-color: #006039;
        }

        .sort-dropdown {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: #f9f9f9;
            padding: 20px;
            position: relative;
            transition: all 0.3s ease;
            min-height: 340px;
        }

        .product-info {
            position: relative;
            z-index: 1;
        }

        .product-price {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 35px;
        }

        .product-actions {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .product-card:hover .product-actions {
            opacity: 1;
            visibility: visible;
        }

        .add-to-bag {
            width: 100%;
            background-color: #006039;
            color: white;
            border: none;
            padding: 8px 0;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .add-to-bag:hover {
            background-color: #004c2d;
        }

        .wishlist-icon {
            position: absolute;
            top: 15px;
            left: 15px;
            color: #666;
            cursor: pointer;
            font-size: 22px;
            z-index: 10;
            background-color: rgba(255, 255, 255, 0.8);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .wishlist-icon:hover {
            transform: scale(1.1);
        }
        
        .wishlist-icon .fas.fa-heart {
            color: #006039;
        }

        .product-image {
            text-align: center;
            margin-bottom: 15px;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .product-title {
            font-size: 16px;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .product-specs {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .price-info {
            color: #006039;
            cursor: pointer;
        }

        .filter-sidebar {
            width: 250px;
            padding: 20px;
            background: #f9f9f9;
            border-right: 1px solid #eee;
        }
        
        .main-content {
            display: flex;
            gap: 30px;
        }
        
        .filter-section {
            margin-bottom: 20px;
        }
        
        .filter-section h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .filter-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .price-inputs {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .price-inputs input {
            width: 80px;
            padding: 5px;
        }
        
        .search-bar {
            margin-bottom: 20px;
            width: 100%;
        }
        
        .search-bar input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .filter-buttons button {
            padding: 8px 16px;
            background: #006039;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .filter-buttons button.reset {
            background: #666;
        }
        
        .no-products {
            text-align: center;
            padding: 40px;
            font-size: 18px;
            color: #666;
        }
        
        .products-container {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
            
            .filter-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #eee;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .product-image {
                height: 140px;
            }
            
            .product-title {
                font-size: 14px;
            }
            
            .product-card {
                min-height: 300px;
            }
            
            .product-price {
                margin-bottom: 30px;
            }
            
            .product-actions {
                bottom: 15px;
                left: 15px;
                right: 15px;
            }
        }
        
        /* Filter button styles - updated position to bottom middle */
        .filter-button {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #006039;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 24px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 100;
        }
        
        /* Filter sidebar styles */
        .filter-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 200;
            display: none;
        }
        
        .filter-drawer {
            position: fixed;
            top: 0;
            right: -350px;
            width: 350px;
            height: 100%;
            background-color: white;
            z-index: 300;
            transition: right 0.3s ease;
            padding: 20px;
            box-sizing: border-box;
            overflow-y: auto;
        }
        
        .filter-drawer.open {
            right: 0;
        }
        
        .filter-drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .filter-drawer-header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .close-filter {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #333;
        }
        
        .filter-section {
            margin-bottom: 30px;
        }
        
        .filter-section h3 {
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .radio-option input[type="radio"] {
            margin-right: 10px;
        }

        /* Add CSS for the popup animation */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-in-out forwards;
        }
        
        .fade-out {
            animation: fadeOut 0.3s ease-in-out forwards;
        }

        /* Cart Slide Panel */
        .cart-panel {
            position: fixed;
            top: 0;
            right: -100%;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 5px rgba(0,0,0,0.1);
            transition: right 0.3s ease-in-out;
            z-index: 1000;
            overflow-y: auto;
        }

        .cart-panel.active {
            right: 0;
        }

        .cart-panel-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-panel-content {
            padding: 20px;
        }

        .cart-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .cart-item-image {
            width: 100px;
            margin-right: 15px;
        }

        .cart-item-image img {
            width: 100%;
            height: auto;
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-title {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .cart-item-price {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .cart-panel-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            position: sticky;
            bottom: 0;
            background: white;
        }

        .cart-subtotal {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .checkout-button {
            display: block;
            width: 100%;
            padding: 15px;
            background: #000;
            color: white;
            text-align: center;
            text-decoration: none;
            text-transform: uppercase;
            font-weight: bold;
        }

        .close-button {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
        }

        .shipping-text {
            text-align: center;
            color: #666;
            margin: 10px 0;
            font-size: 14px;
        }

        /* Add error handling for images */
        .product-image img:not([src]), 
        .product-image img[src=""],
        .product-image img[src="#"] {
            display: none;
        }

        /* Popup notification styles */
        .popup-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 16px;
            display: flex;
            align-items: flex-start;
            max-width: 320px;
            z-index: 1000;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }
        
        .popup-notification.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .popup-icon {
            color: #006039;
            font-size: 20px;
            margin-right: 12px;
            margin-top: 2px;
        }
        
        .popup-content {
            flex: 1;
        }
        
        .popup-title {
            font-weight: bold;
            margin-bottom: 4px;
        }
        
        .popup-message {
            color: #666;
            font-size: 14px;
        }
        
        .popup-close {
            color: #999;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            margin-left: 8px;
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="product-collection">
        <div class="collection-header">
            <h1>All models</h1>
            <div class="view-controls">
                <div class="view-options">
                    <a href="#" class="view-option active">Model view</a>
                    <a href="#" class="view-option">Grouped view</a>
                </div>
                <form method="GET" action="<?php echo $current_url; ?>" id="sort-form">
                    <!-- Preserve other filters when sorting changes -->
                    <?php if(!empty($filters['search'])): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>">
                    <?php endif; ?>
                    <?php if(!empty($filters['category'])): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($filters['category']); ?>">
                    <?php endif; ?>
                    <?php if(!empty($filters['min_price'])): ?>
                        <input type="hidden" name="min_price" value="<?php echo htmlspecialchars($filters['min_price']); ?>">
                    <?php endif; ?>
                    <?php if(!empty($filters['max_price'])): ?>
                        <input type="hidden" name="max_price" value="<?php echo htmlspecialchars($filters['max_price']); ?>">
                    <?php endif; ?>
                    
                    <select class="sort-dropdown" name="sort" onchange="document.getElementById('sort-form').submit()">
                        <option value="featured" <?php echo $filters['sort'] == 'featured' ? 'selected' : ''; ?>>Sort by: Featured</option>
                        <option value="price_low_high" <?php echo $filters['sort'] == 'price_low_high' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high_low" <?php echo $filters['sort'] == 'price_high_low' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="newest" <?php echo $filters['sort'] == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="main-content">
            <!-- Products Grid -->
            <div class="products-container">
                <?php if (empty($products)): ?>
                    <div class="no-products">
                        <p>No products found matching your criteria.</p>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="wishlist-icon" data-product-id="<?= $product['id'] ?>">
                                    <i class="far fa-heart"></i>
                                </div>
                                
                                <a href="product-details.php?id=<?= $product['id'] ?>" class="product-image">
                                    <img src="<?= htmlspecialchars($product['image_url'] ?? $product['primary_image_url'] ?? 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                                </a>
                                
                                <h3 class="product-title">
                                    <a href="product-details.php?id=<?= $product['id'] ?>">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </a>
                                </h3>
                                
                                <div class="product-specs">
                                    <?php
                                    // Display only essential details
                                    $specs = [];
                                    if (!empty($product['brand'])) $specs[] = $product['brand'];
                                    
                                    // Only add additional specs if we have them
                                    if (!empty($product['movement'])) {
                                        $specs[] = $product['movement'] . ' mm';
                                    }
                                    
                                    echo htmlspecialchars(implode(', ', $specs));
                                    ?>
                                </div>
                                
                                <div class="product-price">
                                    ₹ <?= number_format($product['price'], 0) ?>
                                </div>
                                
                                <div class="product-actions">
                                    <button class="add-to-bag" 
                                        data-product-id="<?= $product['id'] ?>"
                                        data-product-name="<?= htmlspecialchars($product['name']) ?>"
                                        data-product-price="<?= $product['price'] ?>"
                                        data-product-image="<?= htmlspecialchars($product['image_url'] ?? $product['primary_image_url'] ?? 'placeholder.jpg') ?>">
                                        Add to Bag
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Filter Button -->
        <button class="filter-button" id="openFilterBtn">
            <i class="fas fa-sliders-h"></i> Filters
        </button>
        
        <!-- Filter Overlay and Drawer -->
        <div class="filter-overlay" id="filterOverlay"></div>
        <div class="filter-drawer" id="filterDrawer">
            <div class="filter-drawer-header">
                <h2>Filters</h2>
                <button class="close-filter" id="closeFilterBtn">&times;</button>
            </div>
            
            <form method="GET" action="<?php echo $current_url; ?>">
                <!-- Sort By Section -->
                <div class="filter-section">
                    <h3>Sort by</h3>
                    <div class="filter-options">
                        <label class="radio-option">
                            <input type="radio" name="sort" value="featured" <?php echo $filters['sort'] == 'featured' ? 'checked' : ''; ?>>
                            Featured
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="sort" value="price_low_high" <?php echo $filters['sort'] == 'price_low_high' ? 'checked' : ''; ?>>
                            Price low to high
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="sort" value="price_high_low" <?php echo $filters['sort'] == 'price_high_low' ? 'checked' : ''; ?>>
                            Price high to low
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="sort" value="newest" <?php echo $filters['sort'] == 'newest' ? 'checked' : ''; ?>>
                            Newest first
                        </label>
                    </div>
                </div>
                
                <!-- Collection Section -->
                <div class="filter-section">
                    <h3>Collection</h3>
                    <div class="filter-options">
                        <?php foreach ($categories as $category): ?>
                            <label class="radio-option">
                                <input type="radio" name="category" value="<?php echo $category['id']; ?>" 
                                    <?php echo ($filters['category'] == $category['id']) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Brands Section -->
                <div class="filter-section">
                    <h3>Brands</h3>
                    <div class="filter-options">
                        <?php foreach ($brands as $brand): ?>
                            <label class="radio-option">
                                <input type="radio" name="brand" value="<?php echo $brand['id']; ?>" 
                                    <?php echo ($filters['brand'] == $brand['id']) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($brand['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Price Range Section -->
                <div class="filter-section">
                    <h3>Price Range</h3>
                    <div class="price-inputs">
                        <input type="number" name="min_price" placeholder="Min" value="<?php echo htmlspecialchars($filters['min_price']); ?>">
                        <span>to</span>
                        <input type="number" name="max_price" placeholder="Max" value="<?php echo htmlspecialchars($filters['max_price']); ?>">
                    </div>
                </div>
                
                <!-- Search (hidden, preserve value) -->
                <?php if(!empty($filters['search'])): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>">
                <?php endif; ?>
                
                <!-- Filter Buttons -->
                <div class="filter-buttons">
                    <button type="submit">Apply Filters</button>
                    <button type="button" class="reset" onclick="window.location.href='<?php echo $current_url; ?>'">Reset</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Cart Slide Panel -->
    <div class="cart-panel">
        <div class="cart-panel-header">
            <h2>SHOPPING BAG (<span class="cart-count">0</span>)</h2>
            <button class="close-button">&times;</button>
        </div>
        <div class="cart-panel-content">
            <!-- Cart items will be dynamically added here -->
        </div>
        <div class="cart-panel-footer">
            <div class="cart-subtotal">
                <span>Subtotal</span>
                <span class="subtotal-amount">₹0</span>
            </div>
            <a href="bag.php" class="checkout-button">View my shopping bag</a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter drawer functionality
            const openFilterBtn = document.getElementById('openFilterBtn');
            const closeFilterBtn = document.getElementById('closeFilterBtn');
            const filterOverlay = document.getElementById('filterOverlay');
            const filterDrawer = document.getElementById('filterDrawer');
            
            openFilterBtn.addEventListener('click', function() {
                filterOverlay.style.display = 'block';
                filterDrawer.classList.add('open');
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            });
            
            function closeFilterDrawer() {
                filterOverlay.style.display = 'none';
                filterDrawer.classList.remove('open');
                document.body.style.overflow = ''; // Restore scrolling
            }
            
            closeFilterBtn.addEventListener('click', closeFilterDrawer);
            filterOverlay.addEventListener('click', closeFilterDrawer);
            
            // Check if user is logged in using PHP
            const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
            
            // Function to update wishlist count
            function updateWishlistCount() {
                fetch('get_wishlist_count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update count in header
                            const wishlistCountElement = document.querySelector('.wishlist-count');
                            if (wishlistCountElement) {
                                wishlistCountElement.textContent = data.count;
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching wishlist count:', error));
            }
            
            // Function to update cart count
            function updateCartCount() {
                fetch('get_cart_count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update count in header
                            const cartCountElement = document.querySelector('.cart-count');
                            if (cartCountElement) {
                                cartCountElement.textContent = data.count;
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching cart count:', error));
            }
            
            // Function to show popup message
            function showPopup(message, title, icon = 'heart') {
                // Remove any existing popups
                const existingPopup = document.querySelector('.popup-notification');
                if (existingPopup) {
                    existingPopup.remove();
                }
                
                // Create popup elements
                const popup = document.createElement('div');
                popup.className = 'popup-notification';
                
                const iconClass = icon === 'heart' ? 'fas fa-heart' : 'fas fa-shopping-bag';
                
                const popupHTML = `
                    <div class="popup-icon">
                        <i class="${iconClass}"></i>
                    </div>
                    <div class="popup-content">
                        <div class="popup-title">${title}</div>
                        <div class="popup-message">${message}</div>
                    </div>
                    <button class="popup-close">&times;</button>
                `;
                
                popup.innerHTML = popupHTML;
                document.body.appendChild(popup);
                
                // Add click event to close button
                popup.querySelector('.popup-close').addEventListener('click', () => {
                    popup.classList.remove('show');
                    setTimeout(() => popup.remove(), 300);
                });
                
                // Show popup with animation
                setTimeout(() => {
                    popup.classList.add('show');
                }, 10);
                
                // Auto-hide popup after 3 seconds
                setTimeout(() => {
                    popup.classList.remove('show');
                    setTimeout(() => popup.remove(), 300);
                }, 3000);
            }

            // Wishlist functionality
            document.querySelectorAll('.wishlist-icon').forEach(icon => {
                icon.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent any default action
                    
                    const productId = this.getAttribute('data-product-id');
                    const heartIcon = this.querySelector('i');
                    
                    if (isLoggedIn) {
                        // Toggle heart appearance immediately for better UX
                        const isInWishlist = heartIcon.classList.contains('fas');
                        
                        if(isInWishlist) {
                            heartIcon.classList.replace('fas', 'far');
                            heartIcon.style.color = '';
                        } else {
                            heartIcon.classList.replace('far', 'fas');
                            heartIcon.style.color = '#006039';
                        }
                        
                        // Send AJAX request to update wishlist
                        fetch('update_wishlist.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'product_id=' + productId + '&action=' + (isInWishlist ? 'remove' : 'add')
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok: ' + response.status);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if(data.success) {
                                console.log(data.message); // Log success message
                                
                                // Show popup message for add action
                                if (!isInWishlist) {
                                    showPopup('This watch has been added to your favourites.', 'Watch saved', 'heart');
                                }
                                
                                // Update wishlist count
                                updateWishlistCount();
                            } else {
                                // Revert the heart if there was an error
                                if(isInWishlist) {
                                    heartIcon.classList.replace('far', 'fas');
                                    heartIcon.style.color = '#006039';
                                } else {
                                    heartIcon.classList.replace('fas', 'far');
                                    heartIcon.style.color = '';
                                }
                                console.error('Server error:', data.message);
                                alert('Error updating wishlist: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            // Revert the heart on error
                            if(isInWishlist) {
                                heartIcon.classList.replace('far', 'fas');
                                heartIcon.style.color = '#006039';
                            } else {
                                heartIcon.classList.replace('fas', 'far');
                                heartIcon.style.color = '';
                            }
                            alert('Error updating wishlist: ' + error.message);
                        });
                    } else {
                        alert('Please log in to add items to your wishlist.');
                    }
                });
            });
            
            // Function to show cart panel
            function showCartPanel(newItem) {
                const cartPanel = document.querySelector('.cart-panel');
                const cartContent = cartPanel.querySelector('.cart-panel-content');
                const cartCount = cartPanel.querySelector('.cart-count');
                const cartSubtotal = cartPanel.querySelector('.cart-subtotal');
                
                // Add new item to cart panel
                if (newItem) {
                    const itemHTML = `
                        <div class="cart-item">
                            <div class="cart-item-image">
                                <img src="${newItem.image}" alt="${newItem.name}">
                            </div>
                            <div class="cart-item-details">
                                <h3 class="cart-item-title">${newItem.name}</h3>
                                <p class="cart-item-price">₹${newItem.price.toLocaleString()}</p>
                                <p>Quantity: ${newItem.quantity}</p>
                            </div>
                        </div>
                    `;
                    cartContent.insertAdjacentHTML('afterbegin', itemHTML);
                }
                
                // Update cart count and show panel
                fetch('get_cart_count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            cartCount.textContent = data.count;
                        }
                    });
                    
                // Update subtotal
                fetch('get_cart_total.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            cartSubtotal.textContent = '₹' + data.total.toLocaleString();
                        }
                    });
                
                cartPanel.classList.add('active');
            }

            // Close cart panel
            document.querySelector('.cart-panel .close-button').addEventListener('click', () => {
                document.querySelector('.cart-panel').classList.remove('active');
            });

            // Update Add to Bag click handler
            document.querySelectorAll('.add-to-bag').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    if (!isLoggedIn) {
                        alert('Please log in to add items to your bag.');
                        return;
                    }

                    const productId = this.getAttribute('data-product-id');
                    const productName = this.getAttribute('data-product-name');
                    const productPrice = parseFloat(this.getAttribute('data-product-price'));
                    const productImage = this.getAttribute('data-product-image');
                    
                    // Show loading state
                    const originalText = this.textContent;
                    this.textContent = 'Adding...';
                    this.disabled = true;

                    fetch('add_to_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'product_id=' + productId + '&quantity=1'
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.textContent = originalText;
                        this.disabled = false;
                        
                        if (data.success) {
                            // Show cart panel with new item
                            showCartPanel({
                                name: productName,
                                price: productPrice,
                                image: productImage,
                                quantity: 1
                            });
                        } else {
                            alert('Error adding item to bag: ' + data.error);
                        }
                    })
                    .catch(error => {
                        this.textContent = originalText;
                        this.disabled = false;
                        alert('Error adding to bag: ' + error.message);
                    });
                });
            });
            
            // Initialize counts on page load
            if (isLoggedIn) {
                updateWishlistCount();
                updateCartCount();
            }

            // Handle image loading errors
            document.querySelectorAll('.product-image img').forEach(img => {
                img.onerror = function() {
                    this.src = 'placeholder.jpg'; // Replace with your placeholder image
                    console.log('Image failed to load:', this.src);
                };
            });
        });
    </script>
</body>
</html>
