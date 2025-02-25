<?php
require_once 'dbconnect.php';
$db = Database::getInstance();
$conn = $db->getConnection();

if (isset($_POST['category_id'])) {
    $category_id = intval($_POST['category_id']);
    $stmt = $conn->prepare("SELECT id, name FROM subcategories WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<option value="">Select a subcategory</option>';
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['name']) . "</option>";
    }

    $stmt->close();
}
?> 