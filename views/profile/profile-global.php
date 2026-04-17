<?php
require_once dirname(__DIR__, 2) . '/models/connection.php';
require_once dirname(__DIR__, 2) . '/models/University.php';
require_once dirname(__DIR__, 2) . '/models/Major.php';
require_once dirname(__DIR__, 2) . '/models/user-stat.php';

$connectionModel = new app\models\Connection();
$userStat = new app\models\UserStat();
$currentUserId = $_SESSION['user_id'];
$targetUserId = $id ?? null;

if (!$targetUserId) {
    echo "<div class='error'>User ID not provided.</div>";
    return;
}

if ($currentUserId == $targetUserId) {
    echo "<script>window.location.href = '/unihelper/profile/view';</script>";
    return;
}

// 1. Fetch initial public details
$initialTarget = $connectionModel->getTargetUser($targetUserId, 'public');
if (!$initialTarget) {
    echo "<div class='profile-card-container'><div class='profile-card' style='padding: 2rem; text-align: center;'>User not found.</div></div>";
    return;
}

$isPublic = $initialTarget->public;
$state = $isPublic ? 'public' : 'private';

// 2. Check connection status
$statusRow = $connectionModel->checkStatus($currentUserId, $targetUserId);
$friendStatus = $statusRow ? $statusRow['status'] : 'none';
$initiatedBy = $statusRow ? $statusRow['requester_id'] : null;
$isViewerAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'role-admin');

if ($friendStatus === 'accepted') {
    $state = 'friend';
}

// Admins can inspect full profile details regardless of connection/public state.
if ($isViewerAdmin) {
    $state = 'friend';
}

// 3. Re-fetch user data with the final determined state
$target = $connectionModel->getTargetUser($targetUserId, $state);

// if the sesssion variable doesn't exist
if (!isset($_SESSION['last_viewed_user_id'])) {
    $_SESSION['last_viewed_user_id'] = -1; // initialize to an invalid user ID
}

if ($_SESSION['last_viewed_user_id'] != $targetUserId)
{
    $userStat->increment($targetUserId, 'profile_view_count');
    $_SESSION['last_viewed_user_id'] = $targetUserId;
}

// Determine profile picture safely avoiding undefined property or double slashes
$rawPic = $target->profile_picture ?? '/uploads/profilePictures/default-pfp.png';
// Normalize: remove any leading slash so concatenation doesn't produce double slashes
$normalizedPic = ltrim($rawPic, '/');
$profilePic = '/unihelper/public/' . $normalizedPic;


// Helpers
$universityName = 'Not specified';
if (isset($target->university) && is_numeric($target->university)) {
    $uModel = new app\models\University();
    foreach ($uModel->getAll() as $u) {
        if ($u->id == $target->university) { $universityName = $u->name; break; }
    }
}

$majorName = 'Not specified';
if (isset($target->major) && is_numeric($target->major)) {
    $mModel = new app\models\Major();
    foreach ($mModel->getAll() as $m) {
        if ($m->id == $target->major) { $majorName = $m->name; break; }
    }
}

$roleTitle = '';
switch($target->role) {
    case 'role-applicant': $roleTitle = 'Applicant'; break;
    case 'role-undergrad': $roleTitle = 'Undergraduate'; break;
    case 'role-profile': $roleTitle = 'University Staff'; break;
    case 'role-admin': $roleTitle = 'Administrator'; break;
    default: $roleTitle = 'Member';
}

require_once dirname(__DIR__, 2) . '/models/badge-user.php';

$badgeUserModel = new app\models\BadgeUser();
$userBadges = $badgeUserModel->getBadgeNames($targetUserId);

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

