<?php
session_start();
require_once 'dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

// Get data from POST
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$order_id = isset($_POST['order_id']) ? $_POST['order_id'] : '';
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';

// Validate data
if (!$product_id || !$rating || $rating > 5 || $rating < 1 || empty($review_text)) {
    echo json_encode(['success' => false, 'error' => 'Missing or invalid required fields']);
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// First verify that the product exists
$verify_query = "SELECT id FROM products WHERE id = ?";
$verify_stmt = $conn->prepare($verify_query);
$verify_stmt->bind_param("i", $product_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Product does not exist: ' . $product_id]);
    exit();
}

// Check if review already exists
$check_query = "SELECT id FROM product_reviews WHERE user_id = ? AND product_id = ? AND order_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("iis", $_SESSION['user_id'], $product_id, $order_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing review
    $row = $result->fetch_assoc();
    $review_id = $row['id'];
    
    $update_query = "UPDATE product_reviews SET rating = ?, review_text = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("isi", $rating, $review_text, $review_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update review: ' . $update_stmt->error]);
    }
} else {
    // Insert new review
    $insert_query = "INSERT INTO product_reviews (user_id, product_id, order_id, rating, review_text, status) VALUES (?, ?, ?, ?, ?, 'pending')";
    $insert_stmt = $conn->prepare($insert_query);

    // Add debug output
    if (!$insert_stmt) {
        echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    
    $insert_stmt->bind_param("iisis", $_SESSION['user_id'], $product_id, $order_id, $rating, $review_text);
    
    if (!$insert_stmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Failed to submit review: ' . $insert_stmt->error]);
    } else {
        echo json_encode(['success' => true]);
    }
}

exit();
?> 