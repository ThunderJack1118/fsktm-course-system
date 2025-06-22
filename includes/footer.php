    </main>
    <footer class="main-footer">
        <div class="container">
            <div class="footer-section">
                <h3>About FSKTM</h3>
                <p>The Faculty of Computer Science and Information Technology provides world-class education in computing and IT fields.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>/pages/courses.php">All Courses</a></li>
                    <li><a href="#">Academic Calendar</a></li>
                    <li><a href="#">Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <p>Email: fsktm@university.edu</p>
                <p>Phone: +603-1234 5678</p>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> FSKTM Course Management System. All rights reserved.</p>
        </div>
    </footer>
    <?php if (isAdmin()): ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/admin.js"></script>
    <?php endif; ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>