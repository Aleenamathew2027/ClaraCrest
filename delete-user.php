<?php
session_start();
require 'dbconnect.php'; // Ensure this is before any usage of $conn

// Check if the user ID is provided
if (!isset($_GET['id'])) {
    die("User ID not specified.");
}

$user_id = $_GET['id'];

// Delete user
$delete_query = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$_SESSION['message'] = "User deleted successfully.";
header("Location: registereduser.php");
exit();
?> 