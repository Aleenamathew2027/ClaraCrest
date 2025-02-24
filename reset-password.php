<?php
session_start();
require 'dbconnect.php';

// Check if user is verified
if (!isset($_SESSION['email']) || !isset($_SESSION['code_verified'])) {
    header("Location: forgot-password.php");
    exit();
}

if (isset($_POST['reset_password'])) {
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $email = $_SESSION['email'];

    // Validate password
    if (strlen($password) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } else {
        // Hash the password using MD5 to match existing login system
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password and clear reset token
        $query = "UPDATE users SET password='$hashed_password', reset_token=NULL WHERE email='$email'";
        if (mysqli_query($conn, $query)) {
            // Clear all session variables
            session_unset();
            session_destroy();
            
            // Start new session for success message
            session_start();
            $_SESSION['success'] = "Password reset successful. Please login with your new password.";
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to reset password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ClaraCrest</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-center mb-8">Reset Password</h2>
            
            <?php
            if (isset($_SESSION['error'])) {
                echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>{$_SESSION['error']}</div>";
                unset($_SESSION['error']);
            }
            ?>
            
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" 
                           name="password" 
                           class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                           required
                           minlength="6"
                           autocomplete="new-password">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input type="password" 
                           name="confirm_password" 
                           class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                           required
                           minlength="6"
                           autocomplete="new-password">
                </div>

                <button type="submit" 
                        name="reset_password" 
                        class="w-full bg-black text-white px-8 py-3 rounded-full hover:bg-gray-800">
                    Reset Password
                </button>
            </form>
        </div>
    </div>
</body>
</html>