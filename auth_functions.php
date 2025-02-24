<?php
// auth_functions.php - Create this as a separate file
function loginUser($email, $password, $conn) {
    try {
        $stmt = $conn->prepare("SELECT id, fullname, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Start session and set user data
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['loggedin'] = true;

                // Log login attempt
                logLogin($user['id'], $conn);

                return [
                    'success' => true,
                    'role' => $user['role']
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Login error: ' . $e->getMessage()
        ];
    }
}

function logLogin($user_id, $conn) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO login_logs (user_id, ip_address) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $ip);
    $stmt->execute();
}

function registerUser($fullname, $email, $phone, $password, $conn) {
    try {
        // Check if email exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            return [
                'success' => false,
                'message' => 'Email already exists'
            ];
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fullname, $email, $phone, $hashed_password);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Registration successful'
            ];
        } else {
            throw new Exception($stmt->error);
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Registration error: ' . $e->getMessage()
        ];
    }
}