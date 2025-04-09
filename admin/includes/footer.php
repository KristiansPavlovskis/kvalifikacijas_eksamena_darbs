    </div> <!-- End of main container -->

    <!-- Admin Footer -->
    <footer class="admin-footer">
        <div class="container">
            <div class="footer-content">
                <!-- Quick Stats -->
                <div class="footer-stats">
                    <h4><i class="fas fa-chart-pie"></i> Quick Stats</h4>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="stat-label">Pending Orders</span>
                            <span class="stat-value" id="pendingOrders">0</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-dollar-sign"></i>
                            <span class="stat-label">Today's Revenue</span>
                            <span class="stat-value" id="todayRevenue">$0</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <span class="stat-label">Active Sellers</span>
                            <span class="stat-value" id="activeSellers">0</span>
                        </div>
                    </div>
                </div>

                <!-- Support & Resources -->
                <div class="footer-support">
                    <h4><i class="fas fa-life-ring"></i> Support & Resources</h4>
                    <div class="support-links">
                        <a href="/admin/docs/marketplace.php">
                            <i class="fas fa-book"></i> Marketplace Guide
                        </a>
                        <a href="/admin/support-tickets.php">
                            <i class="fas fa-ticket-alt"></i> Support Tickets
                        </a>
                        <a href="/admin/system-status.php">
                            <i class="fas fa-server"></i> System Status
                        </a>
                        <a href="/admin/docs/api.php">
                            <i class="fas fa-code"></i> API Documentation
                        </a>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="footer-actions">
                    <h4><i class="fas fa-bolt"></i> Quick Actions</h4>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline-light" onclick="window.location.href='/admin/orders.php?status=pending'">
                            <i class="fas fa-clock"></i> View Pending Orders
                        </button>
                        <button class="btn btn-sm btn-outline-light" onclick="window.location.href='/admin/products.php?filter=low-stock'">
                            <i class="fas fa-exclamation-triangle"></i> Low Stock Items
                        </button>
                        <button class="btn btn-sm btn-outline-light" onclick="window.location.href='/admin/reports/daily.php'">
                            <i class="fas fa-file-alt"></i> Generate Daily Report
                        </button>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="footer-brand">
                    <i class="fas fa-shield-alt"></i> GYMVERSE Admin Panel
                </div>
                <div class="footer-copyright">
                    &copy; <?php echo date("Y"); ?> GYMVERSE. All rights reserved.
                </div>
                <div class="footer-version">
                    <span>Version 1.0.0</span>
                    <a href="/admin/changelog.php" class="version-link">View Changelog</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Core Scripts -->
    <script src="/assets/js/jquery.min.js"></script>
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    
    <!-- Footer Stats Update Script -->
    <script>
    function updateFooterStats() { 
        document.getElementById('pendingOrders').textContent = Math.floor(Math.random() * 50);
        document.getElementById('todayRevenue').textContent = '$' + (Math.floor(Math.random() * 10000)).toLocaleString();
        document.getElementById('activeSellers').textContent = Math.floor(Math.random() * 100);
    }
 
    updateFooterStats();
 
    setInterval(updateFooterStats, 300000);
    </script>
    
    <?php if (isset($additionalScripts)) echo $additionalScripts; ?>
</body>
</html> 