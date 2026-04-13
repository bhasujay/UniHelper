<link rel="stylesheet" href="/unihelper/views/css/components/moderation.css">

<?php
require_once __DIR__ . '/../../models/User.php';
use App\Models\User;

$user = new User();
$user = $user->findById($_SESSION['user_id']);
$is_moderator = ($user && (int) $user->mod === 1) ? 1 : 0;
$is_admin = ($user && $user->role === 'role-admin') ? 1 : 0;
?>

<div class="moderation-wrapper">
    <?php if ($is_admin): ?>
        <script src="/unihelper/views/js/moderation-admin.js"></script>

        <div class="mod-admin-layout">
            <!-- Left Side: Reports Queue -->
            <div class="mod-admin-left">
                <div class="mod-panel-header">
                    <div class="mod-header-text">
                        <h1 class="mod-panel-title">Moderation Queue</h1>
                        <p class="mod-panel-subtitle">Global oversight of reported content</p>
                    </div>
                    <div class="mod-filter-group" style="display:flex; align-items:center; gap:var(--spacing-sm);">
                        <button id="adminRefreshReportsBtn" class="mod-refresh-btn" title="Refresh Reports">
                            <span class="refresh-icon">🔄</span>
                        </button>
                        <div class="mod-search-container admin-search-container">
                            <input type="text" id="adminReportSearch" class="mod-search-input" placeholder="Search reports...">
                            <button type="button" id="adminSearchClearBtn" class="mod-search-clear" style="display: none;">&times;</button>
                        </div>
                    </div>
                </div>

                <div class="mod-tabs" id="adminReportsTabs">
                    <button class="mod-tab active" data-tab="admin-pending">
                        Pending
                        <span class="mod-tab-count" id="adminPendingCount" style="display:none;">0</span>
                    </button>
                    <button class="mod-tab" data-tab="admin-mods-resolved">
                        Mod Resolved
                        <span class="mod-tab-count" id="adminModsResolvedCount" style="display:none;">0</span>
                    </button>
                    <button class="mod-tab" data-tab="admin-forwarded">
                        Forwarded
                        <span class="mod-tab-count" id="adminForwardedCount" style="display:none;">0</span>
                    </button>
                    <button class="mod-tab" data-tab="admin-resolved">
                        Admin Resolved
                        <span class="mod-tab-count" id="adminResolvedCount" style="display:none;">0</span>
                    </button>
                    <div class="mod-tab-indicator"></div>
                </div>

                <div class="mod-panels" id="adminReportsPanels">
                    <div class="mod-tab-panel active" data-panel="admin-pending">
                        <div class="mod-report-list" id="adminPendingList"></div>
                    </div>
                    <div class="mod-tab-panel" data-panel="admin-mods-resolved">
                        <div class="mod-report-list" id="adminModsResolvedList"></div>
                    </div>
                    <div class="mod-tab-panel" data-panel="admin-forwarded">
                        <div class="mod-report-list" id="adminForwardedList"></div>
                    </div>
                    <div class="mod-tab-panel" data-panel="admin-resolved">
                        <div class="mod-report-list" id="adminResolvedList"></div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Moderators Management -->
            <div class="mod-admin-right">
                <div class="mod-panel-header">
                    <div class="mod-header-text">
                        <h1 class="mod-panel-title">Team Management</h1>
                        <p class="mod-panel-subtitle">Manage system moderators</p>
                    </div>
                    <button id="adminRefreshModsBtn" class="mod-refresh-btn" title="Refresh Team">
                        <span class="refresh-icon">🔄</span>
                    </button>
                </div>

                <div class="mod-tabs" id="adminModsTabs">
                    <button class="mod-tab active" data-tab="admin-mods-all">
                        All
                        <span class="mod-tab-count" id="adminModsAllCount" style="display:none;">0</span>
                    </button>
                    <button class="mod-tab" data-tab="admin-mods-requests">
                        Requests
                        <span class="mod-tab-count" id="adminModsRequestsCount" style="display:none;">0</span>
                    </button>
                    <div class="mod-tab-indicator"></div>
                </div>

                <div class="mod-panels admin-mod-panels" id="adminModsPanels">
                    <div class="mod-tab-panel active" data-panel="admin-mods-all">
                        <div class="mod-modlist" id="adminModsAllList"></div>
                    </div>
                    <div class="mod-tab-panel" data-panel="admin-mods-requests">
                        <div class="mod-modlist" id="adminModsRequestsList"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overlays (Hidden by default) -->
        <!-- View Moderator Overlay -->
        <div id="adminViewModOverlay" class="mod-admin-overlay" style="display: none;">
            <div class="mod-admin-modal">
                <button type="button" class="mod-modal-close-icon close-modal-btn">&times;</button>
                <div class="mod-profile-header">
                    <img class="mod-profile-avatar" id="viewModAvatar" src="/unihelper/views/assets/default-pfp.png" alt="">
                    <div class="mod-profile-details">
                        <h2 id="viewModName">Name</h2>
                        <p id="viewModUni">University</p>
                    </div>
                </div>
                <div class="mod-modal-actions">
                    <button type="button" class="mod-btn-outline" id="viewModProfileBtn">View Profile</button>
                    <button type="button" class="mod-btn-outline" id="viewActivityBtn">View Activity</button>
                    <button type="button" class="mod-btn-submit" style="background: #ff4757; box-shadow: 0 5px 15px rgba(255, 71, 87, 0.3);" id="removeModBtn">Remove Access</button>
                </div>
            </div>
        </div>

        <!-- View Request Overlay -->
        <div id="adminViewReqOverlay" class="mod-admin-overlay" style="display: none;">
            <div class="mod-admin-modal">
                <button type="button" class="mod-modal-close-icon close-modal-btn">&times;</button>
                <div class="mod-profile-header">
                    <img class="mod-profile-avatar" id="viewReqAvatar" src="/unihelper/views/assets/default-pfp.png" alt="">
                    <div class="mod-profile-details">
                        <h2 id="viewReqName">Name</h2>
                        <p id="viewReqUni">University</p>
                    </div>
                </div>
                <div class="mod-motivation-box">
                    <div class="mod-motivation-title">Moderator Message</div>
                    <p id="viewReqMotivation">User motivation statement replaces this text...</p>
                </div>
                <div class="mod-modal-actions">
                    <button type="button" class="mod-btn-outline" id="viewReqProfileBtn">View Profile</button>
                    <button type="button" class="mod-btn-submit" style="background: #ff4757; box-shadow: 0 5px 15px rgba(255, 71, 87, 0.3);" id="rejectReqBtn">Reject</button>
                    <button type="button" class="mod-btn-submit" style="background: #2ed573; box-shadow: 0 5px 15px rgba(46, 213, 115, 0.3);" id="acceptReqBtn">Accept</button>
                </div>
            </div>
        </div>

        <!-- Admin Report Card Template -->
        <template id="adminReportCardTemplate">
            <div class="mod-report-card">
                <div class="mod-report-top">
                    <div class="mod-report-meta">
                        <span class="mod-report-type-badge"></span>
                        <span class="mod-report-time"></span>
                    </div>
                </div>
                <div class="mod-report-body">
                    <p class="mod-report-reason"></p>
                    <p class="mod-report-text"></p>
                </div>
                <div class="mod-report-footer">
                    <div class="mod-reporter-group" style="display: flex; align-items: center;">
                        <div class="mod-reporter">
                            <img class="mod-reporter-avatar reporter-avatar" src="/unihelper/views/assets/default-pfp.png" alt="">
                            <span class="mod-reporter-name reporter-name"></span>
                        </div>
                    </div>
                    <div class="mod-report-actions"></div>
                </div>
            </div>
        </template>

        <!-- Admin Mod Card Template -->
        <template id="adminModCardTemplate">
            <div class="admin-mod-card">
                <img class="admin-mod-avatar" src="/unihelper/views/assets/default-pfp.png" alt="">
                <div class="admin-mod-info">
                    <div class="admin-mod-name">Name</div>
                    <div class="admin-mod-uni">University</div>
                </div>
                <button class="mod-action-btn action-undo admin-mod-action-btn">Action</button>
            </div>
        </template>
    <?php elseif (!$is_moderator): ?>
        <script src="/unihelper/views/js/moderation.js"></script>
        <div class="mod-native-layout">
            <div class="mod-hero-compact">
                <h1 class="mod-hero-title">Step up. <span class="gradient-text">Become a Moderator.</span></h1>
                <p class="mod-hero-subtitle">Help ensure the Q&A Forum remains a reliable, respectful environment.</p>
            </div>
            
            <div class="mod-content-grid">
                <div class="mod-details-section">
                    <div class="mod-details-grid-compact">
                        <div class="mod-feature">
                            <div class="mod-feature-icon">🛡️</div>
                            <div>
                                <h4>Protecting Information</h4>
                                <p>Monitor academic resources for accuracy.</p>
                            </div>
                        </div>
                        <div class="mod-feature">
                            <div class="mod-feature-icon">🔍</div>
                            <div>
                                <h4>Managing Reports</h4>
                                <p>Be the first line of defense against rule violations.</p>
                            </div>
                        </div>
                        <div class="mod-feature">
                            <div class="mod-feature-icon">🚫</div>
                            <div>
                                <h4>Filtering Spam</h4>
                                <p>Proactively remove irrelevant content.</p>
                            </div>
                        </div>
                        <div class="mod-feature">
                            <div class="mod-feature-icon">🤝</div>
                            <div>
                                <h4>Inter-University Stewardship</h4>
                                <p>Bridge the gap with a neutral tone.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mod-form-side">
                    <div class="mod-form-card">
                        <h3>Submit Your Application</h3>
                        <p class="mod-form-desc">Tell us why you are a great fit for the moderation team.</p>
                        <form id="moderatorApplicationForm">
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <textarea id="motivationInput" class="form-control mod-textarea" rows="4" placeholder="Your motivation statement..." required></textarea>
                            </div>
                            <div class="form-actions mod-form-actions">
                                <button type="submit" id="applyModeratorBtn" class="mod-btn-submit">Apply for a Moderator</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="modGuideOverlay" class="mod-guide-overlay" style="display: none;">
            <div class="mod-guide-modal">
                <div class="mod-guide-header">
                    <h2>The UniHelper Moderator's Guide</h2>
                </div>
                <div class="mod-guide-content">
                    <p class="mod-intro-text">As a moderator, your authority is strictly limited to the functions provided within the Moderation Module UI. You do not have direct access to the database or the server file system. Your role is focused on managing the visibility of content through the following three protocols.</p>
                    
                    <h4>1. The Three Allowed Actions</h4>
                    <p>The moderation component allows you to take only one of these three actions for any reported item:</p>
                    <ul class="mod-list-styled">
                        <li><strong>Ignore (Dismiss):</strong> If a report is unfounded, you will "Ignore" it. This removes the report from your queue while leaving the Question or Answer in its normal state.</li>
                        <li><strong>Flag (Restrict Visibility):</strong> If content violates rules, you will "Flag" it. This uses the system component to update the post status to deleted or banned, hiding it from public view. This does not delete the record or any associated files from the server.</li>
                        <li><strong>Forward to Admin:</strong> For severe or complex violations, you must escalate the report to the System Administrator for a final, high-level review.</li>
                    </ul>

                    <h4>2. System Boundaries</h4>
                    <ul class="mod-list-styled">
                        <li><strong>No File Management:</strong> The system does not provide features for moderators to delete images, documents, or attachments.</li>
                        <li><strong>Limited UI Access:</strong> You can only interact with content through the provided moderation tools. You cannot modify user accounts or core platform settings.</li>
                        <li><strong>Visibility vs. Deletion:</strong> Your actions only change how content is displayed to the public; you cannot permanently destroy data.</li>
                    </ul>

                    <h4>3. Professional Standards</h4>
                    <ul class="mod-list-styled">
                        <li><strong>Inter-University Neutrality:</strong> You must provide unbiased oversight for all users, regardless of their university or major.</li>
                        <li><strong>Accountability:</strong> Every decision you make—Ignore, Flag, or Forward—is logged by the system. Abuse of these limited tools will result in the immediate removal of your moderator status.</li>
                        <li><strong>Confidentiality:</strong> All reports and internal moderation processes are strictly confidential.</li>
                    </ul>
                </div>
                <div class="mod-guide-actions">
                    <button type="button" id="disagreeGuideBtn" class="mod-btn-outline">I do not agree</button>
                    <button type="button" id="agreeGuideBtn" class="mod-btn-submit">I agree, continue</button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <script src="/unihelper/views/js/moderation.js"></script>
        <!-- ========== MODERATOR PANEL ========== -->
        <div class="mod-panel">
            <div class="mod-panel-header">
                <div class="mod-header-text">
                    <h1 class="mod-panel-title">Moderation</h1>
                    <p class="mod-panel-subtitle">Review and manage reported content</p>
                </div>
                <button id="refreshPendingBtn" class="mod-refresh-btn">
                    <span class="refresh-icon">🔄</span>
                </button>
            </div>

            <div class="mod-tabs">
                <button class="mod-tab active" data-tab="pending">
                    Pending
                    <span class="mod-tab-count" id="pendingCount" style="display:none;">0</span>
                </button>
                <button class="mod-tab" data-tab="resolved">
                    Resolved
                    <span class="mod-tab-count" id="resolvedCount" style="display:none;">0</span>
                </button>
                <button class="mod-tab" data-tab="forwarded">
                    Forwarded
                    <span class="mod-tab-count" id="forwardedCount" style="display:none;">0</span>
                </button>
                <div class="mod-tab-indicator"></div>
            </div>

            <div class="mod-panels">
                <div class="mod-tab-panel active" data-panel="pending">
                    <div class="mod-report-list" id="pendingList"></div>
                    <div class="mod-empty" id="pendingEmpty" style="display:none;">
                        <div class="mod-empty-icon">✅</div>
                        <p>No pending reports</p>
                        <span>The queue is clear. Nice work!</span>
                    </div>
                </div>
                <div class="mod-tab-panel" data-panel="resolved">
                    <div class="mod-report-list" id="resolvedList"></div>
                    <div class="mod-empty" id="resolvedEmpty" style="display:none;">
                        <div class="mod-empty-icon">📋</div>
                        <p>No resolved reports</p>
                        <span>Reports you resolve will appear here.</span>
                    </div>
                </div>
                <div class="mod-tab-panel" data-panel="forwarded">
                    <div class="mod-report-list" id="forwardedList"></div>
                    <div class="mod-empty" id="forwardedEmpty" style="display:none;">
                        <div class="mod-empty-icon">📨</div>
                        <p>No forwarded reports</p>
                        <span>Reports you escalate to admin will appear here.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Card Template -->
        <template id="reportCardTemplate">
            <div class="mod-report-card" data-report-id="">
                <div class="mod-report-top">
                    <div class="mod-report-meta">
                        <span class="mod-report-type-badge"></span>
                        <span class="mod-report-time"></span>
                    </div>
                </div>
                <div class="mod-report-body">
                    <p class="mod-report-reason"></p>
                    <p class="mod-report-text"></p>
                </div>
                <div class="mod-report-footer">
                    <div class="mod-reporter">
                        <img class="mod-reporter-avatar" src="/unihelper/views/assets/default-pfp.png" alt="">
                        <span class="mod-reporter-name"></span>
                    </div>
                    <div class="mod-report-actions"></div>
                </div>
            </div>
        </template>

    <?php endif; ?>
</div>