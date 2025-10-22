<!DOCTYPE html>
<html lang="en">
<head>
    <base href="/unihelper/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniHelper - From Application to Graduation</title>

    <link rel="stylesheet" href="/unihelper/views/css/style.css">
    <link rel="stylesheet" href="/unihelper/views/css/dashboard.css">
    <link rel="stylesheet" href="/unihelper/views/css/components/cards.css">
    <link rel="stylesheet" href="/unihelper/views/css/components/und-cards.css">
    <link rel="stylesheet" href="/unihelper/views/css/profile.css">
</head>
<body>
    <nav class="nav">
        <div class="nav-container">
            <div class="nav-left">
                <div class="logo">UniHelper</div>
                <div class="nav-links">
                    <!--a href="#home" class="nav-link">Home</a-->
                </div>
            </div>
            <div class="nav-right">
                <div class="profile-container" id="profileDropdownTrigger">
                    <div class="profile-picture"><?= substr($user->firstName, 0, 1) ?></div>
                    <div class="profile-info">
                        <span class="profile-name"><?= htmlspecialchars($user->firstName) ?></span>
                        <span class="profile-role"><?= htmlspecialchars(substr($user->role, 5)) ?></span>
                    </div>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="dropdown-header">
                            <div class="dropdown-user-info">
                                <span class="dropdown-name"><?= htmlspecialchars($user->firstName . ' ' . $user->lastName) ?></span>
                                <span class="dropdown-email"><?= htmlspecialchars($user->email) ?></span>
                            </div>
                        </div>
                        <div class="dropdown-body">
                            <a href="profile" class="dropdown-item">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                My Profile
                            </a>
                            <a href="profile/edit" class="dropdown-item">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Edit Profile
                            </a>
                            <a href="profile/change-password" class="dropdown-item">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                Change Password
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/UniHelper/logout" class="dropdown-item">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <!-- University Admin Features -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">System Admin</div>
                <li><a href="dashboard/admin/content-review-queue" class="sidebar-link <?= $activeComponent === 'content-review-queue' ? 'active' : '' ?>">
                    <i class="fas fa-tasks"></i>
                    <span>Content Review Queue</span>
                </a></li>
                <li><a href="dashboard/admin/role-applications" class="sidebar-link <?= $activeComponent === 'role-applications' ? 'active' : '' ?>">
                    <i class="fas fa-user-check"></i>
                    <span>Role Applications</span>
                </a></li>
                <li><a href="dashboard/admin/degree-programs-management" class="sidebar-link <?= $activeComponent === 'degree-programs-management' ? 'active' : '' ?>">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Degree Programs</span>
                </a></li>
            </div>
        </ul>
    </aside>

    <main class="main-content">
        <?php if (isset($content)) echo $content; ?>
    </main>

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

    <script>
        // Profile dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const profileTrigger = document.getElementById('profileDropdownTrigger');
            const profileDropdown = document.getElementById('profileDropdown');
            
            if (profileTrigger && profileDropdown) {
                // Toggle dropdown visibility when clicking the trigger
                profileTrigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (profileDropdown.classList.contains('show') && 
                        !profileTrigger.contains(e.target) && 
                        !profileDropdown.contains(e.target)) {
                        profileDropdown.classList.remove('show');
                    }
                });
                
                // Prevent dropdown from closing when clicking inside it
                profileDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
                
                // Close dropdown when pressing Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && profileDropdown.classList.contains('show')) {
                        profileDropdown.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>
</html>