<?php
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['items' => []]);
    exit;
}

$user_id = $_SESSION['user_id'];
$db = Database::getInstance();
$conn = $db->getConnection();

$query = "SELECT c.product_id, c.quantity, p.name, p.price, p.image_url, 
          COALESCE(
              (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1),
              p.image_url
          ) as primary_image_url
          FROM cart c
          JOIN products p ON c.product_id = p.id
          WHERE c.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    // Determine the correct image URL
    $image_url = !empty($row['primary_image_url']) ? $row['primary_image_url'] : $row['image_url'];
    
    // Fix relative image paths if needed
    if (!empty($image_url)) {
        if (strpos($image_url, 'http') !== 0 && strpos($image_url, '/') !== 0) {
            if (strpos($image_url, './') === 0) {
                $image_url = substr($image_url, 1);
            } elseif (strpos($image_url, 'uploads/') !== 0) {
                $image_url = 'uploads/' . $image_url;
            }
        }
    } else {
        $image_url = 'placeholder.jpg';
    }
    
    $items[] = [
        'id' => $row['product_id'],
        'name' => $row['name'],
        'price' => $row['price'],
        'quantity' => $row['quantity'],
        'image_url' => $image_url
    ];
}

echo json_encode(['items' => $items]);
?> 