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

if ($friendStatus === 'accepted') {
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

// Determine profile picture safely avoiding Undefined property warning
$profilePicPath = $target->profile_picture;
$profilePic = "/unihelper/public/" . $target->profile_picture;


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
