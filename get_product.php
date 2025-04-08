<?php
// Include database connection
require_once 'dbconnect.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

$id = $_GET['id'];

// Get product details
$query = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $product = $result->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode($product);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Product not found']);
}
?> 