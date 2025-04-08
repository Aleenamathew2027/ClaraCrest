<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Products</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
        }

        .sidebar h2 {
            margin-bottom: 30px;
            text-align: center;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar li {
            margin: 15px 0;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: #34495e;
            border-radius: 5px;
        }

        .sidebar i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            background: #f5f6fa;
        }

        .content-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Products table styles */
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

        .products-header {
            margin-bottom: 20px;
        }

        .products-header h1 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>Admin Dashboard</h2>
            <ul>
                <li>
                    <a href="admin-dashboard.php">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="registereduser.php">
                        <i class="fas fa-users"></i>
                        Users
                    </a>
                </li>
                <li>
                    <a href="admin-viewproduct.php" class="bg-blue-600">
                        <i class="fas fa-box"></i>
                        Products
                    </a>
                </li>
                <li>
                    <a href="admin-categories.php">
                        <i class="fas fa-tags"></i>
                        Categories
                    </a>
                </li>
                <li>
                    <a href="admin-order-view.php">
                        <i class="fas fa-shopping-cart"></i>
                        Orders
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-container">
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
        </div>
    </div>
</body>
</html> 