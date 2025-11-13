<!DOCTYPE html>
<html lang="en">
<head>
    <base href="/unihelper/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniHelper - From Application to Graduation</title>

    <link rel="stylesheet" href="/unihelper/views/css/style.css">
    <link rel="stylesheet" href="/unihelper/views/css/dashboard.css">
    <link rel="stylesheet" href="/unihelper/views/css/profile.css">
    <link rel="stylesheet" href="/unihelper/views/css/components/cards.css">
    <link rel="stylesheet" href="/unihelper/views/css/components/<?= htmlspecialchars($user->role) ?>.css">
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
                    <div class="profile-picture" >
                        <img class="profile-img" src="<?= htmlspecialchars($user->profilePicture ? "/unihelper/public/" . $user->profilePicture : '/unihelper/views/assets/default-pfp.png') ?>">
                    </div>
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
                            <div class="dropdown-profile-picture">
                                <img src="<?= htmlspecialchars($user->profilePicture ? "/unihelper/public/" . $user->profilePicture : '/unihelper/views/assets/default-pfp.png') ?>" alt="Profile Picture" class="dropdown-profile-img">
                            </div>
                        </div>
                        <div class="dropdown-body">
                            <a href="profile/view" class="dropdown-item">
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
            <!-- Undergraduate Features -->
            <div class="sidebar-section">
                <div class="sidebar-section-title"><?= htmlspecialchars($role_title) ?></div>
                <?php foreach ($sidebar as $item): ?>
                    <li>
                        <a href="dashboard/<?= htmlspecialchars($item['component']) ?>" class="sidebar-link <?= $activeComponent === $item['component'] ? 'active' : '' ?>">
                            <i class="fas fa-question-circle"></i>
                            <span><?= htmlspecialchars($item['title']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </div>
        </ul>

        <!-- Minimal footer -->
        <div class="sidebar-footer">
            <div class="sidebar-footer-logo">UniHelper</div>
            <a href="mailto:unihelper@gmail.com" class="sidebar-footer-link">Support: unihelper@gmail.com</a>
            <div class="sidebar-footer-copy">&copy; <?= date('Y') ?> UniHelper</div>
        </div>
    </aside>

    <main class="main-content">
        <?php if (isset($content)) echo $content; ?>
    </main>

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

    <!-- Error Modal Script -->
    <?php if (isset($error) && !empty($error)): ?>
    <style>
        .modal-error-box {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .modal-error-content {
            background: white;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
        }
        
        .modal-error-content span {
            color: #d32f2f;
            font-weight: 500;
            display: block;
            margin-bottom: 15px;
        }
        
        .modal-error-close {
            background-color: #d32f2f;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .modal-error-close:hover {
            background-color: #b71c1c;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalErrorBox = document.createElement('div');
            modalErrorBox.className = 'modal-error-box';
            modalErrorBox.style.display = 'flex';
            modalErrorBox.innerHTML = `
                <div class="modal-error-content">
                    <span><?php echo htmlspecialchars($error); ?></span>
                    <br>
                    <button class="modal-error-close">OK</button>
                </div>
            `;
            document.body.appendChild(modalErrorBox);
            console.log("motherfuker");
            
            modalErrorBox.querySelector('.modal-error-close').onclick = function() {
                modalErrorBox.remove();
            };
        });
    </script>
    <?php endif; ?>
</body>
</html>