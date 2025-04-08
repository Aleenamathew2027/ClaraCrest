<?php
// Start session
session_start();

// Include database connection
require_once 'dbconnect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to your wishlist.']);
    exit;
}

// Check if product ID is provided
if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];

// Check if the product is already in the wishlist
$stmt = $conn->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Product is already in wishlist, remove it
    $delete_stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $delete_stmt->bind_param("ii", $user_id, $product_id);
    
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove from wishlist.']);
    }
} else {
    // Product is not in wishlist, add it
    $insert_stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id, date_added) VALUES (?, ?, NOW())");
    $insert_stmt->bind_param("ii", $user_id, $product_id);
    
    if ($insert_stmt->execute()) {
        echo json_encode(['success' => true, 'action' => 'added']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to wishlist.']);
    }
}

// Get the total count of items in wishlist for this user
$count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();

echo json_encode(['success' => true, 'count' => $count_row['count']]);
?> 