<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Products</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reuse existing styles for body and sidebar */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)),
                        url('/api/placeholder/1920/1080') center/cover fixed;
            display: flex;
        }

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

        /* New table styles */
        .products-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .products-table th,
        .products-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        .products-table th {
            background-color: #2c3e50;
            color: white;
            font-weight: 600;
        }

        .products-table tr:hover {
            background-color: #f8f9fa;
        }

        .product-image-small {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }

        .price {
            color: #27ae60;
            font-weight: 600;
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

            .products-table {
                display: block;
                overflow-x: auto;
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
                <a href="dashboard.php">
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
            <h1>Products Overview</h1>
        </div>

        <?php
        if (isset($_GET['success'])) {
            echo '<div class="alert alert-success">Product added successfully!</div>';
        }
        ?>

        <table class="products-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Category</th>
                    <th>Subcategory</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php
                require_once 'dbconnect.php';
                try {
                    $db = Database::getInstance();
                    $conn = $db->getConnection();

                    $query = "SELECT p.*, c.name as category_name, s.name as subcategory_name 
                             FROM products p 
                             LEFT JOIN categories c ON p.category_id = c.id 
                             LEFT JOIN subcategories s ON p.subcategory_id = s.id 
                             ORDER BY p.created_at DESC";
                    
                    $result = $conn->query($query);

                    if ($result->num_rows > 0) {
                        while ($product = $result->fetch_assoc()) {
                            ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-image-small">
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="price">₹<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['subcategory_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['description']); ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="6" style="text-align: center;">No products found.</td></tr>';
                    }
                } catch (Exception $e) {
                    echo '<tr><td colspan="6" class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html> 