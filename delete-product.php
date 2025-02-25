<?php
require_once 'dbconnect.php';

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $id = $_GET['id'];
    
    // First, check if the product exists
    $check_query = "SELECT id FROM products WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: products.php?error=product_not_found");
        exit();
    }
    
    // Delete the product
    $delete_query = "DELETE FROM products WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        header("Location: products.php?success=deleted");
    } else {
        header("Location: products.php?error=delete_failed");
    }
} catch (Exception $e) {
    header("Location: products.php?error=" . urlencode($e->getMessage()));
}
exit(); 