<?php
// login.php
session_start();
require 'dbconnect.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Check for manager credentials
        if ($email === 'manager123@gmail.com' && $password === 'manager@123') {
            $_SESSION['user_id'] = 'manager';
            $_SESSION['fullname'] = 'Manager';
            $_SESSION['role'] = 'manager';
            $_SESSION['loggedin'] = true;
            
            // Log the manager login
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt = $conn->prepare("INSERT INTO login_logs (user_id, ip_address) VALUES ('manager', ?)");
            $log_stmt->bind_param("s", $ip);
            $log_stmt->execute();
            
            header("Location: manager-dashboard.php");
            exit();
        }

        // Debugging: Check if the email and password are being received correctly
        error_log("Email: $email, Password: $password");

       
        
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['fullname'] = $row['fullname'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['loggedin'] = true;
                
                // Log the login
                $ip = $_SERVER['REMOTE_ADDR'];
                $user_id = $row['id'];
                $log_stmt = $conn->prepare("INSERT INTO login_logs (user_id, ip_address) VALUES (?, ?)");
                $log_stmt->bind_param("is", $user_id, $ip);
                $log_stmt->execute();
                
                // Check for admin or manager role
                if ($row['role'] === 'admin') {
                    header("Location: admin-dashboard.php");
                    exit();
                } elseif ($row['role'] === 'manager') {
                    header("Location: manager-dashboard.php");
                    exit();
                } else {
                    header("Location: home.php");
                    exit();
                }
            } else {
                $error_message = "Invalid email or password";
            }
        } else {
            $error_message = "Invalid email or password";
        }
    } catch (Exception $e) {
        $error_message = "An error occurred. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ClaraCrest</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        .login-background {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.8)),
                        url('image/wat4.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .form-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .nav-blur {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .input-field {
            transition: all 0.3s ease;
        }

        .input-field:focus {
            transform: translateX(5px);
        }

        .submit-button {
            transition: all 0.3s ease;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body class="bg-gray-50">
    <nav class="nav-blur fixed w-full z-50 shadow-lg">
        <div class="container mx-auto px-6 py-4 flex items-center justify-between">
            <a href="home.php" class="text-2xl font-bold text-gray-800">ClaraCrest</a>
            <a href="home.php" class="text-gray-600 hover:text-gray-900 transition-colors duration-300">Back to Home</a>
        </div>
    </nav>

    <div class="login-background min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full form-container rounded-xl shadow-lg p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900">Welcome Back</h2>
                <p class="mt-2 text-gray-600">Please login to your account</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="mb-4 p-4 text-red-700 bg-red-100 rounded-md">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" 
                           name="email" 
                           class="input-field mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                           required
                           autocomplete="off">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" 
                           name="password" 
                           class="input-field mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                           required
                           autocomplete="new-password">
                </div>

                <button type="submit" class="submit-button w-full bg-black text-white px-8 py-3 rounded-full hover:bg-gray-800">Login</button>

                <div class="text-center mt-4 space-y-2">
                    <div>
                        <a href="forgot-password.php" class="text-black hover:underline">Forgot Password?</a>
                    </div>
                    <div>
                        <span class="text-gray-600">Don't have an account?</span>
                        <a href="signup.php" class="text-black hover:underline ml-1">Sign up now</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>