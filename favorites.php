<?php
session_start();
require_once 'dbconnect.php'; // Ensure this file is correct

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get wishlist items
$user_id = $_SESSION['user_id'];
$sql = "SELECT p.* FROM products p 
        JOIN wishlist w ON p.id = w.product_id 
        WHERE w.user_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Include header
include 'header.php';
?>

<div class="container mx-auto px-6 py-24">
    <h1 class="text-4xl font-bold mb-10">My Favorites</h1>
    
    <?php if ($result->num_rows === 0): ?>
        <div class="text-center py-10">
            <i class="fas fa-heart text-gray-300 text-6xl mb-4"></i>
            <p class="text-xl text-gray-500">Your wishlist is empty.</p>
            <a href="collection.php" class="mt-4 inline-block bg-green-600 text-white px-6 py-3 rounded-full">Browse Collection</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="watch-card bg-white p-6 rounded-lg shadow-lg relative">
                    <button class="wishlist-btn absolute top-3 left-3 text-green-600 hover:text-green-800 z-10" 
                            data-product-id="<?php echo $row['id']; ?>">
                        <i class="fas fa-heart text-green-600 filled"></i>
                    </button>
                    
                    <img src="<?php echo $row['image_url']; ?>" alt="<?php echo $row['name']; ?>" class="w-full rounded-lg mb-4">
                    <h3 class="text-xl font-bold mb-2"><?php echo $row['name']; ?></h3>
                    <p class="text-gray-600 mb-4"><?php echo $row['description']; ?></p>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold">₹<?php echo number_format($row['price']); ?></span>
                        <button class="add-to-bag bg-black text-white px-6 py-2 rounded-full" 
                               data-product-id="<?php echo $row['id']; ?>">
                            Add to Cart
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<script>
    // Add to cart functionality
    document.querySelectorAll('.add-to-bag').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            
            // Send AJAX request to add item to cart
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to bag page
                    window.location.href = 'bag.php';
                } else {
                    alert('Error adding item to bag: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding to cart. Please try again.');
            });
        });
    });
    
    // Wishlist functionality
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            
            // Send AJAX request to remove from wishlist
            fetch('update_wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&action=remove'
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Remove product card from page
                    this.closest('.watch-card').remove();
                    
                    // If no more items in wishlist, reload to show empty state
                    if (document.querySelectorAll('.watch-card').length === 0) {
                        location.reload();
                    }
                } else {
                    alert('Error removing from wishlist: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error removing from wishlist. Please try again.');
            });
        });
    });
</script> 