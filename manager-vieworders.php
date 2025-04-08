<?php
require_once 'dbconnect.php';

class OrderManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Get all orders with customer and product details
    public function getAllOrders() {
        // Check if orders table exists
        $check_table = $this->db->query("SHOW TABLES LIKE 'orders'");
        if ($check_table->num_rows == 0) {
            // Table doesn't exist, return empty array
            return [];
        }
        
        // Query to get orders with product, user and image details
        $sql = "SELECT o.*, 
                       u.fullname, u.email, u.phone,
                       p.name as product_name, 
                       COALESCE(pi.image_url, p.image_url, 'assets/img/no-image.jpg') as product_image
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                LEFT JOIN products p ON o.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                ORDER BY o.created_at DESC";
        $result = $this->db->query($sql);
        
        if ($result === false) {
            // Query failed, return empty array
            return [];
        }
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get orders by status
    public function getOrdersByStatus($status) {
        $sql = "SELECT o.*, 
                       u.fullname, u.email, u.phone,
                       p.name as product_name, 
                       COALESCE(pi.image_url, p.image_url, 'assets/img/no-image.jpg') as product_image
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                LEFT JOIN products p ON o.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE o.order_status = ?
                ORDER BY o.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $status);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Update order status
    public function updateOrderStatus($order_id, $status) {
        $sql = "UPDATE orders SET order_status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $status, $order_id);
        return $stmt->execute();
    }
    
    // Get order statistics
    public function getOrderStatistics() {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'cancelled' => 0
        ];
        
        // Check if orders table exists
        $check_table = $this->db->query("SHOW TABLES LIKE 'orders'");
        if ($check_table->num_rows == 0) {
            return $stats;
        }
        
        // Get total orders count
        $sql = "SELECT COUNT(*) as total FROM orders";
        $result = $this->db->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total'] = $row['total'];
        }
        
        // Get count by status
        $sql = "SELECT order_status, COUNT(*) as count 
                FROM orders 
                GROUP BY order_status";
        $result = $this->db->query($sql);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Only include the statuses we want
                if (isset($stats[$row['order_status']])) {
                    $stats[$row['order_status']] = $row['count'];
                }
            }
        }
        
        return $stats;
    }
}

// Check if manager is logged in
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: login.php");
    exit();
}

// Create order manager instance
$db = Database::getInstance();
$conn = $db->getConnection();
$orderManager = new OrderManager($conn);

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    // Update order status
    $updated = $orderManager->updateOrderStatus($order_id, $new_status);
    
    if ($updated) {
        $success_message = "Order status updated successfully.";
    } else {
        $error_message = "Failed to update order status.";
    }
}

// Get status filter from URL
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get orders based on filter
if (!empty($status_filter)) {
    $orders = $orderManager->getOrdersByStatus($status_filter);
} else {
    $orders = $orderManager->getAllOrders();
}

// Get order statistics
$orderStats = $orderManager->getOrderStatistics();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management | Manager Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <style>
        .stat-item {
            padding: 20px;
            border-radius: 8px;
            color: white;
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-item i {
            font-size: 2.5rem;
            margin-right: 15px;
        }
        
        .stat-info {
            display: flex;
            flex-direction: column;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0;
        }
        
        .stat-label {
            font-size: 1rem;
            margin: 0;
        }
        
        .filter-buttons {
            margin: 20px 0;
        }
        
        .filter-buttons .btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .product-cell {
            display: flex;
            align-items: center;
        }
        
        .product-thumbnail {
            width: 50px;
            height: 50px;
            border-radius: 4px;
            object-fit: cover;
            margin-right: 10px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            color: white;
            display: inline-block;
        }
        
        .action-buttons .btn {
            margin-right: 5px;
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
            z-index: 1000;
        }
        
        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar li {
            margin: 5px 0;
        }
        
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }
        
        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar .logout {
            margin-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }
        
        .container-fluid {
            margin-left: 250px;
            width: calc(100% - 250px);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
            }
            
            .sidebar h2 {
                display: none;
            }
            
            .sidebar a span {
                display: none;
            }
            
            .sidebar a {
                padding: 15px;
                justify-content: center;
            }
            
            .sidebar i {
                margin: 0;
            }
            
            .container-fluid {
                margin-left: 60px;
                width: calc(100% - 60px);
            }
        }
    </style>
</head>
<body>
    <!-- Add Sidebar -->
    <div class="sidebar">
        <h2>Manager Dashboard</h2>
        <ul>
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

    <div class="container-fluid py-4">
        <h1 class="mb-4">Order Management</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Order Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stat-item bg-primary">
                                    <i class="fas fa-shopping-cart"></i>
                                    <div class="stat-info">
                                        <p class="stat-number"><?php echo $orderStats['total']; ?></p>
                                        <p class="stat-label">Total Orders</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-item bg-warning">
                                    <i class="fas fa-clock"></i>
                                    <div class="stat-info">
                                        <p class="stat-number"><?php echo $orderStats['pending']; ?></p>
                                        <p class="stat-label">Pending</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-item bg-info">
                                    <i class="fas fa-sync-alt"></i>
                                    <div class="stat-info">
                                        <p class="stat-number"><?php echo $orderStats['processing']; ?></p>
                                        <p class="stat-label">Processing</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-item bg-danger">
                                    <i class="fas fa-times-circle"></i>
                                    <div class="stat-info">
                                        <p class="stat-number"><?php echo $orderStats['cancelled']; ?></p>
                                        <p class="stat-label">Cancelled</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Buttons -->
        <div class="filter-buttons">
            <a href="manager-vieworders.php" class="btn btn-outline-primary <?php echo empty($status_filter) ? 'active' : ''; ?>">All Orders</a>
            <a href="manager-vieworders.php?status=pending" class="btn btn-outline-warning <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="manager-vieworders.php?status=processing" class="btn btn-outline-info <?php echo $status_filter === 'processing' ? 'active' : ''; ?>">Processing</a>
            <a href="manager-vieworders.php?status=cancelled" class="btn btn-outline-danger <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
        </div>
        
        <!-- Orders Table -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-table me-1"></i>
                <?php echo !empty($status_filter) ? ucfirst($status_filter) . ' Orders' : 'All Orders'; ?>
            </div>
            <div class="card-body">
                <table id="ordersTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (!empty($orders)): 
                            foreach ($orders as $order): 
                                $total = $order['price_at_time'] * $order['quantity'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td class="product-cell">
                                <img 
                                    src="<?php echo htmlspecialchars($order['product_image']); ?>" 
                                    alt="Product" 
                                    class="product-thumbnail"
                                    onerror="this.onerror=null; this.src='assets/img/no-image.jpg';"
                                >
                                <span><?php echo htmlspecialchars($order['product_name']); ?></span>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($order['fullname']); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($order['email']); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($order['phone']); ?></div>
                            </td>
                            <td><?php echo $order['quantity']; ?></td>
                            <td>₹<?php echo number_format($order['price_at_time'], 2); ?></td>
                            <td>₹<?php echo number_format($total, 2); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                            <td class="action-buttons">
                                <a href="view-order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Details
                                </a>
                            </td>
                        </tr>
                        <?php 
                            endforeach; 
                        else: 
                        ?>
                        <tr>
                            <td colspan="8" class="text-center">No orders found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#ordersTable').DataTable({
                order: [[6, 'desc']], // Sort by date column (index 6) in descending order
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                responsive: true,
                language: {
                    search: "Search orders:",
                    lengthMenu: "Show _MENU_ entries per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ orders",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        });
    </script>
</body>
</html> 