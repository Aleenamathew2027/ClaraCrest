<?php
session_start();
require 'dbconnect.php';

// Check if email is set in session
if (!isset($_SESSION['email'])) {
    header("Location: forgot-password.php");
    exit();
}

if (isset($_POST['verify_code'])) {
    $code = mysqli_real_escape_string($conn, $_POST['code']);
    $email = $_SESSION['email'];

    // Verify the code
    $query = "SELECT * FROM users WHERE email='$email' AND reset_token='$code'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        // Code is correct, redirect to reset password page
        $_SESSION['code_verified'] = true;
        header("Location: reset-password.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid verification code.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - ClaraCrest</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-center mb-8">Verify Code</h2>
            
            <?php
            if (isset($_SESSION['error'])) {
                echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>{$_SESSION['error']}</div>";
                unset($_SESSION['error']);
            }
            ?>
            
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Enter Verification Code</label>
                    <input type="text" 
                           name="code" 
                           class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                           required
                           autocomplete="off">
                </div>

                <button type="submit" 
                        name="verify_code" 
                        class="w-full bg-black text-white px-8 py-3 rounded-full hover:bg-gray-800">
                    Verify Code
                </button>
                
                <div class="text-center">
                    <a href="forgot-password.php" class="text-sm text-gray-600 hover:text-black">Resend Code</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>