<link rel="stylesheet" href="/unihelper/views/css/profile.css">
<script src="/unihelper/views/js/profile-global.js"></script>
<style>
    /* Specific global profile tweaks to seamlessly integrate into the main theme logic */
    .gp-wa-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: #25D366;
        color: white;
        padding: 0.5rem 0.9rem;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: background 0.2s ease;
        margin-top: 8px;
    }
    .gp-wa-btn:hover {
        background: #1fb956;
    }
    .gp-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }
    .gp-private-message {
        padding: 3rem 1rem;
        text-align: center;
        color: var(--muted-foreground);
        background: var(--key);
        border-radius: 12px;
        border: 1px dashed var(--border);
        margin-top: 1.5rem;
    }
    .gp-private-message svg {
        margin-bottom: 1rem;
        color: var(--primary);
        opacity: 0.7;
    }
    .gp-private-message h3 {
        color: var(--text);
        margin-bottom: 0.5rem;
    }
    .btn-gp {
        padding: 0.6rem 1.2rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s;
        font-size: 0.95rem;
        font-family: inherit;
    }
    .btn-gp:hover {
        transform: translateY(-2px);
    }
    .btn-gp.primary {
        background: var(--btn-gradient-primary);
        color: white;
    }
    .btn-gp.primary:hover {
        box-shadow: var(--glow-primary);
    }
    .btn-gp.success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }
    .btn-gp.danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }
    .btn-gp.danger-outline {
        background: transparent;
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.5);
    }
    .btn-gp.danger-outline:hover {
        background: rgba(239, 68, 68, 0.1);
    }
    .btn-gp.disabled {
        background: transparent;
        color: var(--muted-foreground);
        border: 1px solid var(--border);
        cursor: not-allowed;
    }
    .btn-gp.disabled:hover {
        transform: none;
    }

    .gp-status-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #10b981;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        margin-left: 0.5rem;
        border: 2px solid var(--card);
    }

    .gp-report-btn {
        border-color: rgba(239, 68, 68, 0.45) !important;
        color: #ef4444 !important;
    }
    .gp-report-btn:hover {
        background: rgba(239, 68, 68, 0.12) !important;
        color: #ef4444 !important;
    }

    .gp-reportmodal {
        position: fixed;
        inset: 0;
        z-index: 10020;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    .gp-reportmodal-content {
        width: min(500px, 90%);
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 24px 64px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.04) inset;
        padding: 1.25rem 1.5rem;
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        max-height: 90vh;
        overflow-y: auto;
    }

    .gp-reportmodal-content::-webkit-scrollbar {
        width: 6px;
    }

    .gp-reportmodal-content::-webkit-scrollbar-track {
        background: transparent;
    }

    .gp-reportmodal-content::-webkit-scrollbar-thumb {
        background: var(--border);
        border-radius: 3px;
    }

    .gp-reportmodal-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        border: none;
        background: transparent;
        color: var(--muted-foreground);
        font-size: 2rem;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        width: 2rem;
        height: 2rem;
        transition: color 0.2s ease;
    }

    .gp-reportmodal-close:hover {
        color: var(--text);
    }

    .gp-reportmodal-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        margin-bottom: 1rem;
    }

    .gp-reportmodal-header h2 {
        margin: 0;
        color: var(--text);
        font-size: 1.25rem;
        font-weight: 700;
    }

    .gp-reportmodal-header p {
        margin: 0.25rem 0 0;
        color: var(--muted-foreground);
        font-size: 0.9rem;
        line-height: 1.4;
    }

    .gp-report-radio-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .gp-report-radio-label {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: var(--text_background);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .gp-report-radio-label:hover {
        border-color: var(--primary);
    }

    .gp-report-radio-label:has(input:checked) {
        border-color: #e5484d;
        background: rgba(229, 72, 77, 0.05);
    }

    .gp-report-radio-label input[type="radio"] {
        margin-top: 0.15rem;
        width: 1.1rem;
        height: 1.1rem;
        accent-color: #e5484d;
        cursor: pointer;
    }

    .gp-report-radio-content {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }

    .gp-report-radio-title {
        color: var(--text);
        font-weight: 600;
        font-size: 0.95rem;
    }

    .gp-report-radio-desc {
        color: var(--muted-foreground);
        font-size: 0.8rem;
    }

    .gp-report-details-group {
        display: none;
        flex-direction: column;
        gap: 0.35rem;
    }

    .gp-report-details-group label {
        color: var(--text);
        font-size: 0.9rem;
        font-weight: 600;
    }

    .gp-report-details-group textarea {
        width: 100%;
        min-height: 60px;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        background: var(--text_background);
        color: var(--text);
        padding: 0.7rem 0.8rem;
        resize: vertical;
        font: inherit;
    }

    .gp-report-actions {
        display: flex;
        gap: 1rem;
        margin-top: 0;
        padding-top: 1rem;
        border-top: 1px solid var(--border);
    }

    .gp-report-actions .btn {
        flex: 1;
        padding: 0.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .gp-report-submit-btn {
        background: #c23d41;
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        transition: background 0.2s;
    }

    .gp-report-submit-btn:hover:not(:disabled) {
        background: #cf2f35;
    }

    .gp-report-submit-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    [data-theme="light"] .gp-reportmodal {
        background: rgba(0, 0, 0, 0.08);
    }

    [data-theme="light"] .gp-reportmodal-content {
        box-shadow: 0 24px 64px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.04) inset;
    }

    [data-theme="light"] .gp-report-radio-label:has(input:checked) {
        background: rgba(207, 47, 53, 0.05);
    }
</style>

<div class="profile-card-container" id="globalProfileContainer" data-target-id="<?= htmlspecialchars($targetUserId) ?>" data-current-user-id="<?= htmlspecialchars($currentUserId) ?>">
    <div class="profile-card">
        
        <div class="profile-header">
            <h1 class="profile-title">User Profile</h1>
        </div>

        <div class="profile-card-header">
            <div class="profile-image">
                <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture">
            </div>

            <div class="profile-card-name-section">
                <h2 class="profile-card-name">
                    <?= htmlspecialchars($target->first_name . ' ' . $target->last_name) ?>
                    <?php if ($friendStatus === 'accepted'): ?>
                        <span class="gp-status-icon" title="You are connected">
                            <svg viewBox="0 0 24 24" fill="none" width="14" height="14" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"></path></svg>
                        </span>
                    <?php endif; ?>
                </h2>
                <div class="profile-role-badge"><?= htmlspecialchars($roleTitle) ?></div>

                <!-- Feature explicitly removed for Admin roles -->
                <?php if ($target->role !== 'role-admin'): ?>
                    <div class="gp-actions">
                        <?php if ($friendStatus === 'none'): ?>
                            <button class="btn-gp primary" onclick="handleConnection('request')">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                Add Friend
                            </button>
                        <?php elseif ($friendStatus === 'pending'): ?>
                            <?php if ($initiatedBy == $currentUserId): ?>
                                <button class="btn-gp danger-outline" onclick="handleConnection('cancel')">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                    Cancel Request
                                </button>
                            <?php else: ?>
                                <button class="btn-gp success" onclick="handleConnection('accept')">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"></path></svg>
                                    Accept Request
                                </button>
                                <button class="btn-gp danger-outline" onclick="handleConnection('reject')">
                                    Reject
                                </button>
                            <?php endif; ?>
                        <?php elseif ($friendStatus === 'accepted'): ?>
                            <button class="btn-gp danger" onclick="handleConnection('remove')">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="18" y1="9" x2="24" y2="15"></line><line x1="24" y1="9" x2="18" y2="15"></line></svg>
                                Remove Friend
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="profile-top-actions">
                <button class="btn btn-outline gp-report-btn" id="openProfileReportBtn" type="button">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 21V5"></path>
                        <path d="M5 5c2.2-1.4 4.4-1.4 6.6 0s4.4 1.4 6.6 0V13c-2.2 1.4-4.4 1.4-6.6 0S7.2 11.6 5 13"></path>
                    </svg>
                    Report User
                </button>
                <button class="btn btn-outline" id="openBadgeShowcaseBtn" type="button">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="6"></circle>
                        <path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"></path>
                    </svg>
                    View Badges
                </button>
                <a href="/unihelper/qa-forum?user=<?= htmlspecialchars($targetUserId) ?>" class="btn btn-outline">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    View Q&A Activity
                </a>
            </div>

            <div class="profile-public-card">
                <?php if ($isPublic): ?>
                    <div class="public-status public">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" fill="#4caf50"/>
                            <path d="M9 12l2 2l4 -4" stroke="#fff" stroke-width="2" fill="none"/>
                        </svg>
                        <span>Public Profile</span>
                    </div>
                <?php else: ?>
                    <div class="public-status private">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" fill="#f44336"/>
                            <path d="M8 12l2 2l4 -4" stroke="#fff" stroke-width="2" fill="none"/>
                        </svg>
                        <span>Private Profile</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-card-body">
            <?php if ($state === 'public' || $state === 'friend'): ?>
                
                <?php if ($target->role === 'role-applicant'): ?>
                <div class="profile-info-section">
                    <h3>Applicant Information</h3>
                    <div class="profile-info-items">
                        <div class="profile-info-item">
                            <span class="info-label">A/L Year</span>
                            <span class="info-value"><?= htmlspecialchars($target->al_year ?? 'Not specified') ?></span>
                        </div>
                    </div>
                </div>
                <?php elseif ($target->role === 'role-undergrad'): ?>
                <div class="profile-info-section">
                    <h3>University Information</h3>
                    <div class="profile-info-items">
                        <div class="profile-info-item">
                            <span class="info-label">University</span>
                            <span class="info-value"><?= htmlspecialchars($universityName) ?></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="info-label">Major</span>
                            <span class="info-value"><?= htmlspecialchars($majorName) ?></span>
                        </div>
                    </div>
                </div>
                <?php elseif ($target->role === 'role-profile'): ?>
                <div class="profile-info-section">
                    <h3>University Profile</h3>
                    <div class="profile-info-items">
                        <div class="profile-info-item">
                            <span class="info-label">University</span>
                            <span class="info-value"><?= htmlspecialchars($universityName) ?></span>
                        </div>
                        <div class="profile-info-item">
                            <span class="info-label">Role / Position</span>
                            <span class="info-value"><?= htmlspecialchars($target->profile_role ?? 'Not specified') ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($state === 'friend' && !empty($target->phone)): ?>
                <?php 
                    $rawPhone = preg_replace('/[^0-9]/', '', $target->phone);
                    $waPhone = (strpos($rawPhone, '0') === 0) ? '94' . substr($rawPhone, 1) : $rawPhone;
                    $displayPhone = (strpos($target->phone, '0') === 0) ? '+94 ' . substr($target->phone, 1) : $target->phone;
                ?>
                <div class="profile-info-section">
                    <h3>Contact Information</h3>
                    <div class="profile-info-items">
                        <div class="profile-info-item" style="border-bottom:none">
                            <span class="info-label">Mobile Number</span>
                            <span class="info-value">
                                <?= htmlspecialchars($displayPhone) ?><br>
                                <a href="https://wa.me/<?= $waPhone ?>" target="_blank" class="gp-wa-btn">
                                    <svg viewBox="0 0 448 512" width="16" height="16" fill="currentColor">
                                        <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.7 17.8 69.4 27.2 106.2 27.2 122.4 0 222-99.6 222-222 0-59.3-23-115.1-65-157.1zM223.9 446.3c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 365.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.5-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 186.2zm113.3-153.9c-6.1-3.1-36.1-17.8-41.7-19.8-5.6-2-9.7-3-13.7 3-4 6-15.3 19.3-18.8 23.3-3.5 4-7.1 4.5-13.2 1.5-6.1-3.1-25.8-9.5-49.1-30.4-18.1-16.1-30.3-36.1-33.9-42.1-3.5-6-.4-9.2 2.7-12.2 2.8-2.7 6.1-7.1 9.2-10.7 3-3.5 4.1-6.1 6.1-10.2 2-4.1 1-7.6-.5-10.7-1.5-3.1-13.7-33-18.8-45.2-4.9-12-10-10.3-13.7-10.5-3.5-.2-7.6-.2-11.7-.2-4.1 0-10.7 1.5-16.3 7.6-5.6 6.1-21.3 20.8-21.3 50.8 0 30 21.8 58.9 24.8 63 3 4.1 42.9 65.5 104 92 14.5 6.2 25.8 10 34.7 12.8 14.6 4.6 28 4 38.5 2.5 11.7-1.8 36.1-14.8 41.2-29.1 5.1-14.2 5.1-26.4 3.6-29.1-1.6-2.6-5.6-4.1-11.7-7.2z"/>
                                    </svg>
                                    Chat on WhatsApp
                                </a>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="gp-private-message">
                    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <h3>Private Account</h3>
                    <p>This information is only available to connected friends.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Profile Report Modal -->
<div class="gp-reportmodal" id="profileReportModal" aria-hidden="true">
    <div class="gp-reportmodal-content" role="dialog" aria-modal="true" aria-labelledby="profileReportTitle">
        <button class="gp-reportmodal-close" id="closeProfileReportBtn" aria-label="Close">&times;</button>

        <div class="gp-reportmodal-header">
            <h2 id="profileReportTitle">Report User</h2>
            <p>Select the reason that best matches this profile behavior.</p>
        </div>

        <form id="profileReportForm">
            <div class="gp-report-radio-group">
                <label class="gp-report-radio-label">
                    <input type="radio" name="profile_report_reason" value="harassment" required>
                    <div class="gp-report-radio-content">
                        <span class="gp-report-radio-title">Harassment</span>
                        <span class="gp-report-radio-desc">Bullying, threatening, or abusive behavior.</span>
                    </div>
                </label>
                <label class="gp-report-radio-label">
                    <input type="radio" name="profile_report_reason" value="spam">
                    <div class="gp-report-radio-content">
                        <span class="gp-report-radio-title">Spam</span>
                        <span class="gp-report-radio-desc">Unwanted repetitive outreach or promotional behavior.</span>
                    </div>
                </label>
                <label class="gp-report-radio-label">
                    <input type="radio" name="profile_report_reason" value="inappropriate_pfp">
                    <div class="gp-report-radio-content">
                        <span class="gp-report-radio-title">Inappropriate Profile Picture</span>
                        <span class="gp-report-radio-desc">Profile image violates platform standards.</span>
                    </div>
                </label>
                <label class="gp-report-radio-label">
                    <input type="radio" name="profile_report_reason" value="fake_account">
                    <div class="gp-report-radio-content">
                        <span class="gp-report-radio-title">Fake Account</span>
                        <span class="gp-report-radio-desc">Impersonation, suspicious identity, or non-genuine account.</span>
                    </div>
                </label>
                <label class="gp-report-radio-label">
                    <input type="radio" name="profile_report_reason" value="other">
                    <div class="gp-report-radio-content">
                        <span class="gp-report-radio-title">Other</span>
                        <span class="gp-report-radio-desc">Use this when your reason is not listed above.</span>
                    </div>
                </label>
            </div>

            <div class="gp-report-details-group" id="profileReportDetailsGroup">
                <label for="profileReportDetails">Details</label>
                <textarea id="profileReportDetails" placeholder="Provide relevant details for moderators..."></textarea>
            </div>

            <div class="gp-report-actions">
                <button type="button" class="btn btn-outline" id="cancelProfileReportBtn">Cancel</button>
                <button type="submit" class="btn gp-report-submit-btn" id="submitProfileReportBtn">Submit Report</button>
            </div>
        </form>
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
