<?php
// Disable output buffering
ob_clean();

// Start output buffering to catch any unexpected output
ob_start();

// Enable error reporting for debugging (but don't display errors)
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

// Log all incoming data
error_log("update_wishlist.php called with POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Validate input
if (!isset($_POST['product_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $action = $_POST['action'];

    // Debug log
    error_log("Wishlist Update - User ID: $user_id, Product ID: $product_id, Action: $action");

    // Verify user exists
    $user_check = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $user_check->bind_param("i", $user_id);
    $user_check->execute();
    $user_result = $user_check->get_result();
    
    if ($user_result->num_rows === 0) {
        throw new Exception("Invalid user ID");
    }

    // Verify product exists
    $product_check = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $product_check->bind_param("i", $product_id);
    $product_check->execute();
    $product_result = $product_check->get_result();
    
    if ($product_result->num_rows === 0) {
        throw new Exception("Invalid product ID");
    }

    if ($action === 'add') {
        // Check if already in wishlist
        $check_stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check_stmt->bind_param("ii", $user_id, $product_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Add to wishlist
            $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id, date_added) VALUES (?, ?, NOW())");
            $stmt->bind_param("ii", $user_id, $product_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to add to wishlist: " . $stmt->error);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
    } 
    elseif ($action === 'remove') {
        // Remove from wishlist
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to remove from wishlist: " . $stmt->error);
        }
        
        echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
    } 
    else {
        throw new Exception("Invalid action");
    }

} catch (Exception $e) {
    error_log("Wishlist Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();

// Function to send clean JSON response
function sendJsonResponse($data) {
    // Clear any previous output
    ob_end_clean();
    
    // Set content type
    header('Content-Type: application/json');
    
    // Encode and output JSON
    echo json_encode($data);
    exit;
}
?> 