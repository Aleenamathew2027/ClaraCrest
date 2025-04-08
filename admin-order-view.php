<?php
// admin-order-view.php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Include the database connection
require_once 'dbconnect.php';

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // Build base query with JOINs
    $base_query = "SELECT o.order_id, 
                         o.quantity,
                         o.price_at_time,
                         o.order_status,
                         o.created_at,
                         u.fullname as customer_name,
                         p.name as product_name,
                         (o.quantity * o.price_at_time) as total_amount
                  FROM orders o
                  LEFT JOIN users u ON o.user_id = u.id
                  LEFT JOIN products p ON o.product_id = p.id";
    
    if (!empty($search)) {
        // Add WHERE clause for search across multiple columns
        $base_query .= " WHERE o.order_id LIKE ? 
                        OR u.fullname LIKE ? 
                        OR p.name LIKE ?
                        OR o.order_status LIKE ?";
        $stmt = $conn->prepare($base_query);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $search_term = "%$search%";
        $stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
    } else {
        $stmt = $conn->prepare($base_query);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
    }

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Result failed: " . $conn->error);
    }

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    // Get total orders count
    $total_orders = count($orders);

    // Clear any existing error messages
    unset($_SESSION['error_message']);

} catch (Exception $e) {
    // Log the error
    error_log("Error in admin-order-view.php: " . $e->getMessage());
    
    // Initialize empty arrays if query fails
    $orders = [];
    $total_orders = 0;
    
    // Set error message to display to user
    $_SESSION['error_message'] = "Database Error: " . $e->getMessage();
}

// Debug information
if (empty($orders)) {
    error_log("No orders found in the database. Search term: " . $search);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
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

        .status-badge {
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending { background-color: #FEF3C7; color: #92400E; }
        .status-processing { background-color: #DBEAFE; color: #1E40AF; }
        .status-shipped { background-color: #F3E8FF; color: #6B21A8; }
        .status-delivered { background-color: #D1FAE5; color: #065F46; }
        .status-cancelled { background-color: #FEE2E2; color: #991B1B; }
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
                    <a href="admin-categories.php">
                        <i class="fas fa-tags"></i>
                        Categories
                    </a>
                </li>
                <li>
                    <a href="admin-order-view.php" class="bg-blue-600">
                        <i class="fas fa-shopping-cart"></i>
                        Orders
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-container">
                <h2 class="text-2xl font-bold mb-6">Order Management</h2>

                <!-- Search Form -->
                <form method="GET" action="admin-order-view.php" class="mb-6">
                    <div class="flex gap-4">
                        <input type="text" 
                               name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by order ID, customer, product, or status..." 
                               class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-200">
                            Search
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="admin-order-view.php" 
                               class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-200">
                                Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Results Summary -->
                <p class="text-gray-600 mb-4">
                    Found <?php echo $total_orders; ?> orders
                    <?php echo !empty($search) ? " matching '" . htmlspecialchars($search) . "'" : ''; ?>
                </p>

                <!-- Orders Table -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                        No orders found<?php echo !empty($search) ? " matching '" . htmlspecialchars($search) . "'" : ''; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['order_id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($order['product_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['quantity']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">₹<?php echo number_format($order['price_at_time'], 2); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Error Message -->
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $_SESSION['error_message']; ?></span>
                        <?php unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
