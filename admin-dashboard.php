<?php
// admin-dashboard.php
session_start();
// Add these at the top of admin-dashboard.php after session_start()

require 'dbconnect.php'; // Ensure this is before any usage of $conn

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Handle sorting
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Validate sort column to prevent SQL injection
$allowed_columns = ['id', 'fullname', 'email', 'phone', 'role', 'created_at'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'created_at';
}

// Build the query with search and sorting
$query = "SELECT id, fullname, email, phone, role, created_at, status 
          FROM users 
          WHERE fullname LIKE ? OR email LIKE ? OR phone LIKE ?
          ORDER BY $sort_column $sort_order";

try {
    $stmt = $conn->prepare($query);
    $search_term = "%$search%";
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Error fetching users: " . $e->getMessage());
}

// Function to generate sort URL
function getSortUrl($column, $currentSort, $currentOrder) {
    $newOrder = ($column == $currentSort && $currentOrder == 'ASC') ? 'DESC' : 'ASC';
    return "?sort=" . $column . "&order=" . $newOrder . (isset($_GET['search']) ? "&search=" . $_GET['search'] : "");
}

// Function to display sort indicator
function getSortIndicator($column, $currentSort, $currentOrder) {
    if ($column == $currentSort) {
        return $currentOrder == 'ASC' ? ' ↑' : ' ↓';
    }
    return '';
}


// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Remove or comment out this duplicate query
/*
try {
    $query = "SELECT id, fullname, email, phone, role, created_at FROM users ORDER BY created_at DESC";
    $result = $conn->query($query);
    $users = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    } else {
        throw new Exception("Error fetching users: " . $conn->error);
    }
} catch (Exception $e) {
    die("Error fetching users: " . $e->getMessage());
}
*/

    // Keep these statistics queries
    // Get total users count
    $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    
    // Get new users count this week
    $new_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)")->fetch_assoc()['count'];
    
    // Get recent logins count
    $recent_logins = $conn->query("SELECT COUNT(*) as count FROM login_logs WHERE login_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['count'];

// Add this before the HTML chart section
try {
    // Fetch user registration data for the last 6 months
    $registration_query = "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as user_count
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC";
    
    $reg_result = $conn->query($registration_query);
    
    $months = [];
    $counts = [];
    
    while($row = $reg_result->fetch_assoc()) {
        // Format the month for display
        $months[] = date('M Y', strtotime($row['month'] . '-01'));
        $counts[] = $row['user_count'];
    }
    
    $chart_data = [
        'months' => $months,
        'counts' => $counts
    ];
} catch (Exception $e) {
    error_log("Chart Data Error: " . $e->getMessage());
    $chart_data = ['months' => [], 'counts' => []];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClaraCrest Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Admin Navigation -->
    <nav class="bg-black text-white fixed w-full z-50">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <span class="text-xl font-bold">ClaraCrest Admin</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?></span>
                    <a href="logout.php" class="bg-red-600 px-4 py-2 rounded-lg hover:bg-red-700">Logout</a>
                    <a href="home.php" class="bg-blue-600 px-4 py-2 rounded-lg hover:bg-blue-700">Back to Store</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Dashboard Layout -->
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>Admin Dashboard</h2>
            <ul>
                <li>
                    <a href="admin-dashboard.php" class="bg-blue-600">
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
                    <a href="admin-order-view.php">
                        <i class="fas fa-shopping-cart"></i>
                        Orders
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Stats Cards -->
            <div class="grid grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Total Users</h3>
                    <p class="text-2xl font-bold"><?php echo $total_users; ?></p>
                    <span class="text-green-500 text-sm">+<?php echo $new_users; ?> this week</span>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Recent Logins</h3>
                    <p class="text-2xl font-bold"><?php echo $recent_logins; ?></p>
                    <span class="text-blue-500 text-sm">Last 24 hours</span>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-gray-500 text-sm font-medium">New Users</h3>
                    <p class="text-2xl font-bold"><?php echo $new_users; ?></p>
                    <span class="text-yellow-500 text-sm">This week</span>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Active Users</h3>
                    <p class="text-2xl font-bold"><?php echo $recent_logins; ?></p>
                    <span class="text-green-500 text-sm">Currently online</span>
                </div>
            </div>

            <!-- User Management Section -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-bold mb-4">Registered Users</h2>
                    
                    <!-- Search Form -->
                    <form method="GET" class="flex space-x-2 mb-4">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search users..." 
                               class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 flex-1">
                        <button type="submit" 
                                class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                            Search
                        </button>
                        <?php if (!empty($search)): ?>
                        <a href="?" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                            Clear
                        </a>
                        <?php endif; ?>
                    </form>

                    <!-- Results Summary -->
                    <p class="text-gray-600 mb-4">
                        Found <?php echo count($users); ?> users
                        <?php echo !empty($search) ? " matching '" . htmlspecialchars($search) . "'" : ''; ?>
                    </p>
                </div>

                <div class="p-6 overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="pb-4">
                                    <a href="<?php echo getSortUrl('id', $sort_column, $sort_order); ?>" 
                                       class="hover:text-gray-700">
                                        User ID<?php echo getSortIndicator('id', $sort_column, $sort_order); ?>
                                    </a>
                                </th>
                                <th class="pb-4">
                                    <a href="<?php echo getSortUrl('fullname', $sort_column, $sort_order); ?>" 
                                       class="hover:text-gray-700">
                                        Full Name<?php echo getSortIndicator('fullname', $sort_column, $sort_order); ?>
                                    </a>
                                </th>
                                <th class="pb-4">
                                    <a href="<?php echo getSortUrl('email', $sort_column, $sort_order); ?>" 
                                       class="hover:text-gray-700">
                                        Email<?php echo getSortIndicator('email', $sort_column, $sort_order); ?>
                                    </a>
                                </th>
                                <th class="pb-4">
                                    <a href="<?php echo getSortUrl('phone', $sort_column, $sort_order); ?>" 
                                       class="hover:text-gray-700">
                                        Phone<?php echo getSortIndicator('phone', $sort_column, $sort_order); ?>
                                    </a>
                                </th>
                                <th class="pb-4">
                                    <a href="<?php echo getSortUrl('role', $sort_column, $sort_order); ?>" 
                                       class="hover:text-gray-700">
                                        Role<?php echo getSortIndicator('role', $sort_column, $sort_order); ?>
                                    </a>
                                </th>
                                <th class="pb-4">
                                    <a href="<?php echo getSortUrl('created_at', $sort_column, $sort_order); ?>" 
                                       class="hover:text-gray-700">
                                        Join Date<?php echo getSortIndicator('created_at', $sort_column, $sort_order); ?>
                                    </a>
                                </th>
                                <th class="pb-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="py-4 text-center text-gray-500">
                                    No users found<?php echo !empty($search) ? " matching '" . htmlspecialchars($search) . "'" : ''; ?>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <?php if ($user['role'] !== 'admin'): ?>
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="py-4"><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td class="py-4"><?php echo htmlspecialchars($user['fullname']); ?></td>
                                        <td class="py-4"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td class="py-4"><?php echo htmlspecialchars($user['phone']); ?></td>
                                        <td class="py-4">
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                                                user
                                            </span>
                                        </td>
                                        <td class="py-4"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td class="py-4">
                                            <div class="flex space-x-2">
                                                <button onclick="toggleStatus(<?php echo $user['id']; ?>)"
                                                        class="px-3 py-1 rounded-full <?php echo $user['status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                    <?php echo $user['status'] ? 'Active' : 'Inactive'; ?>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Analytics Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">User Registration Trends</h2>
                <div style="height: 400px;">
                    <canvas id="userChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize the chart with actual data from database
        const ctx = document.getElementById('userChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_data['months']); ?>,
                datasets: [{
                    label: 'New User Registrations',
                    data: <?php echo json_encode($chart_data['counts']); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
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
                        text: 'Monthly User Registration Trends'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
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

        function toggleStatus(userId) {
            // Add AJAX call to update user status
            fetch('update-user-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    userId: userId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show updated status
                    window.location.reload();
                } else {
                    alert('Error updating user status');
                }
            });
        }
    </script>

    <style>
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
    </style>
</body>
</html>

<?php