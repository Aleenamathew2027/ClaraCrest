<?php
session_start();
require_once 'dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->begin_transaction();

    // Generate unique order ID
    $order_id = 'ORD' . time() . rand(1000, 9999);
    
    // Get payment details
    $payment_id = $_POST['payment_id'];
    $amount = $_POST['amount'] / 100; // Convert back from paise to rupees
    $user_id = $_SESSION['user_id'];

    // Insert payment record
    $stmt = $conn->prepare("INSERT INTO payments (user_id, order_id, razorpay_payment_id, amount, status) 
                           VALUES (?, ?, ?, ?, 'completed')");
    $stmt->bind_param("issd", $user_id, $order_id, $payment_id, $amount);
    
    if (!$stmt->execute()) {
        throw new Exception("Error recording payment: " . $stmt->error);
    }

    // Get cart items
    $cart_query = "SELECT c.*, p.price 
                   FROM cart_items c 
                   JOIN products p ON c.product_id = p.id 
                   WHERE c.user_id = ?";
    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();

    // Debug log
    error_log("Processing order: " . $order_id);
    
    // Store order details for each cart item
    while ($item = $cart_result->fetch_assoc()) {
        // Debug log
        error_log("Processing item: " . print_r($item, true));

        // Insert into orders table
        $insert_order = $conn->prepare("INSERT INTO orders (user_id, order_id, product_id, quantity, price_at_time, order_status) 
                                      VALUES (?, ?, ?, ?, ?, 'processing')");
        
        $insert_order->bind_param("isiid", 
            $user_id, 
            $order_id, 
            $item['product_id'], 
            $item['quantity'], 
            $item['price']
        );
        
        if (!$insert_order->execute()) {
            throw new Exception("Error creating order: " . $insert_order->error);
        }

        // Debug log
        error_log("Order item inserted successfully");

        // Update product stock
        $update_stock = $conn->prepare("UPDATE products 
                                      SET stock_quantity = stock_quantity - ? 
                                      WHERE id = ?");
        $update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
        
        if (!$update_stock->execute()) {
            throw new Exception("Error updating stock: " . $update_stock->error);
        }
    }

    // Clear cart
    $clear_cart = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $clear_cart->bind_param("i", $user_id);
    
    if (!$clear_cart->execute()) {
        throw new Exception("Error clearing cart: " . $clear_cart->error);
    }

    // Commit transaction
    $conn->commit();
    
    // Debug log
    error_log("Order completed successfully: " . $order_id);

    echo json_encode([
        'success' => true,
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Log the error
    error_log("Payment processing error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 