<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniHelper - From Application to Graduation</title>
    <link rel="stylesheet" href="views/css/style.css">
    <link rel="stylesheet" href="views/css/dashboard.css">
</head>
<body>
    <!-- Part 1: Navigation Bar (Completely Separate) -->
    <nav class="nav">
        <div class="nav-container">
            <div class="nav-left">
                <div class="logo">UniHelper</div>
                <div class="nav-links">
                    <!--a href="#home" class="nav-link">Home</a-->
                </div>
            </div>
            <div class="nav-right">
                <div class="profile-container">
                    <div class="profile-picture">U</div>
                    <div class="profile-info">
                        <span class="profile-name">User</span>
                        <span class="profile-role">Role</span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Part 2: Sidebar (Completely Separate) -->
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <!-- Platform Moderator Features -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">Moderator</div>
                <li><a href="#content-moderation" class="sidebar-link">
                    <i class="fas fa-shield-alt"></i>
                    <span>Content Moderation</span>
                </a></li>
                <li><a href="#flagged-posts" class="sidebar-link">
                    <i class="fas fa-flag"></i>
                    <span>Flagged Posts</span>
                </a></li>
                <li><a href="#user-management" class="sidebar-link">
                    <i class="fas fa-user-cog"></i>
                    <span>User Management</span>
                </a></li>
                <li><a href="#activity-monitor" class="sidebar-link">
                    <i class="fas fa-eye"></i>
                    <span>Activity Monitor</span>
                </a></li>
                <li><a href="#expired-content" class="sidebar-link">
                    <i class="fas fa-clock"></i>
                    <span>Expired Content</span>
                </a></li>
                <li><a href="#moderator-dashboard" class="sidebar-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Moderator Dashboard</span>
                </a></li>
            </div>
        </ul>
    </aside>


    <!-- Part 4: Footer (At Very Bottom, Completely Separate) -->
    <footer class="dashboard-footer">
        <div class="footer-container">
            <div class="footer-content">
                <!-- Company Info -->
                <div class="footer-section">
                    <div class="footer-logo">UniHelper</div>
                    <p class="footer-description">Empowering Sri Lankan students from application to graduation with smart tools and community support.</p>
                    <div class="social-links">
                        <a href="#" class="social-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                            </svg>
                        </a>
                        <a href="#" class="social-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M23 3a10.9 10.9 0 0 1-3.14 1.68 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>
                            </svg>
                        </a>
                        <a href="#" class="social-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/>
                                <rect x="2" y="9" width="4" height="12"/>
                                <circle cx="4" cy="4" r="2"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="footer-section">
                    <h3 class="footer-title">Contact Info</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <span>unihelper@gmail.com</span>
                        </div>
                        <div class="contact-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                            <span>+94 11 234 5678</span>
                        </div>
                        <div class="contact-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span>Colombo, Sri Lanka</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p>&copy; 2024 UniHelper. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="js/script.js"></script>
    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        });

        // Sidebar link active state
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', function(e) {
                // Remove active class from all links
                document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
                // Add active class to clicked link
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>