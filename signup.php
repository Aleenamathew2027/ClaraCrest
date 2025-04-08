<?php
require_once 'dbconnect.php';

$username = $email = $number = $country_code = $password = $confirmPassword = $role = "";
$usernameErr = $emailErr = $numberErr = $passwordErr = $confirmPasswordErr = "";
$databaseErr = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $country_code = trim($_POST['country_code']);
    $number = trim($_POST['number']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];
    $role = isset($_POST['role']) ? $_POST['role'] : 'buyer'; // Default to buyer if not set

    // Combine country code and phone number
    $full_phone = $country_code . $number;

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
        // Check if email or phone number already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ? OR phone = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("sss", $email, $username, $full_phone);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if ($result->num_rows > 0) {
                $databaseErr = "Email, username, or phone number already exists!";
            } else {
                // Hash password before storing
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert into database
                $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, role, username) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ssssss", $username, $email, $full_phone, $hashedPassword, $role, $username);
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6" autocomplete="off" onsubmit="return validateForm()">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" name="username" id="username"
                        class="input-field mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                        value="<?php echo htmlspecialchars($username); ?>"
                        onblur="validateUsername()">
                    <p id="usernameError" class="mt-1 text-sm text-red-600"></p>
                    <?php if (!empty($usernameErr)): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $usernameErr; ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="email"
                        class="input-field mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                        value="<?php echo htmlspecialchars($email); ?>">
                    <p id="emailError" class="mt-1 text-sm text-red-600"></p>
                    <?php if (!empty($emailErr)): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $emailErr; ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Mobile Number</label>
                    <div class="flex">
                        <select name="country_code" id="country_code" class="input-field mt-1 block w-1/3 px-2 py-3 border border-gray-300 rounded-l-md shadow-sm focus:ring-black focus:border-black">
                            <option value="+91">+91 (India)</option>
                            <option value="+1">+1 (USA/Canada)</option>
                            <option value="+44">+44 (UK)</option>
                            <option value="+61">+61 (Australia)</option>
                            <option value="+65">+65 (Singapore)</option>
                            <option value="+971">+971 (UAE)</option>
                            <option value="+86">+86 (China)</option>
                            <option value="+49">+49 (Germany)</option>
                            <option value="+33">+33 (France)</option>
                            <option value="+81">+81 (Japan)</option>
                            <option value="+82">+82 (South Korea)</option>
                            <option value="+7">+7 (Russia)</option>
                        </select>
                        <input type="tel" name="number" id="phone"
                            class="input-field mt-1 block w-2/3 px-4 py-3 border border-gray-300 rounded-r-md shadow-sm focus:ring-black focus:border-black"
                            value="<?php echo htmlspecialchars($number); ?>"
                            onblur="validatePhone()">
                    </div>
                    <p id="phoneError" class="mt-1 text-sm text-red-600"></p>
                    <?php if (!empty($numberErr)): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $numberErr; ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" 
                           name="password" 
                           id="password"
                           class="input-field mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                           onblur="validatePassword()"
                           onpaste="return false"
                           oncopy="return false"
                           oncut="return false"
                           autocomplete="new-password">
                    <p id="passwordError" class="mt-1 text-sm text-red-600"></p>
                    <?php if (!empty($passwordErr)): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $passwordErr; ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" 
                           name="confirm-password" 
                           id="confirmPassword"
                           class="input-field mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black"
                           onblur="validateConfirmPassword()"
                           onpaste="return false"
                           oncopy="return false"
                           oncut="return false"
                           autocomplete="new-password">
                    <p id="confirmPasswordError" class="mt-1 text-sm text-red-600"></p>
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

    <script>
        $(document).ready(function() {
            var isEmailBeingValidated = false;

            // Trigger validation on input event for live feedback
            $('#email').on('input', function() {
                var email = $(this).val();
                if (!isEmailBeingValidated) {
                    validateEmail(email);
                }
            });

            $('#username').on('input', validateUsername);
            $('#phone').on('input', validatePhone);
            $('#password').on('input', validatePassword);
            $('#confirmPassword').on('input', validateConfirmPassword);

            function validateEmail() {
                var email = $('#email').val();
                var emailError = $('#emailError');
                emailError.text("");

                if (email.trim() === "") {
                    emailError.text("Email is required");
                    return false;
                }
                if (email.includes(" ")) {
                    emailError.text("Email cannot contain spaces");
                    return false;
                }

                var emailRegex = /^[a-z][a-z0-9]*(?:[][a-z0-9]+)*@(gmail\.com|yahoo\.com)$/;
                if (!emailRegex.test(email)) {
                    emailError.text("Enter a valid email address");
                    return false;
                }

                $.ajax({
                    type: "POST",
                    url: "check_availability.php",
                    data: { 'email': email },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.status === 'exists') {
                            emailError.text('Email already exists');
                        }
                    }
                });

                return true;
            }

            function validateUsername() {
                var username = $('#username').val();
                var usernameError = $('#usernameError');
                usernameError.text("");

                if (username.trim() === "") {
                    usernameError.text("Username is required");
                    return false;
                }

                if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                    usernameError.text("Username can only contain letters, numbers, and underscores");
                    return false;
                }

                $.ajax({
                    type: "POST",
                    url: "check_availability.php",
                    data: { 'username': username },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.status === 'exists') {
                            usernameError.text('Username already exists');
                        }
                    }
                });

                return true;
            }

            function validatePhone() {
                var countryCode = $('#country_code').val();
                var phone = $('#phone').val();
                var phoneError = $('#phoneError');
                phoneError.text("");

                if (phone.trim() === "") {
                    phoneError.text("Phone number is required");
                    return false;
                }

                // Adjust validation based on country code
                if (countryCode === "+91") {
                    // Indian phone number validation
                    var phoneRegex = /^[6789]\d{9}$/;
                    if (!phoneRegex.test(phone)) {
                        phoneError.text("Enter a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9");
                        return false;
                    }
                } else {
                    // Generic validation for other countries
                    if (!/^\d{6,15}$/.test(phone)) {
                        phoneError.text("Enter a valid phone number (6-15 digits)");
                        return false;
                    }
                }

                var repeatingDigitsRegex = /(\d)\1{9}/;
                if (repeatingDigitsRegex.test(phone)) {
                    phoneError.text("Phone number cannot contain all repeating digits");
                    return false;
                }

                // When checking availability, use the full phone number
                $.ajax({
                    type: "POST",
                    url: "check_availability.php",
                    data: { 'number': countryCode + phone },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.status === 'exists') {
                            phoneError.text('Phone number already exists');
                        }
                    }
                });

                return true;
            }

            function validatePassword() {
                var password = $('#password').val();
                var passwordError = $('#passwordError');
                passwordError.text("");

                if (password.trim() === "") {
                    passwordError.text("Password is required");
                    return false;
                }

                if (/\s/.test(password)) {
                    passwordError.text("Password cannot contain spaces");
                    return false;
                }

                if (password.length < 6) {
                    passwordError.text("Password must be at least 6 characters long");
                    return false;
                }

                var hasUppercase = /[A-Z]/.test(password);
                var hasLowercase = /[a-z]/.test(password);
                var hasDigit = /\d/.test(password);
                var hasSpecialChar = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/.test(password);

                if (!(hasUppercase && hasLowercase && hasDigit && hasSpecialChar)) {
                    passwordError.text("Password must contain uppercase, lowercase, number, and special character");
                    return false;
                }

                return true;
            }

            function validateConfirmPassword() {
                var password = $('#password').val();
                var confirmPassword = $('#confirmPassword').val();
                var confirmPasswordError = $('#confirmPasswordError');
                confirmPasswordError.text("");

                if (confirmPassword.trim() === "") {
                    confirmPasswordError.text("Confirm Password is required");
                    return false;
                }

                if (password !== confirmPassword) {
                    confirmPasswordError.text("Passwords do not match");
                    return false;
                }

                return true;
            }

            function validateForm() {
                var isEmailValid = validateEmail();
                var isUsernameValid = validateUsername();
                var isPhoneValid = validatePhone();
                var isPasswordValid = validatePassword();
                var isConfirmPasswordValid = validateConfirmPassword();

                return isEmailValid && isUsernameValid && isPhoneValid && 
                       isPasswordValid && isConfirmPasswordValid;
            }

            // Update form submission
            $('form').on('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>