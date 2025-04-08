<?php
session_start();
require_once 'dbconnect.php';

// Set content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['in_wishlist' => false]);
    exit;
}

// Validate request
if (!isset($_GET['product_id'])) {
    echo json_encode(['in_wishlist' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = (int)$_GET['product_id'];

// Check if product is in wishlist
$check_wishlist = "SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($check_wishlist);
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();
$in_wishlist = ($result->num_rows > 0);
$stmt->close();

echo json_encode(['in_wishlist' => $in_wishlist]);
?> 