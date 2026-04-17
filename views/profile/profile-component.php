<link rel="stylesheet" href="/unihelper/views/css/profile.css">

<?php
require_once dirname(__DIR__, 2) . '/models/University.php';
require_once dirname(__DIR__, 2) . '/models/Major.php';
require_once dirname(__DIR__, 2) . '/models/user-stat.php';
require_once dirname(__DIR__, 2) . '/models/badge-user.php';
?>

<?php
$profileOwnerId = $user->id ?? $user->user_id ?? null;
$profileViewCount = 0;

if ($profileOwnerId !== null) {
    $userStatModel = new app\models\UserStat();
    $profileViewCount = $userStatModel->getProfileViews($profileOwnerId);
}

$badgeUserModel = new app\models\BadgeUser();
$userBadges = $badgeUserModel->getBadgeNames($profileOwnerId);

// All badges with metadata for the showcase
$allBadgeMeta = [
    'curious-mind'       => ['name' => 'Curious Mind',        'desc' => 'Asked your very first question on the forum.'],
    'insightful-question'=> ['name' => 'Insightful Question', 'desc' => 'Your question received 5 or more upvotes.'],
    'top-question'       => ['name' => 'Top Question',        'desc' => 'Your question reached 15 or more upvotes.'],
    'regular-inquirer'   => ['name' => 'Regular Inquirer',    'desc' => 'Asked 10 or more questions on the forum.'],
    'discussion-starter' => ['name' => 'Discussion Starter',  'desc' => 'Your question received 5 or more answers.'],
    'peer-influencer'    => ['name' => 'Peer Influencer',     'desc' => 'Posted 10 or more answers to help others.'],
    'trendsetter'        => ['name' => 'Trendsetter',         'desc' => 'One of your answers received 10 or more upvotes.'],
    'explorer'           => ['name' => 'Explorer',            'desc' => 'Viewed 20 or more questions across the forum.'],
    'avid-voter'         => ['name' => 'Avid Voter',          'desc' => 'Cast 20 or more votes on posts and answers.'],
    'community-member'   => ['name' => 'Community Member',    'desc' => 'Earned by actively engaging in the community.'],
    'social-worker'      => ['name' => 'Social Worker',       'desc' => 'Connected with 5 or more peers on the platform.'],
    'celebrity'          => ['name' => 'Celebrity',           'desc' => 'Your profile was viewed 50 or more times.'],
];

// Build a lookup of acquired badges: slug => { name, description, awarded_at }
$acquiredMap = [];
if ($userBadges) {
    foreach ($userBadges as $b) {
        $rawSlug = null;
        $awardedAt = null;

        if (is_array($b)) {
            $rawSlug = $b['slug'] ?? $b['badge_slug'] ?? $b['name'] ?? null;
            $awardedAt = $b['awarded_at'] ?? $b['earned_at'] ?? null;
        } elseif (is_object($b)) {
            $rawSlug = $b->slug ?? $b->badge_slug ?? $b->name ?? null;
            $awardedAt = $b->awarded_at ?? $b->earned_at ?? null;
        }

        if (!is_string($rawSlug) || trim($rawSlug) === '') {
            continue;
        }

        $slug = strtolower(trim($rawSlug));
        if (!isset($allBadgeMeta[$slug])) {
            // Fallback if the DB row has badge name instead of slug.
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug ?? '', '-');
        }

        if ($slug !== '' && isset($allBadgeMeta[$slug])) {
            $acquiredMap[$slug] = [
                'awarded_at' => $awardedAt,
            ];
        }
    }
}

