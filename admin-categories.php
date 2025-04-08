<?php
require_once 'dbconnect.php';

class CategoryManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get all categories
    public function getCategories() {
        $sql = "SELECT * FROM categories ORDER BY name";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get subcategories
    public function getSubcategories() {
        $sql = "SELECT s.*, c.name as category_name 
                FROM subcategories s 
                JOIN categories c ON s.category_id = c.id
                ORDER BY c.name, s.name";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Initialize CategoryManager with database connection
$categoryManager = new CategoryManager($conn);

// Get all categories and subcategories
$categories = $categoryManager->getCategories();
$subcategories = $categoryManager->getSubcategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url("image/bg6.jpg");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            padding: 20px 0;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.3);
            z-index: -1;
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

        /* Ensure container uses flex */
        .container {
            display: flex;
            min-height: 100vh;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.875rem;
        }

        .status-active {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .status-inactive {
            background-color: #FEE2E2;
            color: #991B1B;
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
                    <a href="admin-viewproduct.php">
                        <i class="fas fa-box"></i>
                        Products
                    </a>
                </li>
                <li>
                    <a href="admin-categories.php" class="bg-blue-600">
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

        <div class="main-content">
            <div class="content-container">
                <h2 class="mb-4">Categories Overview</h2>

                <!-- Categories List -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Main Categories</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Category Name</th>
                                        <th>Slug</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $category['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($category['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Subcategories List -->
                <div class="card">
                    <div class="card-header">
                        <h5>Subcategories</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Subcategory Name</th>
                                        <th>Parent Category</th>
                                        <th>Slug</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subcategories as $subcategory): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subcategory['name']); ?></td>
                                            <td><?php echo htmlspecialchars($subcategory['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($subcategory['slug']); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $subcategory['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($subcategory['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>