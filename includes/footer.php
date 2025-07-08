        </div>
        <!-- Main Content End -->
    </div>
    <!-- Container End -->

    <!-- Footer -->
    <footer class="bg-light text-center text-muted py-3">
        <div class="container">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - جميع الحقوق محفوظة
            </p>
            <small>الإصدار <?php echo APP_VERSION; ?></small>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (window.bootstrap) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            });
        }, 5000);
        
        // Confirm delete actions
        function confirmDelete(message = 'هل أنت متأكد من الحذف؟') {
            return confirm(message);
        }
        
        // Back to top button
        window.addEventListener('scroll', function() {
            const backToTop = document.getElementById('backToTop');
            if (backToTop) {
                if (window.pageYOffset > 300) {
                    backToTop.style.display = 'block';
                } else {
                    backToTop.style.display = 'none';
                }
            }
        });
        
        // Smooth scroll to top
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
    
    <!-- Back to top button -->
    <button id="backToTop" onclick="scrollToTop()" 
            style="display: none; position: fixed; bottom: 20px; right: 20px; z-index: 99; border: none; outline: none; background-color: #007bff; color: white; cursor: pointer; padding: 15px; border-radius: 50%; font-size: 18px;">
        <i class="fas fa-arrow-up"></i>
    </button>

</body>
</html>