?>
<div class="profile-card-container">
    <div class="profile-card">
        <div class="profile-header">
            <h1 class="profile-title">My Profile</h1>
            <a href="profile/edit" class="btn btn-outline">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Edit Profile
            </a>
        </div>

        <div class="profile-card-header">
            <?php if($user->profilePicture): ?>
                <div class="profile-image">
                    <img src="<?= htmlspecialchars($user->profilePicture ? "/unihelper/public/" . $user->profilePicture : '/unihelper/views/assets/default-pfp.png') ?>" alt="Profile Picture">
                </div>
            <?php endif; ?>
            
            <div class="profile-card-name-section">
                <h2 class="profile-card-name"><?= htmlspecialchars($user->firstName . ' ' . $user->lastName) ?></h2>
                <div class="profile-role-badge"><?= htmlspecialchars(substr($user->role, 5)) ?></div>
            </div>

            <div class="profile-top-actions">
                <button class="btn btn-outline" id="openBadgeShowcaseBtn" type="button">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="6"></circle>
                        <path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"></path>
                    </svg>
                    View Badges
                </button>
                <a href="/unihelper/qa-forum?user=<?= htmlspecialchars($profileOwnerId) ?>" class="btn btn-outline">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    View my Q&A Activity
                </a>
            </div>

            <div class="profile-public-card">
                <?php if (!empty($user->public)): ?>
                    <div class="public-status public">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" fill="#4caf50"/>
                            <path d="M9 12l2 2l4 -4" stroke="#fff" stroke-width="2" fill="none"/>
                        </svg>
                        <span>Public Account</span>
                    </div>
                <?php else: ?>
                    <div class="public-status private">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" fill="#f44336"/>
                            <path d="M8 12l2 2l4 -4" stroke="#fff" stroke-width="2" fill="none"/>
                        </svg>
                        <span>Private Account</span>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        
        <div class="profile-card-body">
            <div class="profile-info-section">
                <h3>Contact Information</h3>
                <div class="profile-info-items">
                    <div class="profile-info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= htmlspecialchars($user->email) ?></span>
                    </div>
                    <div class="profile-info-item">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?= htmlspecialchars($user->phone) ?></span>
                    </div>
                    <div class="profile-info-item profile-views-item">
                        <span class="info-label">Profile Views</span>
                        <span class="info-value profile-view-count"><?= (int) $profileViewCount ?></span>
                    </div>
                </div>
            </div>
            
            <?php if($user->role === 'role-applicant'): ?>
            <div class="profile-info-section">
                <h3>Applicant Information</h3>
                <div class="profile-info-items">
                    <div class="profile-info-item">
                        <span class="info-label">A/L Year</span>
                        <span class="info-value"><?= htmlspecialchars($user->alYear ?? 'Not specified') ?></span>
                    </div>
                </div>
            </div>
            <?php elseif($user->role === 'role-undergrad'): ?>
            <div class="profile-info-section">
                <h3>University Information</h3>
                <div class="profile-info-items">
                    <div class="profile-info-item">
                        <span class="info-label">University</span>
                        <span class="info-value">
                            <?php 
                            if (isset($user->University) && is_numeric($user->University)) {
                                $universityModel = new app\models\University();
                                $universities = $universityModel->getAll();
                                foreach ($universities as $uni) {
                                    if ($uni->id == $user->University) {
                                        echo htmlspecialchars($uni->name);
                                        break;
                                    }
                                }
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="profile-info-item">
                        <span class="info-label">Major</span>
                        <span class="info-value">
                            <?php 
                            if (isset($user->major) && is_numeric($user->major)) {
                                $majorModel = new app\models\Major();
                                $majors = $majorModel->getAll();
                                foreach ($majors as $m) {
                                    if ($m->id == $user->major) {
                                        echo htmlspecialchars($m->name);
                                        break;
                                    }
                                }
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php elseif($user->role === 'role-profile'): ?>
            <div class="profile-info-section">
                <h3>University Profile</h3>
                <div class="profile-info-items">
                    <div class="profile-info-item">
                        <span class="info-label">University</span>
                        <span class="info-value">
                            <?php 
                            if (isset($user->University) && is_numeric($user->University)) {
                                $universityModel = new app\models\University();
                                $universities = $universityModel->getAll();
                                foreach ($universities as $uni) {
                                    if ($uni->id == $user->University) {
                                        echo htmlspecialchars($uni->name);
                                        break;
                                    }
                                }
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="profile-info-item">
                        <span class="info-label">Role</span>
                        <span class="info-value"><?= htmlspecialchars($user->profileRole ?? 'Not specified') ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="profile-actions">
                <a href="profile/change-password" class="btn btn-outline">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    Change Password
                </a>
                <button type="button" class="btn btn-outline profile-delete-btn" id="openDeleteAccountBtn" data-user-id="<?= htmlspecialchars($profileOwnerId) ?>">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                        <path d="M10 11v6"></path>
                        <path d="M14 11v6"></path>
                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                    </svg>
                    Delete My Account
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Confirmation Modal -->
<div id="deleteAccountConfirmModal" class="cp-otp-overlay" style="display:none">
    <div class="cp-otp-modal-content" role="dialog" aria-modal="true" aria-labelledby="deleteAccountConfirmTitle">
        <div class="cp-otp-header">
            <div class="cp-otp-icon-wrapper">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                    <path d="M10 11v6"></path>
                    <path d="M14 11v6"></path>
                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                </svg>
            </div>
            <h2 class="cp-otp-title" id="deleteAccountConfirmTitle">Delete Your Account</h2>
            <p class="cp-otp-subtitle">This will remove your profile from the platform.</p>
        </div>

        <div class="delete-confirm-body">
            <p>You're about to delete your account. Please review the details below before continuing:</p>
            <ul class="delete-confirm-list">
                <li>Your account and profile access will be removed.</li>
                <li>You can activate your account again by logging in.</li>
                <li>You will need your password and a one-time code to continue.</li>
            </ul>
            <p>Continue only if you are sure you want to delete your account.</p>
        </div>

        <div class="cp-otp-actions">
            <button type="button" class="btn btn-outline" id="deleteConfirmCancelBtn">Cancel</button>
            <button type="button" class="btn btn-primary" id="deleteConfirmContinueBtn">Continue</button>
        </div>
    </div>
</div>

<!-- Delete Account Password Modal -->
<div id="deleteAccountPasswordModal" class="cp-otp-overlay" style="display:none">
    <div class="cp-otp-modal-content" role="dialog" aria-modal="true" aria-labelledby="deleteAccountPasswordTitle">
        <div class="cp-otp-header">
            <div class="cp-otp-icon-wrapper">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <h2 class="cp-otp-title" id="deleteAccountPasswordTitle">Confirm Password</h2>
            <p class="cp-otp-subtitle">Enter your password to continue the deletion process.</p>
        </div>

        <div class="profile-edit-group">
            <label for="deleteAccountPassword">Password</label>
            <div class="cp-input-wrapper">
                <input type="password" id="deleteAccountPassword" name="deleteAccountPassword" placeholder="Enter your password" autocomplete="current-password">
                <button type="button" class="cp-toggle-btn" data-target="deleteAccountPassword" aria-label="Toggle password visibility">
                    <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="7" ry="5"/><circle cx="12" cy="12" r="2"/></svg>
                    <svg class="eye-slash-icon" style="display:none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="7" ry="5"/><circle cx="12" cy="12" r="2"/><line x1="5" y1="5" x2="19" y2="19"/></svg>
                </button>
            </div>
        </div>

        <div class="cp-otp-message cp-otp-error-msg" id="deletePasswordError"></div>
        <div class="cp-otp-message cp-otp-loading-msg" id="deletePasswordLoading">Verifying...</div>

        <div class="cp-otp-actions">
            <button type="button" class="btn btn-outline" id="deletePasswordCancelBtn">Cancel</button>
            <button type="button" class="btn btn-primary" id="deletePasswordContinueBtn">Continue</button>
        </div>
    </div>
</div>

<!-- Delete Account OTP Modal -->
<div id="deleteAccountOtpModal" class="cp-otp-overlay" style="display:none">
    <div class="cp-otp-modal-content">
        <div class="cp-otp-header">
            <div class="cp-otp-icon-wrapper">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <h2 class="cp-otp-title">Verify Your Identity</h2>
            <p class="cp-otp-subtitle">We've sent a 6-digit code to <strong id="deleteOtpEmailDisplay"><?= htmlspecialchars($user->email) ?></strong></p>
        </div>
        <div class="cp-otp-inputs">
            <input type="text" maxlength="1" class="cp-otp-digit" data-index="0">
            <input type="text" maxlength="1" class="cp-otp-digit" data-index="1">
            <input type="text" maxlength="1" class="cp-otp-digit" data-index="2">
            <input type="text" maxlength="1" class="cp-otp-digit" data-index="3">
            <input type="text" maxlength="1" class="cp-otp-digit" data-index="4">
            <input type="text" maxlength="1" class="cp-otp-digit" data-index="5">
        </div>
        <div class="cp-otp-message cp-otp-error-msg" id="deleteOtpError"></div>
        <div class="cp-otp-message cp-otp-loading-msg" id="deleteOtpLoading">Verifying...</div>
        <div class="cp-otp-actions">
            <button type="button" class="btn btn-outline cp-otp-cancel-btn" id="deleteOtpCancelBtn">Cancel</button>
            <button type="button" class="btn btn-primary cp-otp-verify-btn" id="deleteOtpVerifyBtn">
                <span>Verify &amp; Delete</span>
            </button>
        </div>
        <div class="cp-otp-resend">
            Didn't receive code? <a class="cp-otp-resend-link" id="deleteOtpResendLink">Resend</a>
        </div>
    </div>
</div>

<!-- Badge Showcase Modal -->
<div class="badge-showcase-overlay" id="badgeShowcaseModal">
    <div class="badge-showcase-modal">
        <div class="badge-showcase-header">
            <div class="badge-showcase-title-row">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="var(--primary)" stroke-width="2">
                    <circle cx="12" cy="8" r="6"></circle>
                    <path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"></path>
                </svg>
                <h2>Badge Showcase</h2>
            </div>
            <span class="badge-showcase-count"><?= count($acquiredMap) ?> / <?= count($allBadgeMeta) ?> Earned</span>
            <button class="badge-showcase-close" id="closeBadgeShowcaseBtn" type="button" aria-label="Close">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <div class="badge-showcase-grid">
            <?php foreach ($allBadgeMeta as $slug => $meta): 
                $isAcquired = isset($acquiredMap[$slug]);
                $awardedAt = $isAcquired ? ($acquiredMap[$slug]['awarded_at'] ?? null) : null;
            ?>
            <div class="badge-showcase-item <?= $isAcquired ? 'acquired' : 'locked' ?>">
                <div class="badge-img-wrapper">
                    <img src="/unihelper/public/assets/badges_hq/<?= htmlspecialchars($slug) ?>.png" 
                         alt="<?= htmlspecialchars($meta['name']) ?>"
                         loading="lazy">
                    <?php if (!$isAcquired): ?>
                        <div class="badge-lock-icon">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="badge-showcase-tooltip">
                    <strong><?= htmlspecialchars($meta['name']) ?></strong>
                    <p><?= htmlspecialchars($meta['desc']) ?></p>
                    <span class="badge-tooltip-date <?= $isAcquired ? 'earned' : 'not-earned' ?>">
                        <?php if ($isAcquired): ?>
                            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="#4caf50" stroke-width="2.5"><path d="M20 6L9 17l-5-5"></path></svg>
                            Earned <?= $awardedAt ? date('M j, Y', strtotime($awardedAt)) : 'recently' ?>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            Not acquired yet
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="/unihelper/views/js/profile-delete.js"></script>

<script>
(function () {
    const modal = document.getElementById('badgeShowcaseModal');
    const openBtn = document.getElementById('openBadgeShowcaseBtn');
    const closeBtn = document.getElementById('closeBadgeShowcaseBtn');

    if (!modal) {
        return;
    }

    // Ensure the overlay is attached to body to avoid clipping by parent containers.
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    const openModal = function () {
        modal.style.display = 'flex';
        document.body.classList.add('badge-modal-open');
    };

    const closeModal = function () {
        modal.style.display = 'none';
        document.body.classList.remove('badge-modal-open');
    };

    if (openBtn) {
        openBtn.addEventListener('click', openModal);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeModal();
        }
    });
})();
</script>