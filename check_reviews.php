<?php
session_start();
require_once 'dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get product IDs and order ID from POST
$product_ids = json_decode($_POST['product_ids'], true);
$order_id = $_POST['order_id'];

// Initialize response data
$response = [
    'showReview' => false,
    'firstUnreviewedIndex' => null
];

// Check each product to see if it's been reviewed from this order
foreach ($product_ids as $index => $product_id) {
    $query = "SELECT id FROM reviews WHERE user_id = ? AND product_id = ? AND order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $_SESSION['user_id'], $product_id, $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If no review exists for this product from this order
    if ($result->num_rows == 0 && $response['firstUnreviewedIndex'] === null) {
        $response['showReview'] = true;
        $response['firstUnreviewedIndex'] = $index;
    }
}

echo json_encode($response);
?> 