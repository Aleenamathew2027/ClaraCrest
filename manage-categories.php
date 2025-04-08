<?php
require_once 'dbconnect.php';

class CategoryManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Initialize default categories
    public function initializeCategories() {
        // The method is kept but doesn't insert any default data
        // This can be used in the future to initialize categories if needed
    }

    // Get all categories
    public function getCategories() {
        $sql = "SELECT * FROM categories ORDER BY name";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get subcategories by category ID
    public function getSubcategories($category_id = null) {
        $sql = "SELECT s.*, c.name as category_name 
                FROM subcategories s 
                JOIN categories c ON s.category_id = c.id";
        
        if ($category_id) {
            $sql .= " WHERE s.category_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Add new category
    public function addCategory($name) {
        $slug = $this->createSlug($name);
        $sql = "INSERT INTO categories (name, slug) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $name, $slug);
        return $stmt->execute();
    }

    // Add new subcategory
    public function addSubcategory($category_id, $name) {
        $category = $this->getCategoryById($category_id);
        $slug = $this->createSlug($name . '-' . $category['slug']);
        
        $sql = "INSERT INTO subcategories (category_id, name, slug) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $category_id, $name, $slug);
        return $stmt->execute();
    }

    // Update category status
    public function updateCategoryStatus($id, $status) {
        $sql = "UPDATE categories SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    // Update subcategory status
    public function updateSubcategoryStatus($id, $status) {
        $sql = "UPDATE subcategories SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    // Delete category
    public function deleteCategory($id) {
        $sql = "DELETE FROM categories WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Delete subcategory
    public function deleteSubcategory($id) {
        $sql = "DELETE FROM subcategories WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Helper function to get category by ID
    private function getCategoryById($id) {
        $sql = "SELECT * FROM categories WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Helper function to create slug
    private function createSlug($text) {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
    }
}

// Initialize CategoryManager with database connection
$categoryManager = new CategoryManager($conn);

// Initialize categories if needed (now empty by default)
$categoryManager->initializeCategories();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_category':
                $categoryManager->addCategory($_POST['name']);
                break;
                
            case 'add_subcategory':
                $categoryManager->addSubcategory($_POST['category_id'], $_POST['name']);
                break;
                
            case 'update_category':
                $categoryManager->updateCategoryStatus($_POST['id'], $_POST['status']);
                break;
                
            case 'update_subcategory':
                $categoryManager->updateSubcategoryStatus($_POST['id'], $_POST['status']);
                break;
                
            case 'delete_category':
                $categoryManager->deleteCategory($_POST['id']);
                break;
                
            case 'delete_subcategory':
                $categoryManager->deleteSubcategory($_POST['id']);
                break;
        }
    }
}

// Get all categories and subcategories
$categories = $categoryManager->getCategories();
$subcategories = $categoryManager->getSubcategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url("image/bg1.jpg");
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

        .action-buttons form {
            display: inline-block;
            margin-right: 5px;
        }

        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #f8f9fa;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }
        .sidebar a {
            display: block;
            padding: 10px;
            color: #333;
            text-decoration: none;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .sidebar a:hover {
            background-color: #e9ecef;
        }
        .content {
            margin-left: 270px; /* Adjusted for sidebar width */
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4>Menu</h4>
        <br>
        <p>Dashboard</p>
        <a href="manager-dashboard.php">Back</a>
        <a href="managerreguser.php">Users</a>
        <a href="add-products.php">Add Product</a>
        <a href="products.php">Products</a>
        <a href="manager-vieworders.php">View Orders</a>
        
        <a href="manager-payment.php">Payment Details</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="content">
        <div class="container mt-4">
            <h2>Category Management</h2>
            
            <!-- Add Category Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Add New Category</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="add_category">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Add Category</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add Subcategory Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Add New Subcategory</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="add_subcategory">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Parent Category</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="" disabled selected>Select a Parent Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="subcat_name" class="form-label">Subcategory Name</label>
                            <input type="text" class="form-control" id="subcat_name" name="name" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Add Subcategory</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Categories List -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Categories</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $category['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo htmlspecialchars($category['status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="update_category">
                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                            <input type="hidden" name="status" 
                                                   value="<?php echo $category['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                Toggle Status
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure? This will delete all subcategories as well.')">
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Subcategories List -->
            <div class="card">
                <div class="card-header">
                    <h5>Subcategories</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Parent Category</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subcategories as $subcategory): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subcategory['name']); ?></td>
                                    <td><?php echo htmlspecialchars($subcategory['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($subcategory['slug']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $subcategory['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo htmlspecialchars($subcategory['status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="update_subcategory">
                                            <input type="hidden" name="id" value="<?php echo $subcategory['id']; ?>">
                                            <input type="hidden" name="status" 
                                                   value="<?php echo $subcategory['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                Toggle Status
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this subcategory?')">
                                            <input type="hidden" name="action" value="delete_subcategory">
                                            <input type="hidden" name="id" value="<?php echo $subcategory['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>