<?php
session_start();
require 'dbconnect.php'; // Ensure this is before any usage of $conn

// Check if the user ID is provided
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']); // Get the user ID from the query string

    // Prepare the delete statement
    $query = "DELETE FROM users WHERE id = ?";
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id); // Bind the user ID as an integer
        $stmt->execute();

        // Check if the deletion was successful
        if ($stmt->affected_rows > 0) {
            $_SESSION['message'] = "User deleted successfully.";
        } else {
            $_SESSION['message'] = "User not found or already deleted.";
        }
    } catch (Exception $e) {
        $_SESSION['message'] = "Error deleting user: " . $e->getMessage();
    }
} else {
    $_SESSION['message'] = "No user ID provided.";
}

// Redirect back to the registered users page
header("Location: registereduser.php");
exit();
?> 