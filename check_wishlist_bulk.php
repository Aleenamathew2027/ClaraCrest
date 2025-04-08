<?php
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['items' => []]);
    exit;
}

$user_id = $_SESSION['user_id'];

$db = Database::getInstance();
$conn = $db->getConnection();

// Get all products in user's wishlist
$stmt = $conn->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$wishlist_items = [];
while ($row = $result->fetch_assoc()) {
    $wishlist_items[] = $row['product_id'];
}

echo json_encode(['items' => $wishlist_items]);
?> 