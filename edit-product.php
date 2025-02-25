<?php
require_once 'dbconnect.php';

$db = Database::getInstance();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $subcategory_id = $_POST['subcategory_id'];
    
    $query = "UPDATE products SET 
              name = ?, 
              price = ?, 
              description = ?, 
              category_id = ?, 
              subcategory_id = ? 
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sdsssi", $name, $price, $description, $category_id, $subcategory_id, $id);
    
    if ($stmt->execute()) {
        header("Location: products.php?success=updated");
        exit();
    } else {
        $error = "Error updating product: " . $conn->error;
    }
} else {
    // Fetch product data for editing
    if (!isset($_GET['id'])) {
        header("Location: products.php");
        exit();
    }
    
    $id = $_GET['id'];
    $query = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if (!$product) {
        header("Location: products.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            line-height: 1.6;
        }

        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            border-color: #2ecc71;
        }

        .btn-submit {
            background-color: #2ecc71;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: #27ae60;
        }

        @media (max-width: 600px) {
            .form-container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Edit Product</h2>
        <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
        
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
            
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo $product['price']; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <?php
                    $categories = $conn->query("SELECT * FROM categories");
                    while ($category = $categories->fetch_assoc()) {
                        $selected = ($category['id'] == $product['category_id']) ? 'selected' : '';
                        echo "<option value='{$category['id']}' $selected>{$category['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="subcategory_id">Subcategory</label>
                <select id="subcategory_id" name="subcategory_id" required>
                    <?php
                    $subcategories = $conn->query("SELECT * FROM subcategories");
                    while ($subcategory = $subcategories->fetch_assoc()) {
                        $selected = ($subcategory['id'] == $product['subcategory_id']) ? 'selected' : '';
                        echo "<option value='{$subcategory['id']}' $selected>{$subcategory['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            
            <button type="submit" class="btn-submit">Update Product</button>
        </form>
    </div>
</body>
</html> 