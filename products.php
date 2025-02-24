<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reuse existing styles from add-products.php */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)),
                        url('/api/placeholder/1920/1080') center/cover fixed;
            display: flex;
        }

        /* Sidebar styles (reused) */
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

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 40px 20px;
        }

        /* New styles for products page */
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .add-product-btn {
            background-color: #2ecc71;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .add-product-btn:hover {
            background-color: #27ae60;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            padding: 20px;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-details {
            padding: 15px;
        }

        .product-name {
            font-size: 1.2em;
            font-weight: 600;
            margin: 0 0 10px 0;
            color: #2c3e50;
        }

        .product-price {
            font-size: 1.1em;
            color: #27ae60;
            font-weight: 600;
            margin: 0 0 10px 0;
        }

        .product-description {
            color: #7f8c8d;
            font-size: 0.9em;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-actions {
            padding: 15px;
            border-top: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
        }

        .action-btn {
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9em;
        }

        .edit-btn {
            background-color: #3498db;
            color: white;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
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

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
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
                <a href="products.php">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
            <li>
                <a href="orders.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li>
                <a href="customers.php">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
            </li>
            <li>
                <a href="settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
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
        <div class="products-header">
            <h1>Products</h1>
            <a href="add-products.php" class="add-product-btn">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        </div>

        <?php
        if (isset($_GET['success'])) {
            echo '<div class="alert alert-success">Product added successfully!</div>';
        }
        ?>

        <div class="products-grid">
            <?php
            require_once 'dbconnect.php';
            try {
                $db = Database::getInstance();
                $conn = $db->getConnection();

                // Fetch products with category and subcategory information
                $query = "SELECT p.*, c.name as category_name, s.name as subcategory_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         LEFT JOIN subcategories s ON p.subcategory_id = s.id 
                         ORDER BY p.created_at DESC";
                
                $result = $conn->query($query);

                if ($result->num_rows > 0) {
                    while ($product = $result->fetch_assoc()) {
                        ?>
                        <div class="product-card">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-image">
                            <div class="product-details">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-price">₹<?php echo number_format($product['price'], 2); ?></p>
                                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                <p><small>Category: <?php echo htmlspecialchars($product['category_name']); ?></small></p>
                                <p><small>Subcategory: <?php echo htmlspecialchars($product['subcategory_name']); ?></small></p>
                            </div>
                            <div class="product-actions">
                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete-product.php?id=<?php echo $product['id']; ?>" 
                                   class="action-btn delete-btn" 
                                   onclick="return confirm('Are you sure you want to delete this product?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p style="text-align: center; grid-column: 1/-1;">No products found.</p>';
                }
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>
    </div>
</body>
</html> 