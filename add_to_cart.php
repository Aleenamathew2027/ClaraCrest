<?php
// Disable output buffering
ob_clean();

// Start output buffering to catch any unexpected output
ob_start();

// Start session
session_start();

// Include database connection
require_once 'dbconnect.php';

// Enable error logging but disable display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Log request data
error_log("Add to cart request received: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Check if product_id is provided
if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    sendJsonResponse(['success' => false, 'error' => 'Product ID is required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_POST['product_id']);
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

error_log("Processing - User ID: $user_id, Product ID: $product_id, Quantity: $quantity");

// Verify user exists in database
$check_user = $conn->prepare("SELECT id FROM users WHERE id = ?");
if (!$check_user) {
    error_log("Prepare failed for check_user: " . $conn->error);
    sendJsonResponse(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit;
}

$check_user->bind_param("i", $user_id);
$check_user->execute();
$user_result = $check_user->get_result();

if ($user_result->num_rows === 0) {
    error_log("User not found in database: $user_id");
    sendJsonResponse(['success' => false, 'error' => 'User not found in database']);
    exit;
}
$check_user->close();

// Verify product exists
$check_product = $conn->prepare("SELECT id FROM products WHERE id = ?");
if (!$check_product) {
    error_log("Prepare failed for check_product: " . $conn->error);
    sendJsonResponse(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit;
}

$check_product->bind_param("i", $product_id);
$check_product->execute();
$product_result = $check_product->get_result();

if ($product_result->num_rows === 0) {
    error_log("Product not found: $product_id");
    sendJsonResponse(['success' => false, 'error' => 'Product not found']);
    exit;
}
$check_product->close();

try {
    // Check if product already in cart
    $check_cart = $conn->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
    if (!$check_cart) {
        error_log("Prepare failed for check_cart: " . $conn->error);
        sendJsonResponse(['success' => false, 'error' => 'Database error: ' . $conn->error]);
        exit;
    }
    
    $check_cart->bind_param("ii", $user_id, $product_id);
    $check_cart->execute();
    $cart_result = $check_cart->get_result();

    if ($cart_result->num_rows > 0) {
        // Update quantity if product already in cart
        $cart_item = $cart_result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;
        
        $update_cart = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        if (!$update_cart) {
            error_log("Prepare failed for update_cart: " . $conn->error);
            sendJsonResponse(['success' => false, 'error' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        $update_cart->bind_param("ii", $new_quantity, $cart_item['id']);
        
        if ($update_cart->execute()) {
            error_log("Cart updated successfully for item ID: " . $cart_item['id']);
            sendJsonResponse(['success' => true, 'message' => 'Cart updated successfully']);
        } else {
            error_log("Error updating cart: " . $update_cart->error);
            sendJsonResponse(['success' => false, 'error' => 'Failed to update cart: ' . $update_cart->error]);
        }
        $update_cart->close();
    } else {
        // Add new item to cart
        $insert_cart = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
        if (!$insert_cart) {
            error_log("Prepare failed for insert_cart: " . $conn->error);
            sendJsonResponse(['success' => false, 'error' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        $insert_cart->bind_param("iii", $user_id, $product_id, $quantity);
        
        if ($insert_cart->execute()) {
            error_log("New item added to cart. User ID: $user_id, Product ID: $product_id");
            sendJsonResponse(['success' => true, 'message' => 'Item added to cart successfully']);
        } else {
            error_log("Error adding item to cart: " . $insert_cart->error);
            sendJsonResponse(['success' => false, 'error' => 'Failed to add item to cart: ' . $insert_cart->error]);
        }
        $insert_cart->close();
    }
    $check_cart->close();
} catch (Exception $e) {
    error_log("Exception in cart operation: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
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