    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5>e-Barangay ni Kap</h5>
                    <p>Comprehensive Barangay Management and Service Platform for San Joaquin, Palo, Leyte.</p>
                    <div class="social-links">
                        <a href="<?php echo BARANGAY_FACEBOOK; ?>" target="_blank" data-bs-toggle="tooltip" title="Follow us on Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>Contact Information</h5>
                    <p><i class="fas fa-phone me-2"></i> <?php echo BARANGAY_PHONE; ?></p>
                    <p><i class="fas fa-map-marker-alt me-2"></i> <?php echo BARANGAY_ADDRESS; ?></p>
                    <p><i class="fas fa-envelope me-2"></i> info@ebarangay.com</p>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo APP_URL; ?>/index.html" data-bs-toggle="tooltip" title="Go to Homepage"><i class="fas fa-chevron-right me-2"></i>Home</a></li>
                        <li><a href="<?php echo APP_URL; ?>/about.html" data-bs-toggle="tooltip" title="Learn more about us"><i class="fas fa-chevron-right me-2"></i>About Us</a></li>
                                        <li><a href="<?php echo APP_URL; ?>/pages/services.php" data-bs-toggle="tooltip" title="Explore our services"><i class="fas fa-chevron-right me-2"></i>Services</a></li>
                <li><a href="<?php echo APP_URL; ?>/pages/announcements.php" data-bs-toggle="tooltip" title="Stay updated with our announcements"><i class="fas fa-chevron-right me-2"></i>Announcements</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-12 text-center">
                    <p>&copy; <?php echo date('Y'); ?> e-Barangay ni Kap. All rights reserved. | Barangay San Joaquin, Palo, Leyte</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" id="scrollToTop" data-bs-toggle="tooltip" title="Back to Top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo APP_URL; ?>/assets/js/custom.js"></script>
    
    <!-- Page-specific JS -->
    <?php if (isset($page_js)): ?>
        <?php foreach ($page_js as $js): ?>
            <script src="<?php echo APP_URL; ?>/assets/js/<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        // Scroll to Top functionality
        const scrollToTopBtn = document.getElementById('scrollToTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('show');
            } else {
                scrollToTopBtn.classList.remove('show');
            }
        });
        
        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

            // Tooltips are now initialized automatically via custom.js

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Form validation enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.needs-validation');
            forms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        });
    </script>
</body>
</html> 