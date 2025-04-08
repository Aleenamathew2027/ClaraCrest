<?php
session_start();
require_once 'dbconnect.php';

// Get the search query
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Initialize results array
$products = [];

// If there's a search query, fetch results
if (!empty($search_query)) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Create search query with wildcards for partial matching
        $sql = "SELECT * FROM products WHERE 
                name LIKE ? OR 
                description LIKE ? OR 
                brand LIKE ?";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $search_param = "%" . $search_query . "%";
            $stmt->bind_param("sss", $search_param, $search_param, $search_param);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            
            $stmt->close();
        } else {
            $error = "Error preparing search query: " . $conn->error;
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Include header
include 'header.php';
?>

<style>
    .watch-card {
        transition: transform 0.3s ease;
    }

    .watch-card:hover {
        transform: translateY(-10px);
    }
    
    /* Copy other relevant styles from home.php */
</style>

<!-- Search Results Section -->
<section class="py-20 mt-16">
    <div class="container mx-auto px-6">
        <h2 class="text-4xl font-bold text-center mb-8">Search Results</h2>
        <p class="text-center text-lg mb-12">
            <?php 
            if (!empty($search_query)) {
                echo "Showing results for: <span class='font-semibold'>\"$search_query\"</span>";
            } else {
                echo "Please enter a search term";
            }
            ?>
        </p>

        <?php if (isset($error)): ?>
            <div class="text-red-500 text-center mb-8"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($products) && !empty($search_query)): ?>
            <div class="text-center py-12">
                <p class="text-2xl font-semibold mb-4">No watches found</p>
                <p class="text-gray-600">Try a different search term or browse our collection.</p>
                <a href="collection.php" class="mt-6 inline-block bg-green-600 text-white px-6 py-3 rounded-full hover:bg-green-700 transition duration-300">Browse Collection</a>
            </div>
        <?php elseif (!empty($products)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <?php foreach ($products as $product): ?>
                    <div class="watch-card bg-white p-4 rounded-lg shadow-lg">
                        <div class="wishlist-icon" data-product-id="<?php echo $product['id']; ?>">
                            <i class="far fa-heart"></i>
                        </div>
                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="block">
                            <img src="<?php echo $product['image_url'] ?? 'image/default-watch.jpg'; ?>" 
                                alt="<?php echo $product['name']; ?>" 
                                class="w-full h-48 object-cover rounded-lg mb-3">
                            <h3 class="text-lg font-bold mb-1"><?php echo $product['name']; ?></h3>
                            <p class="text-gray-600 mb-3 text-sm"><?php echo substr($product['description'], 0, 70) . (strlen($product['description']) > 70 ? '...' : ''); ?></p>
                        </a>
                        <div class="flex justify-between items-center">
                            <span class="text-xl font-bold">₹<?php echo number_format($product['price']); ?></span>
                            <button onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>, '<?php echo addslashes($product['image_url'] ?? 'image/default-watch.jpg'); ?>')" 
                                class="bg-black text-white px-4 py-1 rounded-full text-sm">
                                Add To Bag
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Begin footer section -->
<footer class="bg-gray-900 text-white py-12">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-xl font-semibold mb-4">ClaraCrest</h3>
                <p class="text-gray-400">Your destination for luxury timepieces that blend elegance, precision, and heritage.</p>
            </div>
            <div>
                <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                <ul class="space-y-2">
                    <li><a href="home.php" class="text-gray-400 hover:text-white">Home</a></li>
                    <li><a href="collection.php" class="text-gray-400 hover:text-white">Collections</a></li>
                    <li><a href="insurance.php" class="text-gray-400 hover:text-white">Insurance</a></li>
                    <li><a href="about.php" class="text-gray-400 hover:text-white">About Us</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-lg font-semibold mb-4">Customer Service</h4>
                <ul class="space-y-2">
                    <li><a href="contact.php" class="text-gray-400 hover:text-white">Contact Us</a></li>
                    <li><a href="shipping.php" class="text-gray-400 hover:text-white">Shipping Policy</a></li>
                    <li><a href="returns.php" class="text-gray-400 hover:text-white">Returns & Exchanges</a></li>
                    <li><a href="faq.php" class="text-gray-400 hover:text-white">FAQs</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-lg font-semibold mb-4">Connect With Us</h4>
                <div class="flex space-x-4 mb-4">
                    <a href="#" class="text-gray-400 hover:text-white">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white">
                        <i class="fab fa-pinterest"></i>
                    </a>
                </div>
                <p class="text-gray-400">Subscribe to our newsletter</p>
                <form class="mt-2 flex">
                    <input type="email" placeholder="Your email" class="px-4 py-2 w-full rounded-l focus:outline-none text-gray-900">
                    <button type="submit" class="bg-green-600 px-4 py-2 rounded-r hover:bg-green-700 transition duration-300">
                        Subscribe
                    </button>
                </form>
            </div>
        </div>
        <div class="border-t border-gray-800 mt-10 pt-6 flex flex-col md:flex-row justify-between items-center">
            <p class="text-gray-400">&copy; 2023 ClaraCrest. All rights reserved.</p>
            <div class="flex space-x-4 mt-4 md:mt-0">
                <a href="privacy.php" class="text-gray-400 hover:text-white">Privacy Policy</a>
                <a href="terms.php" class="text-gray-400 hover:text-white">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<script>
    // Copy your addToCart and wishlist functions from home.php
    function addToCart(id, name, price, image) {
        <?php if(isset($_SESSION['user_id'])): ?>
            // Send AJAX request to add item to cart in database
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + id + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Item added to bag successfully!');
                } else {
                    alert('Error adding item to bag: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding item to bag. Please try again.');
            });
        <?php else: ?>
            // Redirect to login if user is not logged in
            window.location.href = 'login.php?redirect=search_results.php?query=<?php echo urlencode($search_query); ?>';
        <?php endif; ?>
    }

    // Wishlist functionality
    document.querySelectorAll('.wishlist-icon').forEach(icon => {
        icon.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.getAttribute('data-product-id');
            const heartIcon = this.querySelector('i');
            
            <?php if(isset($_SESSION['user_id'])): ?>
                // Toggle heart appearance immediately for better UX
                const isInWishlist = heartIcon.classList.contains('fas');
                
                if(isInWishlist) {
                    heartIcon.classList.replace('fas', 'far');
                    heartIcon.style.color = '';
                } else {
                    heartIcon.classList.replace('far', 'fas');
                    heartIcon.style.color = '#006039';
                }
                
                // Send AJAX request to update wishlist
                fetch('update_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId + '&action=' + (isInWishlist ? 'remove' : 'add')
                })
                .then(response => response.json())
                .then(data => {
                    if(!data.success) {
                        // Revert the heart if there was an error
                        if(isInWishlist) {
                            heartIcon.classList.replace('far', 'fas');
                            heartIcon.style.color = '#006039';
                        } else {
                            heartIcon.classList.replace('fas', 'far');
                            heartIcon.style.color = '';
                        }
                        alert('Error updating wishlist: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert the heart on error
                    if(isInWishlist) {
                        heartIcon.classList.replace('far', 'fas');
                        heartIcon.style.color = '#006039';
                    } else {
                        heartIcon.classList.replace('fas', 'far');
                        heartIcon.style.color = '';
                    }
                    alert('Error updating wishlist. Please try again.');
                });
            <?php else: ?>
                // Redirect to login if user is not logged in
                window.location.href = 'login.php?redirect=search_results.php?query=<?php echo urlencode($search_query); ?>';
            <?php endif; ?>
        });
    });

    // Initialize wishlist hearts on page load
    document.addEventListener('DOMContentLoaded', function() {
        <?php if(isset($_SESSION['user_id'])): ?>
            document.querySelectorAll('.wishlist-icon').forEach(icon => {
                const productId = icon.getAttribute('data-product-id');
                const heartIcon = icon.querySelector('i');
                
                // Check if this product is in the wishlist via AJAX
                fetch('check_wishlist.php?product_id=' + productId, {
                    method: 'GET'
                })
                .then(response => response.json())
                .then(data => {
                    if(data.in_wishlist) {
                        heartIcon.classList.replace('far', 'fas');
                        heartIcon.style.color = '#006039';
                    }
                })
                .catch(error => {
                    console.error('Error checking wishlist status:', error);
                });
            });
        <?php endif; ?>
    });
</script>
</body>
</html> 