<footer class="footer">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4 col-md-6">
                <a class="navbar-brand mb-3 d-inline-block" href="<?= BASE_URL ?>index.php">
                    Art<span>Sphere</span>
                </a>
                <p class="text-secondary pe-lg-5">Your Idea. Your Creator. Your Creation. Connect with local talent and customize every detail of your next creative project.</p>
                <div class="d-flex gap-3 mt-4">
                    <a href="#" class="text-secondary fs-4 hover-pink"><i class="ph ph-instagram-logo"></i></a>
                    <a href="#" class="text-secondary fs-4 hover-pink"><i class="ph ph-twitter-logo"></i></a>
                    <a href="#" class="text-secondary fs-4 hover-pink"><i class="ph ph-pinterest-logo"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6">
                <h5 class="text-white mb-4">Discover</h5>
                <a href="<?= BASE_URL ?>explore.php" class="footer-link">Explore Products</a>
                <a href="<?= BASE_URL ?>creators.php" class="footer-link">Find Creators</a>
                <a href="<?= BASE_URL ?>categories.php" class="footer-link">Categories</a>
                <a href="#" class="footer-link">Hidden Gems</a>
            </div>
            
            <div class="col-lg-2 col-md-6">
                <h5 class="text-white mb-4">For Creators</h5>
                <a href="#" class="footer-link">Join ArtSphere</a>
                <a href="#" class="footer-link">Creator Guidelines</a>
                <a href="#" class="footer-link">Success Stories</a>
                <a href="#" class="footer-link">Creator Help Center</a>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <h5 class="text-white mb-4">Stay Inspired</h5>
                <p class="text-secondary mb-3">Subscribe to our newsletter for featured artists and custom project ideas.</p>
                <form class="d-flex gap-2">
                    <input type="email" class="form-control bg-dark border-secondary text-light shadow-none" placeholder="Email address">
                    <button class="btn btn-primary-custom" type="button">Subscribe</button>
                </form>
            </div>
        </div>
        
        <div class="row mt-5 pt-4 border-top border-secondary">
            <div class="col-md-6 text-center text-md-start">
                <p class="text-secondary mb-0">&copy; <?= date('Y') ?> ArtSphere. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end mt-3 mt-md-0 d-flex justify-content-center justify-content-md-end gap-4">
                <a href="#" class="text-secondary text-decoration-none">Privacy Policy</a>
                <a href="#" class="text-secondary text-decoration-none">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?= BASE_URL ?>assets/js/script.js"></script>
</body>
</html>
