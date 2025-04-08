<?php
session_start();
// Debugging: Check if the session variable is set
if (isset($_SESSION['fullname'])) {
    // {{ edit_1 }} // Removed the echo statement for logged-in user
} else {
    // echo "User is not logged in."; // Commented out to prevent display
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClaraCrest - Luxury Watch Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .auth-button {
            transition: all 0.3s ease;
            padding: 0.5rem 3rem;
        }

        .auth-button:hover {
            transform: translateY(-2px);
        }

        .cart-button {
            transition: all 0.3s ease;
            padding: 7px 50px;
            margin-left: 0.5rem;
        }

        .cart-button:hover {
            transform: translateY(-2px);
        }

        .watch-card {
            transition: transform 0.3s ease;
        }

        .watch-card:hover {
            transform: translateY(-10px);
        }

        .brand-logo {
            transition: opacity 0.3s ease;
        }

        .brand-logo:hover {
            opacity: 1;
        }

        .hero-section {
            position: relative;
            overflow: hidden;
        }
        .video-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 0;
        }

        .rental-card {
            transition: all 0.3s ease;
        }

        .rental-card:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .scroll-snap-container {
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
        }

        .scroll-snap-item {
            scroll-snap-align: start;
        }

        .gallery-item {
        height: 100vh;
        position: relative;
        background-size: cover;
        background-position: center;
    }

    .gallery-text {
        position: absolute;
        bottom: 0;
        left: 0;
        padding: 2rem;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s ease;
        background: linear-gradient(to top, rgba(0,0,0,0.7), rgba(0,0,0,0));
        width: 100%;
    }

    .gallery-item:hover .gallery-text {
        opacity: 1;
        transform: translateY(0);
    }

    .dropdown {
        display: none;
        position: absolute;
        background-color: #2d2d2d;
        border: 1px solid #ccc;
        z-index: 1000;
        width: 150px;
        border-radius: 0.5rem;
    }
    .dropdown-item {
        padding: 10px;
        cursor: pointer;
        color: white;
    }
    .dropdown-item:hover {
        background-color: #444;
    }
    .profile-container:hover .dropdown {
        display: block;
    }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-green-600 text-white shadow-lg fixed w-full z-50">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-8">
                    <a href="home.php" class="text-2xl font-bold">ClaraCrest</a>
                    <!-- Search Bar -->
                    <div class="relative">
                        <form action="search_results.php" method="GET">
                            <input 
                                type="text" 
                                name="query" 
                                placeholder="Search watches..." 
                                class="w-64 px-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:border-gray-500"
                                required
                            >
                            <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="flex items-center space-x-8">
                    <!-- Navigation Links -->
                    <a href="#brands" class="text-gray-300 hover:text-gray-100">Brands</a>
                    <a href="collection.php" class="text-gray-300 hover:text-gray-100">Collection</a>
                    <a href="insurance.php" class="text-gray-300 hover:text-gray-100">Insurance</a>
                    <!-- Auth Buttons -->
                    <div class="flex items-center space-x-6">
                        <a href="login.php" class="text-gray-300 hover:text-gray-100">Login</a>
                        <a href="signup.php" class="bg-black text-white rounded-full hover:bg-gray-700 transition duration-300 auth-button whitespace-nowrap">Sign Up</a>
                        <a href="bag.php" class="bg-black text-white rounded-full hover:bg-gray-700 transition duration-300 cart-button whitespace-nowrap">
                            <i class="fas fa-shopping-bag"></i>
                        </a>
                        <a href="favorites.php" style="color: white; text-decoration: none; display: flex; align-items: center; font-size: 18px;">
            <i class="far fa-heart"></i>
            
        </a>
                        
                        <!-- User Profile Indicator -->
                        <?php if (isset($_SESSION['fullname'])): ?>
                            <div class="relative group profile-container">
                                <div class="w-10 h-10 bg-gray-700 text-white rounded-full flex items-center justify-center font-bold">
                                    <?php echo strtoupper(substr($_SESSION['fullname'], 0, 1)); ?>
                                </div>
                                <!-- Dropdown Menu -->
                                <div class="dropdown">
                                    <div class="dropdown-item" onclick="window.location.href='userprofile.php'">Profile</div>
                                    <div class="dropdown-item" onclick="window.location.href='orders.php'">Orders</div>
                                    <div class="dropdown-item" onclick="window.location.href='logout.php'">Logout</div>
                                    
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

   <!-- Hero Section -->
    <section class="hero-section h-screen flex items-center text-white">
        <video autoplay loop muted playsinline preload="auto" class="video-background" id="myVideo">
            <source src="image/v.mp4" type="video/mp4" />
            Your browser does not support the video tag.
        </video>
        <div class="container mx-auto px-6 text-center relative z-10">
            <h1 class="text-6xl font-bold mb-6">Luxury on Your Wrist</h1>
            <p class="text-xl mb-8">Purchase Premium Timepieces from World-Renowned Brands</p>
            <div class="space-x-4">
                <a href="collection.php" class="bg-black text-white px-8 py-3 rounded-full hover:bg-gray-800 transition duration-300">Shop Now</a>
                
        </div>
    </section>
   
    <!-- Purchase Section -->
    <section id="purchase" class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <h2 class="text-4xl font-bold text-center mb-16">Available for Purchase</h2>
            <div class="grid grid-cols-4 gap-4">
                <?php
                // Use the Database class from dbconnect.php
                require_once 'dbconnect.php';
                
                try {
                    $db = Database::getInstance();
                    $conn = $db->getConnection();
                    
                    // Fetch 4 products from the products table
                    $sql = "SELECT * FROM products LIMIT 4";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            ?>
                            <div class="watch-card bg-white p-4 rounded-lg shadow-lg">
                                <div class="wishlist-icon" data-product-id="<?php echo $row['id']; ?>">
                                    <i class="far fa-heart"></i>
                                </div>
                                <a href="product-details.php?id=<?php echo $row['id']; ?>" class="block">
                                    <img src="<?php echo $row['image_url'] ?? $row['image'] ?? 'image/default-watch.jpg'; ?>" 
                                        alt="<?php echo $row['name']; ?>" 
                                        class="w-full h-48 object-cover rounded-lg mb-3">
                                    <h3 class="text-lg font-bold mb-1"><?php echo $row['name']; ?></h3>
                                    <p class="text-gray-600 mb-3 text-sm"><?php echo substr($row['description'], 0, 70) . (strlen($row['description']) > 70 ? '...' : ''); ?></p>
                                </a>
                                <div class="flex justify-between items-center">
                                    <span class="text-xl font-bold">₹<?php echo number_format($row['price']); ?></span>
                                    <button onclick="addToCart(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>', <?php echo $row['price']; ?>, '<?php echo addslashes($row['image_url'] ?? $row['image'] ?? 'image/default-watch.jpg'); ?>')" 
                                        class="bg-black text-white px-4 py-1 rounded-full text-sm">
                                       Add To Bag
                                    </button>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<p class='text-center col-span-4'>No products found. Please add some products to the database.</p>";
                    }
                } catch (Exception $e) {
                    echo "<p class='text-center col-span-4'>Error: " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Luxury Watch Description Section -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-5xl font-bold text-black-700 mb-8">Explore our Luxury Collection</h2>
                <p class="text-xl text-gray-700 leading-relaxed">
                    Our curated collection offers the finest selection of prestigious timepieces from world-renowned manufacturers, featuring exceptional craftsmanship and timeless design. From classic elegance to modern innovation, each piece represents the pinnacle of horological excellence, ready to grace any discerning wrist.
                </p>
                <a href="collection.php" class="text-green-600 hover:text-green-800 underline">Find your Watch</a>
            </div>
            <div>
                <img src="image/watt1.jpeg" alt="Luxury Watches" class="w-full rounded-lg shadow-xl">
            </div>
        </div>
    </div>
</section>

    


    <body class="bg-gray-50">
        <!-- Navigation remains the same -->
        
        <!-- Hero Section remains the same -->
        
        <!-- Featured Brands remains the same -->
        
        <!-- Purchase Section remains the same -->
        
        <!-- Rental Section remains the same -->
    
        <!-- New Fullscreen Gallery Section -->
        <section class="gallery-section">
            <div class="gallery-item" style="background-image: url('image/wat5.jpeg')">
                <div class="gallery-text text-white">
                    <h3 class="text-3xl font-bold mb-2">Rolex Daytona Cosmograph</h3>
                    <p class="text-xl">The ultimate chronograph for racing enthusiasts</p>
                </div>
            </div>
            <div class="gallery-item" style="background-image: url('image/wat6.jpg')">
                <div class="gallery-text text-white">
                    <h3 class="text-3xl font-bold mb-2">Patek Philippe Grand Complications</h3>
                    <p class="text-xl">A masterpiece of horological excellence</p>
                </div>
            </div>
            <div class="gallery-item" style="background-image: url('image/wat7.jpg')">
                <div class="gallery-text text-white">
                    <h3 class="text-3xl font-bold mb-2">Audemars Piguet Royal Oak</h3>
                    <p class="text-xl">The iconic luxury sports watch</p>
                </div>
            </div>
         <div class="gallery-item" style="background-image: url('image/wat8.jpg')">
                <div class="gallery-text text-white">
                    <h3 class="text-3xl font-bold mb-2">Omega Seamaster Diver</h3>
                    <p class="text-xl">Professional diving excellence since 1948</p>
                </div>
            </div>
               <!--<div class="gallery-item" style="background-image: url('/api/placeholder/1920/1080')">
                <div class="gallery-text text-white">
                    <h3 class="text-3xl font-bold mb-2">Cartier Tank Louis</h3>
                    <p class="text-xl">A century of timeless elegance</p>
                </div>
            </div>
            <div class="gallery-item" style="background-image: url('/api/placeholder/1920/1080')">
                <div class="gallery-text text-white">
                    <h3 class="text-3xl font-bold mb-2">Vacheron Constantin Patrimony</h3>
                    <p class="text-xl">The essence of watchmaking tradition</p>
                </div>
            </div>
            <div class="gallery-item" style="background-image: url('/api/placeholder/1920/1080')">
                <div class="gallery-text text-white">
                    <h3 class="text-3xl font-bold mb-2">Jaeger-LeCoultre Reverso</h3>
                    <p class="text-xl">Art Deco innovation meets modern luxury</p>
                </div>
            </div>
            <div class="gallery-item" style="background-image: url('/api/placeholder/1920/1080')">
                <div class="gallery-text text-white">
                    <h3 class="text-3xl font-bold mb-2">Blancpain Fifty Fathoms</h3>
                    <p class="text-xl">The original modern diving watch</p>
                </div>
            </div>-->
        </section>

         <!-- Featured Brands -->
     <section id="brands" class="py-20">
        <div class="container mx-auto px-6">
            <h2 class="text-4xl font-bold text-center mb-16">Featured Brands</h2>
            <div class="grid grid-cols-4 gap-8">
                <img src="image/rolex-logo.png" alt="Rolex" class="brand-logo opacity-50">
                <img src="image/omegalogo.png" alt="Omega" class="brand-logo opacity-50">
                <img src="image/pateklogo.png" alt="Patek Philippe" class="brand-logo opacity-50">
                <img src="image/cartierlogo.png" alt="Cartier" class="brand-logo opacity-50">
            </div>
        </div>
    </section>
    
        <!-- Why Choose Us Section -->
        <section class="py-20 bg-white">
            <!-- [Why Choose Us section remains exactly the same] -->
        </section>
    
        <!-- [Rest of the code including footer remains exactly the same] -->
    
    </body>

    <!-- Why Choose Us -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <h2 class="text-4xl font-bold text-center mb-16">Why Choose ChronoLuxe</h2>
            <div class="grid grid-cols-4 gap-8 text-center">
                <div>
                    <img src="/api/placeholder/64/64" alt="Authentic" class="mx-auto mb-4">
                    <h3 class="text-xl font-bold mb-2">100% Authentic</h3>
                    <p class="text-gray-600">All watches certified authentic</p>
                </div>
                <div>
                    <img src="/api/placeholder/64/64" alt="Insurance" class="mx-auto mb-4">
                    <h3 class="text-xl font-bold mb-2">Fully Insured</h3>
                    <p class="text-gray-600">Comprehensive rental insurance</p>
                </div>
                <div>
                    <img src="/api/placeholder/64/64" alt="Service" class="mx-auto mb-4">
                    <h3 class="text-xl font-bold mb-2">Expert Service</h3>
                    <p class="text-gray-600">In-house watch specialists</p>
                </div>
                <div>
                    <img src="/api/placeholder/64/64" alt="Delivery" class="mx-auto mb-4">
                    <h3 class="text-xl font-bold mb-2">Secure Delivery</h3>
                    <p class="text-gray-600">Insured worldwide shipping</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black text-white py-16">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">ChronoLuxe</h3>
                    <p class="text-gray-400">Your destination for luxury timepieces</p>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>Purchase</li>
                        <li>Rental</li>
                        <li>About Us</li>
                        <li>Contact</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Contact</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>123 Luxury Lane</li>
                        <li>New York, NY 10001</li>
                        <li>contact@chronoluxe.com</li>
                        <li>(555) 123-4567</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Newsletter</h3>
                    <p class="text-gray-400 mb-4">Subscribe for updates on new arrivals and special offers</p>
                    <input type="email" placeholder="Enter your email" class="w-full px-4 py-2 rounded-full text-black mb-2">
                    <button class="w-full bg-white text-black px-4 py-2 rounded-full">Subscribe</button>
                </div>
            </div>
        </div>
    </footer>

    <script>
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
                window.location.href = 'login.php?redirect=home.php';
            <?php endif; ?>
        }

        // Wishlist functionality
        document.querySelectorAll('.wishlist-icon').forEach(icon => {
            icon.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent any default action
                
                const productId = this.getAttribute('data-product-id');
                const heartIcon = this.querySelector('i');
                
                // Check if user is logged in
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
                        if(data.success) {
                            console.log(data.message); // Log success message
                        } else {
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
                    window.location.href = 'login.php?redirect=home.php';
                <?php endif; ?>
            });
        });
        
        // Initialize wishlist hearts on page load
        document.addEventListener('DOMContentLoaded', function() {
            // If user is logged in, check which products are in their wishlist
            <?php if(isset($_SESSION['user_id'])): ?>
                console.log("Initializing wishlist hearts");
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