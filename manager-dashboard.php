<?php
// Start the session
session_start();

// Check if user is logged in and has manager role
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || 
    ($_SESSION['role'] !== 'manager' && $_SESSION['user_id'] !== 'manager')) {
    // Redirect to login page if not logged in as manager
    header("Location: login.php");
    exit();
}

// Include the database connection
require_once 'dbconnect.php';

// Ensure the connection is established
if (!isset($conn)) {
    die("Database connection not established.");
}

// Fetch counts from database
try {
    // Get total orders count (actual count)
    $orders_query = "SELECT COUNT(*) as total_orders FROM orders";
    $orders_result = $conn->query($orders_query);
    if (!$orders_result) {
        throw new Exception("Error fetching orders count: " . $conn->error);
    }
    $orders_data = $orders_result->fetch_assoc();

    // Get total products count (actual count)
    $products_query = "SELECT COUNT(*) as total_products FROM products";
    $products_result = $conn->query($products_query);
    if (!$products_result) {
        throw new Exception("Error fetching products count: " . $conn->error);
    }
    $products_data = $products_result->fetch_assoc();

    // Get total users count (actual count)
    $users_query = "SELECT COUNT(*) as total_users FROM users WHERE role != 'manager'";
    $users_result = $conn->query($users_query);
    if (!$users_result) {
        throw new Exception("Error fetching users count: " . $conn->error);
    }
    $total_users = $users_result->fetch_assoc()['total_users'];

    // Get new users this week (actual count)
    $new_users_query = "SELECT COUNT(*) as count FROM users 
                       WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                       AND role != 'manager'";
    $new_users_result = $conn->query($new_users_query);
    if (!$new_users_result) {
        throw new Exception("Error fetching new users count: " . $conn->error);
    }
    $new_users = $new_users_result->fetch_assoc()['count'];

    // Get recent logins in last 24 hours (actual count)
    $recent_logins_query = "SELECT COUNT(DISTINCT user_id) as count 
                           FROM login_logs 
                           WHERE login_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $recent_logins_result = $conn->query($recent_logins_query);
    if (!$recent_logins_result) {
        throw new Exception("Error fetching recent logins count: " . $conn->error);
    }
    $recent_logins = $recent_logins_result->fetch_assoc()['count'];

} catch (Exception $e) {
    // Log the error and set default values
    error_log("Dashboard Error: " . $e->getMessage());
    $orders_data = ['total_orders' => 0];
    $products_data = ['total_products' => 0];
    $total_users = 0;
    $new_users = 0;
    $recent_logins = 0;
}

// Fetch actual data from database
try {
    // Get total orders count
    $orders_query = "SELECT COUNT(DISTINCT order_id) as total_orders FROM orders";
    $orders_result = $conn->query($orders_query);
    $orders_data = $orders_result->fetch_assoc();

    // Get total products
    $products_query = "SELECT COUNT(*) as total_products FROM products";
    $products_result = $conn->query($products_query);
    $products_data = $products_result->fetch_assoc();

    // Get total revenue
    $revenue_query = "SELECT SUM(price_at_time * quantity) as total_revenue FROM orders WHERE order_status != 'cancelled'";
    $revenue_result = $conn->query($revenue_query);
    $revenue_data = $revenue_result->fetch_assoc();

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    // Set default values if query fails
    $orders_data = ['total_orders' => 0];
    $products_data = ['total_products' => 0];
    $revenue_data = ['total_revenue' => 0];
}

// Fetch order data for graph
try {
    $graph_query = "SELECT 
        DATE(created_at) as order_date,
        COUNT(*) as order_count
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY order_date ASC";
    
    $graph_result = $conn->query($graph_query);
    
    $dates = [];
    $counts = [];
    
    while($row = $graph_result->fetch_assoc()) {
        $dates[] = date('M d', strtotime($row['order_date']));
        $counts[] = $row['order_count'];
    }
    
    $graph_data = [
        'dates' => $dates,
        'counts' => $counts
    ];
} catch (Exception $e) {
    error_log("Graph Data Error: " . $e->getMessage());
    $graph_data = ['dates' => [], 'counts' => []];
}

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

        .card-header {
            padding: 1rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .card-title {
            margin: 0;
            color: #2c3e50;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        canvas {
            max-width: 100%;
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
                    <a href="manager-dashboard.php">
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
                        Products
                    </a>
                </li>
                <li>
                    <a href="manager-vieworders.php">
                        <i class="fas fa-shopping-cart"></i>
                        View Orders
                    </a>
                </li>
                <li>
                    <a href="manager-review.php">
                        <i class="fas fa-star"></i>
                        Reviews
                    </a>
                </li>
                <li>
                    <a href="manager-payment.php">
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
                <h1>Welcome, Manager</h1>
            </div>

            <div class="dashboard-cards">
                <div class="card">
                    <h3>Total Orders</h3>
                    <p><?php echo number_format($orders_data['total_orders']); ?></p>
                </div>

                <div class="card">
                    <h3>Total Products</h3>
                    <p><?php echo number_format($products_data['total_products']); ?></p>
                </div>

                <div class="card">
                    <h3>Total Users</h3>
                    <p><?php echo number_format($total_users); ?></p>
                </div>

                <div class="card">
                    <h3>New Users This Week</h3>
                    <p><?php echo number_format($new_users); ?></p>
                </div>

                <div class="card">
                    <h3>Recent Logins (24h)</h3>
                    <p><?php echo number_format($recent_logins); ?></p>
                </div>
            </div>

            <!-- Orders Graph Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Order Trends (Last 30 Days)</h3>
                </div>
                <div class="card-body">
                    <canvas id="ordersChart" style="width: 100%; height: 400px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Close the database connection
    if (isset($conn)) {
        $conn->close();
    }
    ?>

    <!-- Add Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    const ctx = document.getElementById('ordersChart').getContext('2d');
    const ordersChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($graph_data['dates']); ?>,
            datasets: [{
                label: 'Number of Orders',
                data: <?php echo json_encode($graph_data['counts']); ?>,
                fill: true,
                borderColor: '#2980b9',
                backgroundColor: 'rgba(41, 128, 185, 0.1)',
                tension: 0.4,
                borderWidth: 2,
                pointBackgroundColor: '#2980b9',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Daily Order Trends'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    },
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
    </script>
</body>
</html>