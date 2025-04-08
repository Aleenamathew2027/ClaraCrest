<?php
// Prevent any whitespace or HTML before JSON output
ob_clean(); // Clear output buffer
header('Content-Type: application/json'); // Set JSON header

session_start();
require_once 'dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

if (!isset($_POST['product_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];
$action = $_POST['action'];

// Start transaction
$conn->begin_transaction();

try {
    // Get current quantity
    $query = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Product not found in cart');
    }
    
    $row = $result->fetch_assoc();
    $current_quantity = $row['quantity'];
    
    // Update quantity based on action
    if ($action === 'increment') {
        $new_quantity = $current_quantity + 1;
        
        // Update cart
        $update_query = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
        $update_stmt->execute();
        
        if ($update_stmt->affected_rows === 0) {
            throw new Exception('Failed to update cart');
        }
        
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Quantity updated in your shopping bag',
            'quantity' => $new_quantity
        ]);
    } else {
        throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Make sure nothing else is output after the JSON
exit;
?> 