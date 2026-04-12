<?php
$composerHeading = $composerHeading ?? 'Community Feed';
$composerSubheading = $composerSubheading ?? 'Create announcements, events, and updates for selected audiences.';
$defaultPostType = $defaultPostType ?? 'announcement';
$composerActionLabel = $composerActionLabel
    ?? ($defaultPostType === 'event' ? 'Publish Event' : ($defaultPostType === 'general' ? 'Create Post' : 'Create Announcement'));
$currentRole = isset($user->role) ? (string)$user->role : '';
?>
<link rel="stylesheet" href="/unihelper/views/css/components/announcement.css">

<section class="feed-shell" data-user-role="<?= htmlspecialchars($currentRole) ?>" data-default-post-type="<?= htmlspecialchars($defaultPostType) ?>">
    <div class="feed-launcher">
        <button type="button" id="feedOpenComposerBtn" class="feed-open-btn" aria-expanded="false" aria-controls="feedComposer">
            <span class="feed-open-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </span>
            <span class="feed-open-label"><?= htmlspecialchars($composerActionLabel) ?></span>
        </button>
    </div>

    <div id="feedComposer" class="feed-composer is-hidden" hidden>
        <div class="feed-composer-header">
            <div>
                <h2 class="feed-title"><?= htmlspecialchars($composerHeading) ?></h2>
                <p class="feed-subtitle"><?= htmlspecialchars($composerSubheading) ?></p>
            </div>
            <button type="button" id="feedCloseComposerBtn" class="feed-composer-close" aria-label="Close form">×</button>
        </div>

        <form id="feedComposerForm" class="feed-form" autocomplete="off">
            <div class="feed-field">
                <label for="feedTitle">Title</label>
                <input id="feedTitle" name="title" class="feed-input" type="text" maxlength="255" placeholder="Write a short, clear title" required>
            </div>

            <div class="feed-field">
                <label for="feedBody">Message</label>
                <textarea id="feedBody" name="body" class="feed-textarea" placeholder="Share details, links, and context..." required></textarea>
            </div>

            <div class="feed-grid">
                <div class="feed-field">
                    <label for="feedType">Post Type</label>
                    <select id="feedType" name="post_type" class="feed-select">
                        <option value="announcement" <?= $defaultPostType === 'announcement' ? 'selected' : '' ?>>Announcement</option>
                        <option value="event" <?= $defaultPostType === 'event' ? 'selected' : '' ?>>Event</option>
                        <option value="general" <?= $defaultPostType === 'general' ? 'selected' : '' ?>>General</option>
                    </select>
                </div>

                <div class="feed-field">
                    <label for="feedAudienceMode">Audience</label>
                    <select id="feedAudienceMode" name="audience_mode" class="feed-select">
                        <option value="all_roles">All roles</option>
                        <option value="selected_roles">Selected roles</option>
                    </select>
                </div>
            </div>

            <div id="feedRoleChoices" class="feed-role-choices" aria-live="polite">
                <label class="feed-role-chip"><input type="checkbox" name="audience_roles[]" value="role-applicant"> Applicant</label>
                <label class="feed-role-chip"><input type="checkbox" name="audience_roles[]" value="role-undergrad"> Undergraduate</label>
                <label class="feed-role-chip"><input type="checkbox" name="audience_roles[]" value="role-profile"> Profile</label>
                <label class="feed-role-chip"><input type="checkbox" name="audience_roles[]" value="role-admin"> Admin</label>
            </div>

            <div class="feed-actions">
                <p class="feed-help">Session posts are automatically included here and follow session audience rules.</p>
                <div class="feed-action-buttons">
                    <button type="button" class="feed-btn-secondary" id="feedCancelComposerBtn">Cancel</button>
                    <button type="submit" class="feed-btn" id="feedPublishBtn">Publish to Feed</button>
                </div>
            </div>
        </form>
    </div>

    <div id="feedList" class="feed-list"></div>
    <button id="feedLoadMore" class="feed-load-more" type="button" style="display:none;">Load more</button>
</section>
<script src="/unihelper/views/js/announcement.js"></script>
