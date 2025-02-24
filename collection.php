<?php
// Include database connection
require_once 'dbconnect.php';

// Fetch products from the database
function fetchProductsFromDatabase() {
    global $conn; // Use the global connection variable
    $query = "SELECT * FROM products WHERE status = 'active'";
    $result = $conn->query($query);
    
    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        return [];
    }
}

$products = fetchProductsFromDatabase();
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
            gap: 30px;
        }

        .product-card {
            background: #fff;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .wishlist-icon {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #006039;
            cursor: pointer;
            font-size: 20px;
        }

        .product-image {
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .product-image:hover {
            transform: translateY(-5px);
        }

        .product-image img {
            max-width: 100%;
            height: auto;
        }

        .product-name {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
        }

        .product-specs {
            color: #666;
            margin-bottom: 10px;
        }

        .product-price {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .price-info {
            color: #006039;
            cursor: pointer;
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
                <select class="sort-dropdown">
                    <option>Sort by: Featured</option>
                    <option>Price: Low to High</option>
                    <option>Price: High to Low</option>
                </select>
            </div>
        </div>

        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="wishlist-icon">
                        <i class="far fa-heart"></i>
                    </div>
                    <div class="product-image">
                        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>" />
                    </div>
                    <h3 class="product-name"><?php echo $product['name']; ?></h3>
                    <p class="product-specs"><?php echo $product['description']; ?></p>
                    <p class="product-price">
                        ₹ <?php echo number_format($product['price'], 2); ?>
                        <span class="price-info"><i class="fas fa-info-circle"></i></span>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
