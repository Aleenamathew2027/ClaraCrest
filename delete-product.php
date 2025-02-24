<?php

// Establish database connection
$conn = new mysqli("localhost", "your_actual_username", "your_actual_password", "database_name");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $productId = intval($_GET['id']);
    
    // Prepare the SQL statement to delete the product
    $query = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productId);
    
    if ($stmt->execute()) {
        // Redirect back to products page with success message
        header("Location: products.php?success=1");
        exit();
    } else {
        echo '<div class="alert alert-danger">Error deleting product: ' . htmlspecialchars($stmt->error) . '</div>';
    }
    
    $stmt->close();
} 