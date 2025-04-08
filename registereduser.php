<?php
// registereduser.php
session_start();
require 'dbconnect.php'; // Ensure this is before any usage of $conn

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query with search
$query = "SELECT id, fullname, email, phone, role, created_at, status 
          FROM users 
          WHERE fullname LIKE ? OR email LIKE ? OR phone LIKE ?";

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

// Get total users count
$total_users = count($users);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Users</title>
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

        body {
            background-image: url("image/bg6.jpg");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
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
                    <a href="registereduser.php" class="bg-blue-600">
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
            <div class="content-container">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="bg-green-500 text-white p-4 rounded-lg mb-4">
                        <?php echo $_SESSION['message']; ?>
                        <?php unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>

                <h2 class="text-2xl font-bold mb-4">Registered Users</h2>

                <!-- Search Form -->
                <form method="GET" class="flex space-x-2 mb-4">
                    <input type="text" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search users..." 
                           class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 flex-1">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="registereduser.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-200">Clear</a>
                    <?php endif; ?>
                </form>

                <!-- Results Summary -->
                <p class="text-gray-600 mb-4">
                    Found <?php echo $total_users; ?> users<?php echo !empty($search) ? " matching '" . htmlspecialchars($search) . "'" : ''; ?>
                </p>

                <!-- Users Table -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="text-left text-gray-500 bg-gray-100">
                                <th class="py-2 px-4">User ID</th>
                                <th class="py-2 px-4">Full Name</th>
                                <th class="py-2 px-4">Email</th>
                                <th class="py-2 px-4">Phone</th>
                                <th class="py-2 px-4">Role</th>
                                <th class="py-2 px-4">Join Date</th>
                                <th class="py-2 px-4">Actions</th>
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
                                            <td class="py-4 px-4"><?php echo htmlspecialchars($user['id']); ?></td>
                                            <td class="py-4 px-4"><?php echo htmlspecialchars($user['fullname']); ?></td>
                                            <td class="py-4 px-4"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="py-4 px-4"><?php echo htmlspecialchars($user['phone']); ?></td>
                                            <td class="py-4 px-4">
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                                                    user
                                                </span>
                                            </td>
                                            <td class="py-4 px-4"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td class="py-4 px-4">
                                                <button onclick="toggleStatus(<?php echo $user['id']; ?>)"
                                                        class="px-3 py-1 rounded-full <?php echo $user['status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                    <?php echo $user['status'] ? 'Active' : 'Inactive'; ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this JavaScript before the closing </body> tag -->
    <script>
    function toggleStatus(userId) {
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
                window.location.reload();
            } else {
                alert('Error updating user status');
            }
        });
    }
    </script>
</body>
</html> 