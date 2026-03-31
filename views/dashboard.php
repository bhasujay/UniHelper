<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Prevent FOUC: apply saved theme before CSS loads -->
    <script>
        (function() {
            var t = localStorage.getItem('unihelper-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    <base href="/unihelper/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniHelper - From Application to Graduation</title>

    <link rel="stylesheet" href="/unihelper/views/css/style.css">
    <link rel="stylesheet" href="/unihelper/views/css/dashboard.css">
    <link rel="stylesheet" href="/unihelper/views/css/components/cards.css">
    <link rel="stylesheet" href="/unihelper/views/css/degree-programs.css">
    <?php
    // Load role-specific CSS based on user role
    $roleMap = [
        'role-applicant' => 'role-applicant.css',
        'role-undergrad' => 'role-undergrad.css',
        'role-profile' => 'role-profile.css',
        'role-admin' => 'role-admin.css'
    ];
    
    // Load component-specific CSS if available
    if (isset($activeComponent)) {
        $componentCssPath = __DIR__ . "/css/{$activeComponent}.css";
        $componentCssComponentPath = __DIR__ . "/css/components/{$activeComponent}.css";
        
        // Check in main css folder first
        if (file_exists($componentCssPath)) {
            echo '<link rel="stylesheet" href="/unihelper/views/css/' . $activeComponent . '.css">';
        }
        // Then check in components folder
        elseif (file_exists($componentCssComponentPath)) {
            echo '<link rel="stylesheet" href="/unihelper/views/css/components/' . $activeComponent . '.css">';
        }
    }
    ?>
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
                <!-- Notification Bell Button -->
                <button id="notificationBellBtn" class="notification-bell-btn" type="button" aria-label="Notifications">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span id="notificationDot" class="notification-dot"></span>
                </button>
                <div class="profile-container" id="profileDropdownTrigger">
                    <div class="profile-picture" >
                        <img class="profile-img" src="<?= htmlspecialchars($user->profilePicture ? "/unihelper/public/" . $user->profilePicture : '/unihelper/views/assets/default-pfp.png') ?>">
                    </div>
                    <img id="default-pfp" src="/unihelper/views/assets/default-pfp.png" style="display:none;">
                    <div class="profile-info">
                        <span class="profile-name"><?= htmlspecialchars($user->firstName) ?></span>
                        <span id="profileRole" class="profile-role"><?= htmlspecialchars(substr($user->role, 5)) ?></span>
                        <span id="profileModStatus" style="display:none;"><?= htmlspecialchars($user->mod) ?></span>
                        <span id="profileUserId" style="display:none;"><?= htmlspecialchars($user->id) ?></span>
                    </div>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="dropdown-header">
                            <div class="dropdown-user-info">
                                <span id="profileName" class="dropdown-name"><?= htmlspecialchars($user->firstName . ' ' . $user->lastName) ?></span>
                                <?php
                                $email = $user->email;
                                if (strlen($email) > 20 && ($atPos = strpos($email, '@')) !== false) {
                                    $visible = substr($email, 0, min(10, $atPos));
                                    $shortEmail = $visible . '..' . substr($email, $atPos);
                                } else {
                                    $shortEmail = $email;
                                }
                                ?>
                                <span class="dropdown-email"><?= htmlspecialchars($shortEmail) ?></span>
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
                            <button class="dropdown-item theme-toggle-btn" id="themeToggleBtn" type="button">
                                <svg id="themeIconDark" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                                </svg>
                                <svg id="themeIconLight" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                    <circle cx="12" cy="12" r="5"></circle>
                                    <line x1="12" y1="1" x2="12" y2="3"></line>
                                    <line x1="12" y1="21" x2="12" y2="23"></line>
                                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                                    <line x1="1" y1="12" x2="3" y2="12"></line>
                                    <line x1="21" y1="12" x2="23" y2="12"></line>
                                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                                </svg>
                                Change Theme
                            </button>
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
                        <a href="<?= htmlspecialchars($item['component']) ?>" class="sidebar-link <?= $activeComponent === $item['component'] ? 'active' : '' ?>">
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
            <a href="mailto:project.unihelper@gmail.com" class="sidebar-footer-link">Support: project.unihelper@gmail.com</a>
            <div class="sidebar-footer-copy">&copy; <?= date('Y') ?> UniHelper</div>
        </div>
    </aside>

    <main class="main-content"
          id="dashboardMain"
          data-component="<?= htmlspecialchars($activeComponent ?? '') ?>"
          data-page-params="<?= htmlspecialchars(json_encode($pageParams ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">
        <?php if (isset($content)) echo $content; ?>
    </main>


    <!-- Dashboard scripts -->
    <script>
        // Theme toggle button - switches between dark/light themes
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            const themeIconDark  = document.getElementById('themeIconDark');
            const themeIconLight = document.getElementById('themeIconLight');

            if (themeToggleBtn && themeIconDark && themeIconLight) {
                // Sync icon state with current theme
                var current = document.documentElement.getAttribute('data-theme') || 'dark';
                themeIconDark.style.display  = current === 'dark' ? '' : 'none';
                themeIconLight.style.display = current === 'light' ? '' : 'none';

                themeToggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var isDark = document.documentElement.getAttribute('data-theme') !== 'light';
                    var newTheme = isDark ? 'light' : 'dark';

                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('unihelper-theme', newTheme);

                    themeIconDark.style.display  = newTheme === 'dark' ? '' : 'none';
                    themeIconLight.style.display = newTheme === 'light' ? '' : 'none';
                });
            }
        });

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

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>
    <script>
        // Toast Notification Function
        function showToast(message, type = 'success', duration = 5000) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const typeText = type === 'success' ? 'Success' : 'Error';
            
            toast.innerHTML = `
                <div>
                    <div class="toast-type">${typeText}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" aria-label="Close">&times;</button>
            `;
            
            container.appendChild(toast);
            
            // Close button handler
            const closeBtn = toast.querySelector('.toast-close');
            closeBtn.addEventListener('click', () => {
                removeToast(toast);
            });
            
            // Auto-remove after duration
            setTimeout(() => {
                removeToast(toast);
            }, duration);
        }
        
        function removeToast(toast) {
            toast.classList.add('hiding');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }
    </script>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="confirmation-modal">
        <div class="confirmation-modal-content" role="dialog" aria-modal="true" aria-labelledby="confirmationTitle" aria-describedby="confirmationMessage">
            <h3 id="confirmationTitle" class="confirmation-modal-title">Please Confirm</h3>
            <p id="confirmationMessage" class="confirmation-modal-message"></p>
            <div class="confirmation-modal-actions">
                <button id="cancelButton" type="button" class="btn btn-outline">Cancel</button>
                <button id="confirmButton" type="button" class="btn btn-primary">Confirm</button>
            </div>
        </div>
    </div>
    <script>
        // Confirmation Modal Functionality
        (function() {
            const modal = document.getElementById('confirmationModal');
            const messageEl = document.getElementById('confirmationMessage');
            const confirmButton = document.getElementById('confirmButton');
            const cancelButton = document.getElementById('cancelButton');

            if (!modal || !messageEl || !confirmButton || !cancelButton) {
                return;
            }

            const nativeConfirm = window.confirm.bind(window);
            let activeResolver = null;
            const pendingQueue = [];

            function openModal(message, resolve) {
                activeResolver = resolve;
                messageEl.textContent = typeof message === 'string' ? message : String(message ?? '');
                modal.classList.add('show');
                cancelButton.focus();
            }

            function closeModal(confirmed) {
                if (!activeResolver) {
                    return;
                }

                const resolve = activeResolver;
                activeResolver = null;
                modal.classList.remove('show');
                resolve(confirmed);

                if (pendingQueue.length > 0) {
                    const nextItem = pendingQueue.shift();
                    openModal(nextItem.message, nextItem.resolve);
                }
            }

            function confirmWithModal(message) {
                return new Promise((resolve) => {
                    pendingQueue.push({ message, resolve });

                    if (!activeResolver) {
                        const nextItem = pendingQueue.shift();
                        openModal(nextItem.message, nextItem.resolve);
                    }
                });
            }

            confirmButton.addEventListener('click', () => closeModal(true));
            cancelButton.addEventListener('click', () => closeModal(false));

            // Clicking outside the dialog acts as cancel.
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal(false);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (!modal.classList.contains('show')) {
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeModal(false);
                }

                if (event.key === 'Enter') {
                    event.preventDefault();
                    closeModal(true);
                }
            });

            window.nativeConfirm = nativeConfirm;
            window.confirm = confirmWithModal;
        })();

    </script>

    <!-- Notification Modal -->
    <div id="notificationModalOverlay" class="notification-modal-overlay">
        <div id="notificationModal" class="notification-modal">
            <!-- Modal Header -->
            <div class="notification-modal-header">
                <h2 class="notification-modal-title">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    Notifications
                </h2>
                <button id="notificationModalClose" class="notification-modal-close" type="button" aria-label="Close notifications">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Tabs -->
            <div class="notification-tabs">
                <button class="notification-tab active" data-tab="new" type="button">
                    New
                    <span id="newNotifCount" class="notification-tab-badge" style="display:none;">0</span>
                </button>
                <button class="notification-tab" data-tab="opened" type="button">
                    Opened
                </button>
                <div class="notification-tab-indicator"></div>
            </div>

            <!-- Tab Panels -->
            <div class="notification-panels">
                <!-- New Notifications Panel -->
                <div id="notifPanelNew" class="notification-panel active" data-panel="new">
                    <div class="notification-list" id="newNotifList">
                        <!-- Items will be cloned from the template and appended here -->
                    </div>
                    <div class="notification-empty" id="newNotifEmpty">
                        <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                        <p>No new notifications</p>
                        <span>You're all caught up!</span>
                    </div>
                </div>

                <!-- Opened Notifications Panel -->
                <div id="notifPanelOpened" class="notification-panel" data-panel="opened">
                    <div class="notification-list" id="openedNotifList">
                        <!-- Items will be cloned from the template and appended here -->
                    </div>
                    <div class="notification-empty" id="openedNotifEmpty">
                        <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <p>No opened notifications</p>
                        <span>Notifications you've read will appear here</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Item Template (hidden, cloned by JS) -->
    <template id="notificationItemTemplate">
        <div class="notification-item" data-notif-id="">
            <div class="notification-item-icon">
                <!-- Icon will be set by JS based on type -->
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div class="notification-item-body">
                <p class="notification-item-title">Notification Title</p>
                <p class="notification-item-message">Notification message goes here.</p>
                <span class="notification-item-time">Just now</span>
            </div>
            <button class="notification-item-action" type="button" aria-label="Mark as read">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </button>
        </div>
    </template>

    <script src="/unihelper/views/js/notification.js"></script>

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