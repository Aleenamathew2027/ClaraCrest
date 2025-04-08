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

        .products-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
        }

        .products-table th,
        .products-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
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

        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            margin-right: 5px;
            display: inline-block;
        }

        .edit-btn {
            background-color: #3498db;
            color: white;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }

        .status-active {
            color: #2ecc71;
            font-weight: bold;
        }

        .status-inactive {
            color: #e74c3c;
            font-weight: bold;
        }

        /* Search and filter container */
        .table-controls {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-box {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 300px;
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
                <a href="add-products.php">
                    <i class="fas fa-box"></i>
                    <span>AddProducts</span>
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
            <h1>Products Management</h1>
            <a href="add-products.php" class="add-product-btn">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        </div>

        <?php
        if (isset($_GET['success'])) {
            $message = '';
            switch ($_GET['success']) {
                case 'updated':
                    $message = 'Product updated successfully!';
                    break;
                case 'deleted':
                    $message = 'Product deleted successfully!';
                    break;
                default:
                    $message = 'Operation completed successfully!';
            }
            echo '<div class="alert alert-success">' . $message . '</div>';
        }
        ?>

        <div class="table-controls">
            <input type="text" id="searchInput" class="search-box" placeholder="Search products...">
        </div>

        <table class="products-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Subcategory</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
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
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['subcategory_name']); ?></td>
                                <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <span class="status-<?php echo strtolower($product['status']); ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                       class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete-product.php?id=<?php echo $product['id']; ?>" 
                                       class="action-btn delete-btn" 
                                       onclick="return confirm('Are you sure you want to delete this product?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="7" style="text-align: center;">No products found.</td></tr>';
                    }
                } catch (Exception $e) {
                    echo '<tr><td colspan="7" class="alert alert-danger">Error: ' . 
                         htmlspecialchars($e->getMessage()) . '</td></tr>';
                }
                ?>
            </tbody>
        </table>

        <div class="flex absolute bottom-0 right-0">
            <form id="image-upload-form" action="" method="POST" enctype="multipart/form-data">
                <label class="bg-orange-500 text-white p-2 rounded-full cursor-pointer hover:bg-orange-600 mr-2">
                    <input 
                        type="file" 
                        class="hidden" 
                        name="user_image"
                        accept="image/*"
                        id="profile-image-input"
                    />
                    <i class="fas fa-camera"></i>
                </label>
                <button type="submit" id="submit-image" class="hidden">Upload</button>
            </form>
            <?php if(!empty($user['profile_image'])): ?>
            <form action="" method="POST" onsubmit="return confirm('Are you sure you want to remove your profile image?');">
                <input type="hidden" name="remove_profile_image" value="yes">
                <button type="submit" class="bg-red-500 text-white p-2 rounded-full cursor-pointer hover:bg-red-600">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let searchQuery = this.value.toLowerCase();
            let tableRows = document.querySelectorAll('.products-table tbody tr');
            
            tableRows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchQuery) ? '' : 'none';
            });
        });
    </script>
</body>
</html> 