<?php
session_start();
require_once 'dbconnect.php';

// Set content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get cart count
$sql = "SELECT SUM(quantity) as count FROM cart_items WHERE user_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// If no items in cart, return 0
$count = ($row['count'] === null) ? 0 : $row['count'];

echo json_encode(['success' => true, 'count' => $count]);

$stmt->close();
$conn->close();
?> 