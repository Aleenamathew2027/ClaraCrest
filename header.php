<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClaraCrest</title>
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
<body>
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
                    <a href="home.php#brands" class="text-gray-300 hover:text-gray-100">Brands</a>
                    <a href="collection.php" class="text-gray-300 hover:text-gray-100">Collection</a>
                    <a href="insurance.php" class="text-gray-300 hover:text-gray-100">Insurance</a>
                    <!-- Auth Buttons -->
                    <div class="flex items-center space-x-6">
                        <?php if (!isset($_SESSION['fullname'])): ?>
                            <a href="login.php" class="text-gray-300 hover:text-gray-100">Login</a>
                            <a href="signup.php" class="bg-black text-white rounded-full hover:bg-gray-700 transition duration-300 auth-button whitespace-nowrap">Sign Up</a>
                        <?php endif; ?>
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
    <div class="pt-24"> <!-- Add padding to account for fixed nav --> 