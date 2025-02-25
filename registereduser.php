<?php
// registereduser.php
session_start();
require 'dbconnect.php'; // Ensure this is before any usage of $conn

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query with search
$query = "SELECT id, fullname, email, phone, role, created_at 
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
    <style>
        body {
            background-image: url("image/bg6.jpg"); /* Replace with your image path */
            background-size: cover;
            background-position: center;
            animation: backgroundAnimation 30s linear infinite;
        }

        @keyframes backgroundAnimation {
            0% { background-position: 0% 0%; }
            100% { background-position: 100% 100%; }
        }

        .container {
            background-color: rgba(255, 255, 255, 0.8); /* Semi-transparent background for readability */
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="bg-black text-white fixed w-full z-50">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <span class="text-xl font-bold">ClaraCrest Admin</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?></span>
                    <a href="admin-dashboard.php" class="bg-green-600 px-4 py-2 rounded-lg hover:bg-green-700">Dashboard</a>
                    <a href="logout.php" class="bg-red-600 px-4 py-2 rounded-lg hover:bg-red-700">Logout</a>
                    <a href="home.php" class="bg-blue-600 px-4 py-2 rounded-lg hover:bg-blue-700">Back to Store</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6" style="padding-top: 120px;"> <!-- Adjust padding to avoid overlap with fixed header -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-500 text-white p-4 rounded-lg mb-4">
                <?php echo $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); // Clear the message after displaying ?>
            </div>
        <?php endif; ?>
        <h2 class="text-2xl font-bold mb-4">Registered Users</h2>
        
        <!-- Search Form -->
        <form method="GET" class="flex space-x-2 mb-4">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
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

        <table class="w-full border border-gray-300">
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
                    <td colspan="7" class="py-4 text-center text-gray-500">No users found<?php echo !empty($search) ? " matching '" . htmlspecialchars($search) . "'" : ''; ?></td>
                </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <?php if ($user['role'] !== 'admin'): ?>
                    <tr class="border-t hover:bg-gray-50">
                      <td class="py-4 px-4"><?php echo htmlspecialchars($user['id']); ?></td>
                        <td class="py-4 px-4"><?php echo htmlspecialchars($user['fullname']); ?></td>
                        <td class="py-4 px-4"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="py-4 px-4"><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td class="py-4 px-4"><?php echo htmlspecialchars($user['role']); ?></td>
                        <td class="py-4 px-4"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td class="py-4 px-4">
                            <div class="flex space-x-2">
                                <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="bg-blue-500 text-white px-3 py-1 rounded-lg hover:bg-blue-600 transition duration-200">Edit</a>
                                <?php if ($user['role'] !== 'admin'): ?>
                                <a href="delete-user.php?id=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?')" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 transition duration-200">Delete</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 