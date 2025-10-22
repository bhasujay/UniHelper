<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UniHelper</title>
    <link rel="stylesheet" href="views/css/style.css">
    <link rel="stylesheet" href="views/css/sign.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="nav">
        <div class="nav-container">
            <div class="nav-left">
                <a href="home" class="logo">UniHelper</a>
                <div class="nav-links"></div>
            </div>
            <div class="nav-right">
                <a href="login"><button class="btn btn-outline text1">Login</button></a>
                <a href="register"><button class="btn btn-primary">Register</button></a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="auth-main">
        <div class="container">
            <div class="auth-container" style="justify-content: center;">
                <div class="auth-card">
                    <!-- auth card header -->
                    <div class="auth-card-header">
                        <div class="auth-icon-container">
                            <i class="auth-icon"></i>
                            <div class="auth-icon-glow"></div>
                        </div>
                        <h1 class="auth-title">Create Your Account</h1>
                        <p class="auth-subtitle">Join UniHelper and start your academic journey</p>
                    </div>

                    <!-- Registration Form -->
                    <form class="auth-form" id="registerForm" method="post" action="register" enctype="multipart/form-data">
                        <!-- Section 1 -->
                        <div class="form-section" id="section1">
                            <div class="form-group">
                                <label for="firstName" class="form-label required">First Name</label>
                                <input type="text" id="firstName" name="firstName" class="form-input" placeholder="Enter your first name" required>
                            </div>
                            <div class="form-group">
                                <label for="lastName" class="form-label required">Last Name</label>
                                <input type="text" id="lastName" name="lastName" class="form-input" placeholder="Enter your last name" required>
                            </div>
                            <div class="form-group">
                                <label for="email" class="form-label required">Email Address</label>
                                <div class="form-input-with-icon">
                                    <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email address" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="phone" class="form-label required">Contact Number</label>
                                <div class="form-input-with-icon">
                                    <input type="tel" id="phone" name="phone" class="form-input" placeholder="Enter your phone number" required>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2 -->
                        <div class="form-section" id="section2" style="display:none;">

                            <!-- Role Switch -->
                            <div class="role-switch-container">
                                <div class="role-switch" id="roleSwitch">

                                    <div class="role-switch-slider"></div>

                                    <input type="radio" id="role-applicant" name="userRole" value="role-applicant" checked hidden>
                                    <input type="radio" id="role-undergrad" name="userRole" value="role-undergrad" hidden>
                                    <input type="radio" id="role-profile" name="userRole" value="role-profile" hidden>
                                    <div class="label-container">
                                        <label for="role-applicant" class="role-switch-label">
                                            Applicant
                                        </label>
                                        <label for="role-undergrad" class="role-switch-label">
                                            Undergrad
                                        </label>
                                        <label for="role-profile" class="role-switch-label">
                                            Profile
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Specialized fields for Applicant -->
                            <div class="form-group role-specific role-applicant">
                                <label for="alYear" class="form-label required">GCE A/L Year</label>
                                <div class="form-select-group">
                                    <select id="alYear" name="alYear" class="form-select" required>
                                        <option value="" disabled selected>Select A/L Year</option>
                                        <option value="2024">2024</option>
                                        <option value="2023">2023</option>
                                        <option value="2022">2022</option>
                                        <option value="2021">2021</option>
                                        <option value="2020">2020</option>
                                    </select>
                                    <span class="form-select-arrow"></span>
                                </div>
                            </div>

                            <!-- Specialized fields for Undergraduate -->
                            <div class="form-group role-specific role-undergrad" style="display:none;">
                                <label for="undergradUniversity" class="form-label required">University</label>
                                <div class="form-select-group">
                                    <select id="undergradUniversity" name="undergradUniversity" class="form-select" required>
                                        <option value="" disabled selected>Select University</option>
                                        <?php foreach ($universities as $university): ?>
                                            <option value="<?= htmlspecialchars($university->id) ?>">
                                                <?= htmlspecialchars($university->name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="form-select-arrow"></span>
                                </div>
                            </div>
                            <div class="form-group role-specific role-undergrad" style="display:none;">
                                <label for="major" class="form-label required">Major</label>
                                <div class="form-select-group">
                                    <select id="major" name="major" class="form-select" required>
                                        <option value="" disabled selected>Select Major</option>
                                        <?php foreach ($majors as $major): ?>
                                            <option value="<?= htmlspecialchars($major->id) ?>">
                                                <?= htmlspecialchars($major->name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="form-select-arrow"></span>
                                </div>
                            </div>

                            <!-- Specialized fields for Profile Admin -->
                            <div class="form-group role-specific role-profile" style="display:none;">
                                <label for="profileUniversity" class="form-label required">University</label>
                                <div class="form-select-group">
                                    <select id="profileUniversity" name="profileUniversity" class="form-select" required>
                                        <option value="" disabled selected>Select University</option>
                                        <?php foreach ($universities as $university): ?>
                                            <option value="<?= htmlspecialchars($university->id) ?>">
                                                <?= htmlspecialchars($university->name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="form-select-arrow"></span>
                                </div>
                            </div>
                            <div class="form-group role-specific role-profile" style="display:none;">
                                <label for="role" class="form-label required">Role</label>
                                <div class="form-input-with-icon">
                                    <input type="text" id="role" name="role" class="form-input" placeholder="Enter your role" required>
                                    <i class="form-input-icon"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3 -->
                        <div class="form-section" id="section3" style="display:none;">
                            <div class="form-group">
                                <label for="password" class="form-label required">Password</label>
                                <div class="form-input-with-icon">
                                    <input type="password" id="password" name="password" class="form-input" placeholder="Create a strong password" required>
                                    <i class="form-input-icon"></i>
                                    <button type="button" class="password-toggle" id="passwordToggle">
                                    </button>
                                </div>
                                    <ul class="form-instructions">
                                        <li>At least 8 characters</li>
                                        <li>At least 1 number</li>
                                        <li>At least 1 special character</li>
                                    </ul>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword" class="form-label required">Confirm Password</label>
                                <div class="form-input-with-icon">
                                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-input" placeholder="Confirm your password" required>
                                    <i class="form-input-icon"></i>
                                    <button type="button" class="password-toggle" id="confirmPasswordToggle">
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="profilePicture" class="form-label">Profile Picture</label>
                                <div class="form-input-with-icon">
                                    <input type="file" id="profilePicture" name="profilePicture" accept="image/*" class="file-input">
                                    <svg class="form-input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="20" height="18" rx="2" ry="2"/><circle cx="8.5" cy="10.5" r="2.5"/><polyline points="21 19 17 15 13 19 9 15 3 19"/></svg>
                                </div>
                                <span class="form-instructions">
                                    <i>Choose a profile picture (optional)</i>
                                </span>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="form-navigation" style="display: flex; gap: 1rem;">
                            <button type="button" class="btn btn-outline btn-full btn-lg" id="prevBtn" style="flex: 1 1 0; display:none;">
                                <span class="btn-text-alt">Previous</span>
                            </button>
                            <button type="button" class="btn btn-primary btn-full btn-lg" id="nextBtn" style="flex: 1 1 0;">
                                <span class="btn-text">Next</span>
                            </button>
                        </div>

                        <div class="auth-footer">
                            <p>Already have an account? <a href="login" class="auth-link">Sign in</a></p>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </main>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
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
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p>&copy; 2025 UniHelper. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    <script src="views/js/register.js"></script>

    <!-- Error Modal Script -->
    <?php if (isset($error) && !empty($error)): ?>
    <script>
        // Wait for the page (and register.js) to load
        document.addEventListener('DOMContentLoaded', function() {
            // Get the modal elements created by register.js
            const modalErrorBox = document.getElementById('modalErrorBox');
            const modalErrorMsg = document.getElementById('modalErrorMsg');
            
            if (modalErrorBox && modalErrorMsg) {
                // Set the error message from PHP and show the modal
                modalErrorMsg.innerHTML = '<?php echo htmlspecialchars($error); ?>';
                modalErrorBox.style.display = 'flex';
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>
