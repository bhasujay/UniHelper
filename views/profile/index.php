<!DOCTYPE html>
<html lang="en">
<head>
    <base href="/unihelper/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniHelper - User Profile</title>
    
    <link rel="stylesheet" href="/unihelper/views/css/style.css">
    <link rel="stylesheet" href="/unihelper/views/css/dashboard.css">
    <link rel="stylesheet" href="/unihelper/views/css/profile.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="nav">
        <div class="nav-container">
            <div class="nav-left">
                <a href="home" class="logo">UniHelper</a>
                <div class="nav-links">
                    <!-- Navigation links can go here -->
                </div>
            </div>
            <div class="nav-right">
                <div class="profile-container">
                    <div class="profile-picture"><?= substr($user->firstName, 0, 1) ?></div>
                    <div class="profile-info">
                        <span class="profile-name"><?= htmlspecialchars($user->firstName) ?></span>
                        <span class="profile-role"><?= htmlspecialchars($user->role) ?></span>
                    </div>
                </div>
                <a href="/UniHelper/logout" class="logout-btn">
                    <button class="btn btn-outline">Logout</button>
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar based on user role -->
    <aside class="sidebar">
        <!-- Sidebar content would be included based on role -->
        <?php 
        // Include appropriate sidebar based on user role
        $role = $user->role;
        if (strpos($role, 'applicant') !== false) {
            require_once 'views/partials/sidebar_applicant.php';
        } elseif (strpos($role, 'undergrad') !== false) {
            require_once 'views/partials/sidebar_undergrad.php';
        } elseif (strpos($role, 'profile') !== false) {
            require_once 'views/partials/sidebar_profile.php';
        }
        ?>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="profile-header">
            <h1 class="profile-title">My Profile</h1>
            <a href="profile/edit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Edit Profile
            </a>
        </div>

        <div class="profile-content">
            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <div class="profile-image-container">
                        <?php if($user->profilePicture): ?>
                            <img src="<?= htmlspecialchars($user->profilePicture) ?>" alt="Profile Picture" class="profile-image">
                        <?php else: ?>
                            <div class="profile-image-placeholder">
                                <?= strtoupper(substr($user->firstName, 0, 1) . substr($user->lastName, 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info-header">
                        <h2 class="profile-name"><?= htmlspecialchars($user->firstName . ' ' . $user->lastName) ?></h2>
                        <p class="profile-role-badge"><?= htmlspecialchars(str_replace('role-', '', $user->role)) ?></p>
                    </div>
                </div>
                
                <div class="profile-card-body">
                    <!-- Personal Information -->
                    <div class="info-section">
                        <h3 class="section-title">Personal Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">First Name</span>
                                <span class="info-value"><?= htmlspecialchars($user->firstName) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Last Name</span>
                                <span class="info-value"><?= htmlspecialchars($user->lastName) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?= htmlspecialchars($user->email) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?= htmlspecialchars($user->phone) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Joined</span>
                                <span class="info-value"><?= date('F j, Y', strtotime($user->createdAt)) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Role-specific Information -->
                    <?php if($user->role === 'role-applicant'): ?>
                    <div class="info-section">
                        <h3 class="section-title">Applicant Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">A/L Year</span>
                                <span class="info-value"><?= htmlspecialchars($user->alYear) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php elseif($user->role === 'role-undergrad'): ?>
                    <div class="info-section">
                        <h3 class="section-title">University Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">University</span>
                                <span class="info-value"><?= htmlspecialchars($user->undergradUniversity) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Major</span>
                                <span class="info-value"><?= htmlspecialchars($user->major) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php elseif($user->role === 'role-profile'): ?>
                    <div class="info-section">
                        <h3 class="section-title">University Profile</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">University</span>
                                <span class="info-value"><?= htmlspecialchars($user->profileUniversity) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Role</span>
                                <span class="info-value"><?= htmlspecialchars($user->profileRole) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Account Security -->
                    <div class="info-section">
                        <h3 class="section-title">Account Security</h3>
                        <div class="security-options">
                            <a href="profile/change-password" class="btn btn-outline">Change Password</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional profile content or cards can go here -->
        </div>
    </main>

    <!-- Footer -->
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
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', function() {
                const sidebar = document.querySelector('.sidebar');
                sidebar.classList.toggle('open');
            });
        }
    </script>
</body>
</html>