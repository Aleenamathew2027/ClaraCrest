<?php
session_start();
require 'dbconnect.php';
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if (isset($_POST['submit'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $code = rand(100000, 999999);

    // Check if email exists in database
    $check_email = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $check_email);

    if (mysqli_num_rows($result) > 0) {
        // Update verification code in database
        $update_code = "UPDATE users SET reset_token='$code' WHERE email='$email'";
        mysqli_query($conn, $update_code);

        // Configure PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'aleenamathew305@gmail.com'; // Replace with your Gmail address
            $mail->Password = 'xywa lpjq qoqq hzgf'; // Replace with the app password generated
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Recipients
            $mail->setFrom('aleenamathew2027@mca.ajce.in', 'ClaraCrest'); // Use same Gmail address
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Verification Code';
            $mail->Body = "Your verification code is: <b>$code</b>";

            $mail->send();
            
            // Store email and code in session
            $_SESSION['email'] = $email;
            $_SESSION['reset_code'] = $code;
            
            // Debug line - remove in production
            echo "Email sent successfully. Redirecting...";
            
            // Explicit redirection
            echo "<script>window.location.href = 'verify-code.php';</script>";
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to send verification code. Error: {$mail->ErrorInfo}";
            header("Location: forgot-password.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Email not found in our records.";
        header("Location: forgot-password.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ClaraCrest</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-center mb-8">Forgot Password</h2>
            
            <?php
            if (isset($_SESSION['error'])) {
                echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4'>{$_SESSION['error']}</div>";
                unset($_SESSION['error']);
            }
            ?>
            
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" 
                           name="email" 
                           class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                           required
                           autocomplete="off">
                </div>

                <button type="submit" 
                        name="submit" 
                        class="w-full bg-black text-white px-8 py-3 rounded-full hover:bg-gray-800">
                    Send Reset Code
                </button>
                
                <div class="text-center">
                    <a href="login.php" class="text-sm text-gray-600 hover:text-black">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>