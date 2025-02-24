<?php
session_start();
require 'dbconnect.php';

// Check if user is logged in (add your authentication check here)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to access this page.";
    header("Location: login.php");
    exit();
}

// Check if the user ID is provided and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: registereduser.php");
    exit();
}

$user_id = intval($_GET['id']);
$errors = [];
$success_message = '';

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fetch user details
try {
    $query = "SELECT id, fullname, email, phone, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error fetching user details.");
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "User not found.";
        header("Location: registereduser.php");
        exit();
    }
    
    $user = $result->fetch_assoc();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: registereduser.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize inputs
        $fullname = sanitize_input($_POST['fullname']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        $role = sanitize_input($_POST['role']);

        // Validation
        if (empty($fullname)) {
            $errors[] = "Full name is required.";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        
        if (empty($phone)) {
            $errors[] = "Phone number is required.";
        }
        
        if (!in_array($role, ['user', 'admin'])) {
            $errors[] = "Invalid role selected.";
        }

        // If no errors, proceed with update
        if (empty($errors)) {
            // Check if email exists for other users
            $email_check = "SELECT id FROM users WHERE email = ? AND id != ?";
            $check_stmt = $conn->prepare($email_check);
            $check_stmt->bind_param("si", $email, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $errors[] = "Email already exists for another user.";
            } else {
                // Update user details
                $update_query = "UPDATE users SET fullname = ?, email = ?, phone = ?, role = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ssssi", $fullname, $email, $phone, $role, $user_id);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Error updating user details.");
                }

                $_SESSION['success'] = "User updated successfully.";
                header("Location: registereduser.php");
                exit();
            }
        }
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <div class="max-w-2xl mx-auto">
            <h2 class="text-3xl font-bold mb-6">Edit User</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php foreach ($errors as $error): ?>
                        <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                <div class="mb-4">
                    <label for="fullname" class="block text-gray-700 text-sm font-bold mb-2">Full Name:</label>
                    <input 
                        type="text" 
                        name="fullname" 
                        id="fullname"
                        value="<?php echo htmlspecialchars($user['fullname']); ?>" 
                        required 
                        maxlength="100"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email"
                        value="<?php echo htmlspecialchars($user['email']); ?>" 
                        required 
                        maxlength="255"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>

                <div class="mb-4">
                    <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Phone:</label>
                    <input 
                        type="tel" 
                        name="phone" 
                        id="phone"
                        value="<?php echo htmlspecialchars($user['phone']); ?>" 
                        required 
                        pattern="[0-9+\-\(\)\s]+"
                        maxlength="20"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>

                <div class="mb-6">
                    <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Role:</label>
                    <select 
                        name="role" 
                        id="role"
                        required 
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <div class="flex items-center justify-between">
                    <button 
                        type="submit" 
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
                    >
                        Update User
                    </button>
                    <a 
                        href="registereduser.php" 
                        class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800 transition-colors duration-200"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Optional: Add client-side phone number formatting
    document.getElementById('phone').addEventListener('input', function(e) {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
        e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
    });
    </script>
</body>
</html>