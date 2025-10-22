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
    <link rel="stylesheet" href="/unihelper/views/css/components/app-cards.css">
    <link rel="stylesheet" href="/unihelper/views/css/degree-programs.css">
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
                <div class="profile-container">
                    <div class="profile-picture">U</div>
                    <div class="profile-info">
                        <span class="profile-name">User</span>
                        <span class="profile-role">Role</span>
                    </div>
                </div>
                <a href="/UniHelper/logout" class="logout-btn">
                    <button class="btn btn-outline">Logout</button>
                </a>
            </div>
            <!-- Mobile Menu Toggle Button -->
            <button id="mobileMenuToggle" class="mobile-menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>

    <aside class="sidebar">
        
        <ul class="sidebar-menu">
            <!-- University Applicant Features -->
            <div class="sidebar-section">

                <div class="sidebar-section-title">University Applicant</div>

                <li><a href="dashboard/applicant/qa-forum" class="sidebar-link">
                    <i class="fas fa-question-circle"></i>
                    <span>Q&A Forum</span>
                </a></li>
                <li><a href="dashboard/applicant/z-score-checker" class="sidebar-link">
                    <i class="fas fa-calculator"></i>
                    <span>Z-Score Checker</span>
                </a></li>
                <li><a href="dashboard/applicant/degree-programs" class="sidebar-link">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Degree Programs</span>
                </a></li>
                <li><a href="dashboard/applicant/wishlist" class="sidebar-link">
                    <i class="fas fa-heart"></i>
                    <span>Wishlist</span>
                </a></li>
                <li><a href="dashboard/applicant/find-applicant" class="sidebar-link">
                    <i class="fas fa-users"></i>
                    <span>Find Applicants</span>
                </a></li>
                <li><a href="dashboard/applicant/unicode-generator" class="sidebar-link">
                    <i class="fas fa-list-ol"></i>
                    <span>Unicode Generator</span>
                </a></li>
                <!-- not implemented -->
                <li><a href="dashboard/applicant/connect-undergrads" class="sidebar-link">
                    <i class="fab fa-whatsapp"></i>
                    <span>Connect with Undergrads</span>
                </a></li>
            </div>
        </ul>
    </aside>

    <main class="main-content">
        <?php if (isset($content)) echo $content; ?>
        
        <!-- Z-Score Modal - Dashboard Version -->
        <div id="zScoreModal" class="dashboard-modal" style="display: none;">
            <div class="dashboard-modal-content">
                <div class="modal-header">
                    <h2>Enter Your Z-Score Information</h2>
                    <span class="close" id="closeModal">&times;</span>
                </div>
                
                <form id="zScoreForm" class="modal-form">
                    <div class="form-group">
                        <label for="district" class="form-label required">District</label>
                        <div class="form-select-group">
                            <select id="district" name="district" class="form-select" required>
                                <option value="" disabled selected>Select your district</option>
                                <option value="colombo">Colombo</option>
                                <option value="gampaha">Gampaha</option>
                                <option value="kalutara">Kalutara</option>
                                <option value="kandy">Kandy</option>
                                <option value="matale">Matale</option>
                                <option value="nuwara-eliya">Nuwara Eliya</option>
                                <option value="galle">Galle</option>
                                <option value="matara">Matara</option>
                                <option value="hambantota">Hambantota</option>
                                <option value="jaffna">Jaffna</option>
                                <option value="vanni">Vanni</option>
                                <option value="batticaloa">Batticaloa</option>
                                <option value="digamadulla">Digamadulla</option>
                                <option value="trincomalee">Trincomalee</option>
                                <option value="kurunegala">Kurunegala</option>
                                <option value="puttalam">Puttalam</option>
                                <option value="anuradhapura">Anuradhapura</option>
                                <option value="polonnaruwa">Polonnaruwa</option>
                                <option value="badulla">Badulla</option>
                                <option value="monaragala">Monaragala</option>
                                <option value="ratnapura">Ratnapura</option>
                                <option value="kegalle">Kegalle</option>
                            </select>
                            <span class="form-select-arrow"></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="stream" class="form-label required">Stream</label>
                        <div class="form-select-group">
                            <select id="stream" name="stream" class="form-select" required>
                                <option value="" disabled selected>Select your stream</option>
                                <option value="physical-science">Physical Science</option>
                                <option value="biological-science">Biological Science</option>
                                <option value="commerce">Commerce</option>
                                <option value="arts">Arts</option>
                                <option value="technology">Technology</option>
                                <option value="science">Other</option>
                            </select>
                            <span class="form-select-arrow"></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Subjects</label>
                        <div class="subjects-container">
                            <div class="form-input-with-icon">
                                <input type="text" id="subject1" name="subject1" class="form-input" placeholder="Subject 1 (e.g., Physics)" required>
                                <i class="form-input-icon"></i>
                            </div>
                            <div class="form-input-with-icon">
                                <input type="text" id="subject2" name="subject2" class="form-input" placeholder="Subject 2 (e.g., Chemistry)" required>
                                <i class="form-input-icon"></i>
                            </div>
                            <div class="form-input-with-icon">
                                <input type="text" id="subject3" name="subject3" class="form-input" placeholder="Subject 3 (e.g., Mathematics)" required>
                                <i class="form-input-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="zScore" class="form-label required">Z-Score</label>
                        <div class="form-input-with-icon">
                            <input type="number" id="zScore" name="zScore" class="form-input" step="0.0001" min="0" max="3.0" placeholder="Enter your Z-score (e.g., 1.8543)" required>
                            <i class="form-input-icon"></i>
                        </div>
                        <span class="form-instructions">
                            <i>Enter your Z-score as a decimal number with up to 4 decimal places (e.g., 1.8543)</i>
                        </span>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" id="cancelBtn" class="btn btn-outline btn-full">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-full">Add Z-Score</button>
                    </div>
                </form>
            </div>
        </div>
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
        // Mobile menu toggle - Safe implementation
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', function() {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar) {
                    sidebar.classList.toggle('open');
                    this.classList.toggle('active');
                }
            });
        }

        // Sidebar link active state - Safe implementation
        const sidebarLinks = document.querySelectorAll('.sidebar-link');
        if (sidebarLinks.length > 0) {
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Remove active class from all links
                    document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
                    // Add active class to clicked link
                    this.classList.add('active');
                });
            });
        }
    </script>
    
    <!-- Load degree programs JavaScript if on degree-programs page -->
    <script>
        // Check if we're on the degree-programs page and load the script
        if (window.location.pathname.includes('degree-programs')) {
            const script = document.createElement('script');
            script.src = '/unihelper/views/js/degree-programs.js';
            script.defer = true;
            document.head.appendChild(script);
        }
    </script>
</body>
</html>