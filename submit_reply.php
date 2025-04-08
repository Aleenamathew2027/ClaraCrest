<?php
session_start();
require_once 'dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

// Get data from POST
$review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
$reply_text = isset($_POST['reply_text']) ? trim($_POST['reply_text']) : '';

// Validate data
if (!$review_id || empty($reply_text)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get a valid user ID - either the current user's ID if valid, or a manager ID if available
$user_id = $_SESSION['user_id'];

// Check if the user ID exists in the database
$check_query = "SELECT id FROM users WHERE id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

// If the user ID doesn't exist but user is a manager, find the first valid manager ID
if ($result->num_rows === 0 && $_SESSION['role'] === 'manager') {
    $manager_query = "SELECT id FROM users WHERE role = 'manager' LIMIT 1";
    $manager_result = $conn->query($manager_query);
    
    if ($manager_result && $manager_result->num_rows > 0) {
        $user_id = $manager_result->fetch_assoc()['id'];
    } else {
        // If no manager found, get first admin or any valid user
        $any_user_query = "SELECT id FROM users LIMIT 1";
        $any_user_result = $conn->query($any_user_query);
        
        if ($any_user_result && $any_user_result->num_rows > 0) {
            $user_id = $any_user_result->fetch_assoc()['id'];
        } else {
            echo json_encode(['success' => false, 'error' => 'No valid users found in the database']);
            exit();
        }
    }
}

// Insert reply into database with the valid user ID
$query = "INSERT INTO review_replies (review_id, user_id, reply_text) VALUES (?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iis", $review_id, $user_id, $reply_text);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false, 
        'error' => $stmt->error, 
        'details' => 'Failed to insert with user_id=' . $user_id
    ]);
}
?> 