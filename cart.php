<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ClaraCrest</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-8">
                    <a href="home.php" class="text-2xl font-bold text-gray-800">ClaraCrest</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Cart Section -->
    <div class="container mx-auto px-6 pt-32 pb-20">
        <h1 class="text-3xl font-bold mb-8">Shopping Cart</h1>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2">
                <div id="cartItems" class="space-y-4">
                    <!-- Cart items will be dynamically inserted here -->
                </div>
            </div>

            <!-- Cart Summary -->
            <div class="bg-white p-6 rounded-lg shadow-md h-fit">
                <h2 class="text-xl font-bold mb-4">Order Summary</h2>
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span id="cartSubtotal">$0</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Shipping</span>
                        <span>Free</span>
                    </div>
                    <div class="border-t pt-2 mt-2">
                        <div class="flex justify-between font-bold">
                            <span>Total</span>
                            <span id="cartTotal">$0</span>
                        </div>
                    </div>
                </div>
                <button onclick="checkout()" class="w-full bg-black text-white py-3 rounded-full hover:bg-gray-800">
                    Proceed to Checkout
                </button>
            </div>
        </div>
    </div>

    <script>
        function displayCart() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const cartItemsContainer = document.getElementById('cartItems');
            
            // Clear existing items
            cartItemsContainer.innerHTML = '';
            
            if (cart.length === 0) {
                cartItemsContainer.innerHTML = `
                    <div class="bg-white p-6 rounded-lg shadow-md text-center">
                        <p class="text-gray-500 mb-4">Your cart is empty</p>
                        <a href="home.php" class="text-black hover:underline">Continue Shopping</a>
                    </div>
                `;
            } else {
                let total = 0;
                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    
                    cartItemsContainer.innerHTML += `
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <div class="flex items-center space-x-4">
                                <img src="${item.image}" alt="${item.name}" class="w-24 h-24 object-cover rounded">
                                <div class="flex-1">
                                    <h3 class="font-bold text-lg">${item.name}</h3>
                                    <p class="text-gray-600">$${item.price.toLocaleString()}</p>
                                    <div class="flex items-center space-x-2 mt-2">
                                        <button onclick="updateQuantity(${item.id}, -1)" class="px-2 py-1 bg-gray-200 rounded">-</button>
                                        <span class="w-8 text-center">${item.quantity}</span>
                                        <button onclick="updateQuantity(${item.id}, 1)" class="px-2 py-1 bg-gray-200 rounded">+</button>
                                        <button onclick="removeItem(${item.id})" class="ml-4 text-red-500">Remove</button>
                                    </div>
                                </div>
                                <div class="font-bold text-lg">
                                    $${itemTotal.toLocaleString()}
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                document.getElementById('cartSubtotal').textContent = `$${total.toLocaleString()}`;
                document.getElementById('cartTotal').textContent = `$${total.toLocaleString()}`;
            }
            
            updateCartCount();
        }

        function updateQuantity(id, change) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            const itemIndex = cart.findIndex(item => item.id === id);
            
            if (itemIndex !== -1) {
                cart[itemIndex].quantity += change;
                
                if (cart[itemIndex].quantity <= 0) {
                    cart.splice(itemIndex, 1);
                }
                
                localStorage.setItem('cart', JSON.stringify(cart));
                displayCart();
            }
        }

        function removeItem(id) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            cart = cart.filter(item => item.id !== id);
            localStorage.setItem('cart', JSON.stringify(cart));
            displayCart();
        }

        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.querySelector('.cart-button').textContent = `Cart (${totalItems})`;
        }

        function checkout() {
            alert('Proceeding to checkout...');
            // Add your checkout logic here
        }

        // Initialize cart display on page load
        document.addEventListener('DOMContentLoaded', displayCart);
    </script>
</body>
</html> 