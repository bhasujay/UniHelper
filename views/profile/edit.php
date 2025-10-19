<!DOCTYPE html>
<html lang="en">
<head>
    <base href="/unihelper/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniHelper - Edit Profile</title>
    
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
            <h1 class="profile-title">Edit Profile</h1>
            <a href="profile" class="btn btn-outline">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Profile
            </a>
        </div>

        <div class="profile-content">
            <!-- Edit Profile Form -->
            <div class="profile-card">
                <div class="profile-card-body">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-error">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($success)): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <form action="profile/update" method="POST" enctype="multipart/form-data" class="profile-form">
                        <!-- Profile Picture Upload -->
                        <div class="form-section">
                            <h3 class="section-title">Profile Picture</h3>
                            <div class="profile-upload-container">
                                <div class="profile-image-preview">
                                    <?php if($user->profilePicture): ?>
                                        <img src="<?= htmlspecialchars($user->profilePicture) ?>" alt="Profile Picture" id="profileImagePreview">
                                    <?php else: ?>
                                        <div class="profile-image-placeholder" id="profilePlaceholder">
                                            <?= strtoupper(substr($user->firstName, 0, 1) . substr($user->lastName, 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="upload-controls">
                                    <label for="profilePicture" class="btn btn-outline upload-btn">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                            <polyline points="17 8 12 3 7 8"></polyline>
                                            <line x1="12" y1="3" x2="12" y2="15"></line>
                                        </svg>
                                        Upload Photo
                                    </label>
                                    <input type="file" id="profilePicture" name="profilePicture" accept="image/*" class="file-input" onchange="previewImage(this)">
                                    <?php if($user->profilePicture): ?>
                                        <button type="button" class="btn btn-outline btn-danger" onclick="removeProfileImage()">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                            Remove
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div class="form-section">
                            <h3 class="section-title">Personal Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="firstName">First Name</label>
                                    <input type="text" id="firstName" name="firstName" value="<?= htmlspecialchars($user->firstName) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="lastName">Last Name</label>
                                    <input type="text" id="lastName" name="lastName" value="<?= htmlspecialchars($user->lastName) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user->email) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user->phone) ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Role-specific Information -->
                        <?php if($user->role === 'role-applicant'): ?>
                        <div class="form-section">
                            <h3 class="section-title">Applicant Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="alYear">A/L Year</label>
                                    <input type="text" id="alYear" name="alYear" value="<?= htmlspecialchars($user->alYear) ?>">
                                </div>
                            </div>
                        </div>
                        <?php elseif($user->role === 'role-undergrad'): ?>
                        <div class="form-section">
                            <h3 class="section-title">University Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="undergradUniversity">University</label>
                                    <input type="text" id="undergradUniversity" name="undergradUniversity" value="<?= htmlspecialchars($user->undergradUniversity) ?>">
                                </div>
                                <div class="form-group">
                                    <label for="major">Major</label>
                                    <input type="text" id="major" name="major" value="<?= htmlspecialchars($user->major) ?>">
                                </div>
                            </div>
                        </div>
                        <?php elseif($user->role === 'role-profile'): ?>
                        <div class="form-section">
                            <h3 class="section-title">University Profile</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="profileUniversity">University</label>
                                    <input type="text" id="profileUniversity" name="profileUniversity" value="<?= htmlspecialchars($user->profileUniversity) ?>">
                                </div>
                                <div class="form-group">
                                    <label for="profileRole">Role</label>
                                    <input type="text" id="profileRole" name="profileRole" value="<?= htmlspecialchars($user->profileRole) ?>">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="profile" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
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
        // Image preview functionality
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const placeholder = document.getElementById('profilePlaceholder');
                    const preview = document.getElementById('profileImagePreview');
                    
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    
                    if (!preview) {
                        const newPreview = document.createElement('img');
                        newPreview.id = 'profileImagePreview';
                        newPreview.alt = 'Profile Picture';
                        newPreview.src = e.target.result;
                        document.querySelector('.profile-image-preview').appendChild(newPreview);
                    } else {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Remove profile image
        function removeProfileImage() {
            const preview = document.getElementById('profileImagePreview');
            const placeholder = document.getElementById('profilePlaceholder');
            
            if (preview) {
                preview.style.display = 'none';
            }
            
            if (placeholder) {
                placeholder.style.display = 'flex';
            } else {
                const initials = '<?= strtoupper(substr($user->firstName, 0, 1) . substr($user->lastName, 0, 1)) ?>';
                const newPlaceholder = document.createElement('div');
                newPlaceholder.id = 'profilePlaceholder';
                newPlaceholder.className = 'profile-image-placeholder';
                newPlaceholder.textContent = initials;
                document.querySelector('.profile-image-preview').appendChild(newPlaceholder);
            }
            
            // Add a hidden input to indicate image removal
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'removeProfilePicture';
            hiddenInput.value = '1';
            document.querySelector('form').appendChild(hiddenInput);
        }
    </script>
</body>
</html>