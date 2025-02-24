<?php
// Start the session
session_start();

// Include the database connection
require_once 'dbconnect.php';

// Ensure the connection is established
if (!isset($conn)) {
    die("Database connection not established.");
}

// Fetch users from database
try {
    // Get total users count
    $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    
    // Get new users count this week
    $new_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)")->fetch_assoc()['count'];
    
    // Get recent logins count
    $recent_logins = $conn->query("SELECT COUNT(*) as count FROM login_logs WHERE login_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['count'];
    
} catch (Exception $e) {
    die("Error fetching users: " . $e->getMessage());
}

// Static values for design display
$orders_data = ['total_orders' => 100]; // Example static value
$products_data = ['total_products' => 50]; // Example static value
$revenue_data = ['total_revenue' => 1500.00]; // Example static value

// Commenting out the database queries to prevent errors
/*
if ($conn) {
    // Get total orders
    $orders_query = "SELECT COUNT(*) as total_orders FROM orders";
    $orders_result = mysqli_query($conn, $orders_query);
    if (!$orders_result) {
        die("Query failed: " . mysqli_error($conn));
    }
    $orders_data = mysqli_fetch_assoc($orders_result);

    // Get total products
    $products_query = "SELECT COUNT(*) as total_products FROM products";
    $products_result = mysqli_query($conn, $products_query);
    if (!$products_result) {
        die("Query failed: " . mysqli_error($conn));
    }
    $products_data = mysqli_fetch_assoc($products_result);

    // Get total revenue
    $revenue_query = "SELECT SUM(total_amount) as total_revenue FROM orders WHERE status = 'completed'";
    $revenue_result = mysqli_query($conn, $revenue_query);
    if (!$revenue_result) {
        die("Query failed: " . mysqli_error($conn));
    }
    $revenue_data = mysqli_fetch_assoc($revenue_result);
} else {
    die("Database connection error.");
}
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            background: #f5f6fa;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .card h3 {
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .logout {
            margin-top: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>Manager Dashboard</h2>
            <ul>
                <li>
                    <a href="dashboard.php">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="managerreguser.php">
                        <i class="fas fa-user-plus"></i>
                        Users
                    </a>
                </li>
                <li>
                    <a href="manage-categories.php">
                        <i class="fas fa-tags"></i>
                        Categories
                    </a>
                </li>
                <li>
                    <a href="add-products.php">
                        <i class="fas fa-plus"></i>
                        Add Product
                    </a>
                </li>
                <li>
                <a href="products.php">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
                <li>
                    <a href="view-orders.php">
                        <i class="fas fa-shopping-cart"></i>
                        View Orders
                    </a>
                </li>
                <li>
                    <a href="top-products.php">
                        <i class="fas fa-chart-bar"></i>
                        Most Selling Products
                    </a>
                </li>
                <li>
                    <a href="payments.php">
                        <i class="fas fa-credit-card"></i>
                        Payment Details
                    </a>
                </li>
                
                <li class="logout">
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Guest'; ?></h1>
            </div>

            <div class="dashboard-cards">
                <div class="card">
                    <h3>Total Orders</h3>
                    <p><?php echo $orders_data['total_orders']; ?></p>
                </div>

                <div class="card">
                    <h3>Total Products</h3>
                    <p><?php echo $products_data['total_products']; ?></p>
                </div>

                <div class="card">
                    <h3>Total Revenue</h3>
                    <p>$<?php echo number_format($revenue_data['total_revenue'], 2); ?></p>
                </div>

                <div class="card">
                    <h3>Total Users</h3>
                    <p><?php echo $total_users; ?></p>
                </div>

                <div class="card">
                    <h3>New Users This Week</h3>
                    <p><?php echo $new_users; ?></p>
                </div>

                <div class="card">
                    <h3>Recent Logins</h3>
                    <p><?php echo $recent_logins; ?></p>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="card">
                <h3>Recent Orders</h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr>
                            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #ddd;">Order ID</th>
                            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #ddd;">Customer</th>
                            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #ddd;">Amount</th>
                            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #ddd;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_orders_query = "SELECT * FROM orders ORDER BY order_date DESC LIMIT 5";
                        $recent_orders_result = mysqli_query($conn, $recent_orders_query);
                        
                        while ($order = mysqli_fetch_assoc($recent_orders_result)) {
                            echo "<tr>";
                            echo "<td style='padding: 10px; border-bottom: 1px solid #ddd;'>#" . $order['order_id'] . "</td>";
                            echo "<td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . $order['customer_name'] . "</td>";
                            echo "<td style='padding: 10px; border-bottom: 1px solid #ddd;'>$" . number_format($order['total_amount'], 2) . "</td>";
                            echo "<td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . $order['status'] . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Registered Users Table -->
            <div class="card">
                <h3>Registered Users</h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr>
                            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #ddd;">User ID</th>
                            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #ddd;">Name</th>
                            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #ddd;">Email</th>
                            <th style="padding: 10px; text-align: left; border-bottom: 1px solid #ddd;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch registered users from the database
                        $users_query = "SELECT * FROM users"; // Adjust the table name as necessary
                        $users_result = mysqli_query($conn, $users_query);
                        
                        if ($users_result) {
                            while ($user = mysqli_fetch_assoc($users_result)) {
                                echo "<tr>";
                                echo "<td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . $user['user_id'] . "</td>";
                                echo "<td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . $user['name'] . "</td>";
                                echo "<td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . $user['email'] . "</td>";
                                echo "<td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . $user['status'] . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='padding: 10px; text-align: center;'>No users found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>