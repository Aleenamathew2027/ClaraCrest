<?php
require_once 'dbconnect.php';
$db = Database::getInstance();
$conn = $db->getConnection();

// Debug output
error_log("fetch_subcategories.php called");

if(isset($_POST['category_id']) && !empty($_POST['category_id'])) {
    $category_id = intval($_POST['category_id']);
    error_log("Category ID received: " . $category_id);
    
    try {
        $stmt = $conn->prepare("SELECT id, name FROM subcategories WHERE category_id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo '<option value="">Error preparing statement</option>';
            exit;
        }
        
        $stmt->bind_param("i", $category_id);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            echo '<option value="">Error executing query</option>';
            exit;
        }
        
        $result = $stmt->get_result();
        error_log("Query executed, found " . $result->num_rows . " subcategories");
        
        $output = '<option value="">Select a subcategory</option>';
        
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $output .= '<option value="' . htmlspecialchars($row['id']) . '">' . 
                           htmlspecialchars($row['name']) . '</option>';
            }
        } else {
            $output = '<option value="">No subcategories found</option>';
        }
        
        echo $output;
        
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        echo '<option value="">Error loading subcategories</option>';
    }
} else {
    error_log("No category_id received or empty value");
    echo '<option value="">Invalid category</option>';
}
?> 