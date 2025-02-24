<?php
require_once 'dbconnect.php';

$username = $email = $number = $password = $confirmPassword = $role = "";
$usernameErr = $emailErr = $numberErr = $passwordErr = $confirmPasswordErr = "";
$databaseErr = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $number = trim($_POST['number']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];
    $role = isset($_POST['role']) ? $_POST['role'] : 'buyer'; // Default to buyer if not set

    // Basic validation
    if (empty($username)) {
        $usernameErr = "Username is required.";
    }
    if (empty($email)) {
        $emailErr = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Invalid email format.";
    }
    if (empty($number)) {
        $numberErr = "Mobile number is required.";
    } elseif (!preg_match('/^[6789]\d{9}$/', $number)) {
        $numberErr = "Invalid mobile number format.";
    }
    if (empty($password)) {
        $passwordErr = "Password is required.";
    } elseif (strlen($password) < 6) {
        $passwordErr = "Password must be at least 6 characters.";
    }
    if ($password !== $confirmPassword) {
        $confirmPasswordErr = "Passwords do not match.";
    }

    // Proceed only if there are no errors
    if (empty($usernameErr) && empty($emailErr) && empty($numberErr) && empty($passwordErr) && empty($confirmPasswordErr)) {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("ss", $email, $username);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if ($result->num_rows > 0) {
                $databaseErr = "Email or username already exists!";
            } else {
                // Hash password before storing
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert into database
                $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, role, username) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ssssss", $username, $email, $number, $hashedPassword, $role, $username);
                    if ($stmt->execute()) {
                        header("Location: login.php");
                        exit();
                    } else {
                        $databaseErr = "Error: " . $stmt->error;
                    }
                } else {
                    $databaseErr = "Prepare statement failed: " . $conn->error;
                }
            }
        } else {
            $databaseErr = "Prepare statement failed: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClaraCrest Signup</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        .login-background {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.8)),
                        url('image/wat3.jpg') no-repeat center center fixed;
            background-size: cover;
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
<body>
    <nav class="nav-blur fixed w-full z-50 shadow-lg">
        <div class="container mx-auto px-6 py-4 flex items-center justify-between">
            <a href="home.php" class="text-2xl font-bold text-gray-800">ClaraCrest</a>
            <a href="home.php" class="text-gray-600 hover:text-gray-900 transition-colors duration-300">Back to Home</a>
        </div>
    </nav>

    <div class="login-background min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full form-container rounded-xl shadow-lg p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900">SIGN UP</h2>
                <p class="mt-2 text-gray-600">ClaraCrest</p>
            </div>

            <?php if (!empty($databaseErr)): ?>
                <div class="mb-4 p-4 text-red-700 bg-red-100 rounded-md">
                    <?php echo $databaseErr; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6" autocomplete="off">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" name="username" 
                        class="input-field mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                        value="<?php echo htmlspecialchars($username); ?>">
                    <?php if (!empty($usernameErr)): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $usernameErr; ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" 
                        class="input-field mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                        value="<?php echo htmlspecialchars($email); ?>">
                    <?php if (!empty($emailErr)): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $emailErr; ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Mobile Number</label>
                    <input type="tel" name="number" 
                        class="input-field mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                        value="<?php echo htmlspecialchars($number); ?>">
                    <?php if (!empty($numberErr)): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $numberErr; ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" 
                           name="password" 
                           class="input-field mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                           onpaste="return false"
                           oncopy="return false"
                           oncut="return false"
                           autocomplete="new-password"
                           >
                    <?php if (!empty($passwordErr)): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $passwordErr; ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" 
                           name="confirm-password" 
                           class="input-field mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                           onpaste="return false"
                           oncopy="return false"
                           oncut="return false"
                           autocomplete="new-password"
                           >
                    <?php if (!empty($confirmPasswordErr)): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $confirmPasswordErr; ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit" 
                    class="submit-button w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                    Create Account
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-600">
                Already have an account? 
                <a href="login.php" class="font-medium text-black hover:text-gray-800 transition-colors duration-300">Login</a>
            </div>
        </div>
    </div>
</body>
</html>