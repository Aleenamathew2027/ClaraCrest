<?php
session_start();
require_once 'dbconnect.php'; // Ensure you have your database connection

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the user ID from the session
    $user_id = $_SESSION['user_id'];

    // Get the form data
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $district = $_POST['district'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    $gender = $_POST['gender'];

    // Handle image upload if a new image is uploaded
    $profile_image = null;
    if (isset($_FILES['user_image']) && $_FILES['user_image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["user_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is valid
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES["user_image"]["tmp_name"], $target_file)) {
                $profile_image = $target_file; // Store the path of the uploaded image
            }
        }
    }

    // Prepare the SQL statement
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Update the user details in the database
    $query = "UPDATE users SET fullname = ?, username = ?, email = ?, phone = ?, address = ?, city = ?, district = ?, state = ?, pincode = ?, gender = ?, profile_image = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssssssssi", $fullname, $username, $email, $phone, $address, $city, $district, $state, $pincode, $gender, $profile_image, $user_id);

    // Execute the statement
    if ($stmt->execute()) {
        // Redirect to home.php after successful update
        header("Location: home.php");
        exit();
    } else {
        echo "Error updating record: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?> 