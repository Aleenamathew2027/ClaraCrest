    </div> <!-- Close the main content div -->
    <footer class="bg-gray-800 text-white py-6 mt-10">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between">
                <div class="mb-6 md:mb-0">
                    <h3 class="text-xl font-bold mb-4">ClaraCrest</h3>
                    <p class="text-gray-400">Luxury watches for the discerning collector.</p>
                </div>
                <div class="mb-6 md:mb-0">
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-gray-400 hover:text-white">Home</a></li>
                        <li><a href="collection.php" class="text-gray-400 hover:text-white">Collection</a></li>
                        <li><a href="about.php" class="text-gray-400 hover:text-white">About Us</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-white">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Connect With Us</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-6 text-center">
                <p>&copy; <?php echo date('Y'); ?> ClaraCrest. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Update cart count from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const cartItems = JSON.parse(localStorage.getItem('cartItems')) || [];
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = cartItems.length;
            }
        });
    </script>
</body>
</html> 