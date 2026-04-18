<link rel="stylesheet" href="/unihelper/views/css/components/peer-learning.css">

<div class="peer-learning-container">
    <?php if (!isset($user) || $user->role !== 'role-applicant'): ?>
        <div class="peer-learning-toolbar">
            <a href="/UniHelper/create-session" class="peer-create-session-btn" title="Create Session"
                aria-label="Create Session" data-action="open-create-session-modal" data-session-id="0">
                <span class="peer-create-session-label">Create New Session</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14"></path>
                    <path d="M5 12h14"></path>
                </svg>
            </a>
        </div>
    <?php endif; ?>

    <div class="peer-session-search">
        <form id="peer-session-search-form" class="peer-session-search-form" role="search">
            <input
                id="peer-session-search-input"
                class="peer-session-search-input"
                type="text"
                placeholder="Search sessions by title, subject, description, tags, or creator"
                autocomplete="off"
                aria-label="Search sessions"
            />
            <button type="submit" class="peer-session-search-btn">Search</button>
            <button type="button" id="peer-session-search-clear" class="peer-session-search-clear-btn">Clear</button>
        </form>
    </div>

    <!-- Tabs -->
    <div class="peer-tabs">
        <button class="peer-tab active" data-tab="my-sessions">My Sessions</button>
        <button class="peer-tab" data-tab="all-sessions">All Sessions</button>
        <button class="peer-tab" data-tab="subscribed-sessions">Subscribed Sessions</button>
    </div>

    <!-- My Sessions Tab -->
    <div class="peer-content active" id="my-sessions">
        <div id="my-sessions-container" class="sessions-grid"></div>
        <button class="load-more-btn" id="my-sessions-load-more" style="display: none;">Load More Sessions</button>
        <div class="loading-spinner" id="my-sessions-loading" style="display: none;">
            <div class="spinner"></div>
        </div>
    </div>

    <!-- All Sessions Tab -->
    <div class="peer-content" id="all-sessions">
        <div id="all-sessions-container" class="sessions-grid"></div>
        <button class="load-more-btn" id="all-sessions-load-more" style="display: none;">Load More Sessions</button>
        <div class="loading-spinner" id="all-sessions-loading" style="display: none;">
            <div class="spinner"></div>
        </div>
    </div>

    <!-- Subscribed Sessions Tab -->
    <div class="peer-content" id="subscribed-sessions">
        <div id="subscribed-sessions-container" class="sessions-grid"></div>
        <button class="load-more-btn" id="subscribed-sessions-load-more" style="display: none;">Load More Sessions</button>
        <div class="loading-spinner" id="subscribed-sessions-loading" style="display: none;">
            <div class="spinner"></div>
        </div>
    </div>
</div>

<div class="session-main-modal" id="sessionMainModal" aria-hidden="true">
    <div class="session-main-modal-content" role="dialog" aria-modal="true" aria-labelledby="sessionMainModalTitle">
        <div class="session-main-modal-header">
            <h3 class="session-main-modal-title" id="sessionMainModalTitle">Session Details</h3>
            <button type="button" class="session-main-modal-close" data-modal-action="close-session-modal"
                aria-label="Close session view">&times;</button>
        </div>
        <div class="session-main-modal-body" id="sessionMainModalBody"></div>
    </div>
</div>

<div class="session-main-modal create-session-modal" id="createSessionModal" aria-hidden="true">
    <div class="session-main-modal-content" role="dialog" aria-modal="true" aria-labelledby="createSessionModalTitle">
        <div class="session-main-modal-header">
            <h3 class="session-main-modal-title" id="createSessionModalTitle">Create Study Session</h3>
            <button type="button" class="session-main-modal-close" data-action="close-create-session-modal"
                aria-label="Close create session form">&times;</button>
        </div>
        <div class="session-main-modal-body" id="createSessionModalBody"></div>
    </div>
</div>

<script>
    const BASE_URL = '/UniHelper';
    const CURRENT_USER_ID = <?= (int)($user->id ?? 0) ?>;

    let currentTab = 'my-sessions';
    let mySessionsPage = 1;
    let allSessionsPage = 1;
    let subscribedSessionsPage = 1;
    let activeSessionId = null;
    let sessionForSubscribersId = null;
    let activeCreateSessionId = 0;
    let createSessionSubmitInFlight = false;
    let deleteSubmitInFlight = false;

    const sessionSearchState = {
        'my-sessions': { query: '', page: 1 },
        'all-sessions': { query: '', page: 1 },
        'subscribed-sessions': { query: '', page: 1 }
    };

    const sessionCache = new Map();
    const pageParams = getPageParams();
    const sessionMainModalElement = document.getElementById('sessionMainModal');
    const createSessionModalElement = document.getElementById('createSessionModal');
    const createSessionModalTitleElement = document.getElementById('createSessionModalTitle');
    const createSessionModalBodyElement = document.getElementById('createSessionModalBody');
    const sessionSearchForm = document.getElementById('peer-session-search-form');
    const sessionSearchInput = document.getElementById('peer-session-search-input');
    const sessionSearchClearButton = document.getElementById('peer-session-search-clear');

    if (typeof window._peerLearningDeepLinkHandled === 'undefined') {
        window._peerLearningDeepLinkHandled = false;
    }

    if (sessionMainModalElement && sessionMainModalElement.parentElement !== document.body) {
        document.body.appendChild(sessionMainModalElement);
    }

    if (createSessionModalElement && createSessionModalElement.parentElement !== document.body) {
        document.body.appendChild(createSessionModalElement);
    }

    function updateBodyModalState() {
        const hasVisibleModal = document.querySelector('.session-main-modal.show') !== null;
        document.body.classList.toggle('peer-modal-open', hasVisibleModal);
    }

    function requestConfirmation(message) {
        if (typeof window.confirm !== 'function') {
            return Promise.resolve(false);
        }

        try {
            const result = window.confirm(message);
            if (result && typeof result.then === 'function') {
                return result.then(Boolean).catch(() => false);
            }
            return Promise.resolve(Boolean(result));
        } catch (error) {
            console.error('Confirmation dialog failed:', error);
            return Promise.resolve(false);
        }
    }

    function getPageParams() {
        const main = document.getElementById('dashboardMain');
        if (!main) {
            return {};
        }

        try {
            return JSON.parse(main.dataset.pageParams || '{}') || {};
        } catch (error) {
            console.warn('Invalid page params for peer-learning:', error);
            return {};
        }
    }

    function normalizeTabName(rawTab) {
        if (rawTab === 'all-sessions' || rawTab === 'subscribed-sessions') {
            return rawTab;
        }
        return 'my-sessions';
    }

    function normalizePositiveInt(rawValue) {
        const parsed = Number(rawValue);
        return Number.isInteger(parsed) && parsed > 0 ? parsed : 0;
    }

    function replaceBrowserUrl(url) {
        if (!window.history || typeof window.history.replaceState !== 'function') {
            return;
        }

        window.history.replaceState({}, '', url);
    }

    function buildSessionDeepLinkUrl(sessionId, tabName) {
        const normalizedSessionId = normalizePositiveInt(sessionId);
        if (!normalizedSessionId) {
            return `${BASE_URL}/peer-learning`;
        }

        const normalizedTab = normalizeTabName(tabName);
        return `${BASE_URL}/peer-learning?session_id=${encodeURIComponent(String(normalizedSessionId))}&tab=${encodeURIComponent(normalizedTab)}`;
    }

    function syncUrlToActiveSession(sessionId, tabName) {
        replaceBrowserUrl(buildSessionDeepLinkUrl(sessionId, tabName));
    }

    function resetPeerLearningUrl() {
        replaceBrowserUrl(`${BASE_URL}/peer-learning`);
    }

    function showPeerLearningError(message) {
        const safeMessage = message || 'Something went wrong. Please try again.';
        if (typeof window.showToast === 'function') {
            window.showToast(safeMessage, 'error');
            return;
        }

        console.error(safeMessage);
    }

    function normalizeSearchQuery(value) {
        return String(value || '').trim();
    }

    function getTabSearchState(tabName) {
        const normalizedTab = normalizeTabName(tabName);
        return sessionSearchState[normalizedTab] || sessionSearchState['my-sessions'];
    }

    function isSearchMode(tabName) {
        if (normalizeTabName(tabName) === 'subscribed-sessions') {
            return false;
        }
        return getTabSearchState(tabName).query.length >= 2;
    }

    function getTabElements(tabName) {
        if (tabName === 'all-sessions') {
            return {
                container: document.getElementById('all-sessions-container'),
                loading: document.getElementById('all-sessions-loading'),
                loadMoreBtn: document.getElementById('all-sessions-load-more')
            };
        }

        if (tabName === 'subscribed-sessions') {
            return {
                container: document.getElementById('subscribed-sessions-container'),
                loading: document.getElementById('subscribed-sessions-loading'),
                loadMoreBtn: document.getElementById('subscribed-sessions-load-more')
            };
        }

        return {
            container: document.getElementById('my-sessions-container'),
            loading: document.getElementById('my-sessions-loading'),
            loadMoreBtn: document.getElementById('my-sessions-load-more')
        };
    }

    function updateSearchControls() {
        if (!sessionSearchInput || !sessionSearchClearButton) {
            return;
        }

        sessionSearchClearButton.style.display = sessionSearchInput.value.trim() !== '' ? 'inline-flex' : 'none';
    }

    function syncSearchUiToCurrentTab() {
        if (!sessionSearchInput) {
            return;
        }

        sessionSearchInput.value = getTabSearchState(currentTab).query;
        updateSearchControls();
    }

    function createSearchEmptyState(tabName, query) {
        const normalizedQuery = normalizeSearchQuery(query);

        if (tabName === 'all-sessions') {
            return createEmptyState(
                'No matching sessions',
                `No sessions in All Sessions matched "${normalizedQuery}".`
            );
        }

        if (tabName === 'subscribed-sessions') {
            return createEmptyState(
                'No matching sessions',
                `No sessions in Subscribed Sessions matched "${normalizedQuery}".`
            );
        }

        return createEmptyState(
            'No matching sessions',
            `No sessions in My Sessions matched "${normalizedQuery}".`
        );
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escapeAttribute(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function normalizeSubscriptionStatus(rawStatus) {
        const status = String(rawStatus || '').toLowerCase();
        if (status === 'approved' || status === 'pending') {
            return status;
        }
        return 'none';
    }

    function normalizeSubscriberDecisionStatus(rawStatus) {
        const status = String(rawStatus || '').toLowerCase();
        if (status === 'approved' || status === 'pending' || status === 'rejected') {
            return status;
        }
        return 'pending';
    }

    function getAudienceLabel(audience) {
        if (audience === 'my_university') {
            return 'My University';
        }
        if (audience === 'private') {
            return 'Private';
        }
        return 'All Universities';
    }

    function getSubscribeButtonLabel(status) {
        if (status === 'pending') {
            return 'Pending';
        }
        if (status === 'approved') {
            return 'Subscribed';
        }
        return 'Subscribe';
    }

    function formatDecisionStatus(status) {
        if (status === 'approved') {
            return 'Approved';
        }
        if (status === 'rejected') {
            return 'Rejected';
        }
        return 'Pending';
    }

    function getAuthorFullName(session) {
        const firstName = String(session.creator_first_name || '').trim();
        const lastName = String(session.creator_last_name || '').trim();
        const fullName = `${firstName} ${lastName}`.trim();
        return fullName || 'Unknown User';
    }

    function getProfileImageUrl(profilePicturePath) {
        const rawPath = String(profilePicturePath || '').trim();
        if (!rawPath) {
            return `${BASE_URL}/views/assets/default-pfp.png`;
        }

        if (/^https?:\/\//i.test(rawPath)) {
            return rawPath;
        }

        if (rawPath.startsWith('/')) {
            return `${BASE_URL}${rawPath}`;
        }

        return `${BASE_URL}/${rawPath}`;
    }

    function formatSubscriberCount(count) {
        const safeCount = Math.max(0, Number(count || 0));
        return `${safeCount} subscriber${safeCount === 1 ? '' : 's'}`;
    }

    function formatDate(dateStr) {
        if (!dateStr) {
            return '-';
        }

        const date = new Date(`${dateStr}T00:00:00`);
        if (Number.isNaN(date.getTime())) {
            return '-';
        }

        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function parseSessionTags(rawTags) {
        const source = String(rawTags || '').trim();
        if (!source) {
            return [];
        }

        return source
            .split(/[#,]+/)
            .map(tag => tag.trim().replace(/^#+/, ''))
            .filter(Boolean);
    }

    function normalizeSessionDurationHours(rawDuration) {
        const parsed = Number(rawDuration);
        if (!Number.isFinite(parsed) || parsed <= 0) {
            return 1;
        }

        return parsed;
    }

    function getSessionStartDateTime(session) {
        const datePart = String(session && session.date ? session.date : '').trim();
        const rawTimePart = String(session && session.time ? session.time : '00:00:00').trim();
        if (!datePart) {
            return null;
        }

        const hasSimpleTimeFormat = /^\d{2}:\d{2}(:\d{2})?$/.test(rawTimePart);
        const normalizedTime = hasSimpleTimeFormat
            ? (rawTimePart.length === 5 ? `${rawTimePart}:00` : rawTimePart)
            : '00:00:00';

        const startDateTime = new Date(`${datePart}T${normalizedTime}`);
        if (Number.isNaN(startDateTime.getTime())) {
            return null;
        }

        return startDateTime;
    }

    function isSessionOngoing(session) {
        if (!session || Number(session.is_deleted || 0) === 1 || session.deleted_at) {
            return false;
        }

        const startDateTime = getSessionStartDateTime(session);
        if (!startDateTime) {
            return false;
        }

        const durationHours = normalizeSessionDurationHours(session.duration);
        const endDateTime = new Date(startDateTime.getTime() + Math.round(durationHours * 3600 * 1000));
        const now = new Date();

        return now > startDateTime && now < endDateTime;
    }

    function buildSessionTagMarkup(rawTags) {
        return parseSessionTags(rawTags)
            .map(tag => `<span class="session-tag">${escapeHtml(tag)}</span>`)
            .join('');
    }

    function createEmptyState(title, text) {
        return `
            <div class="empty-state">
                <div class="empty-state-icon">📚</div>
                <h3 class="empty-state-title">${escapeHtml(title)}</h3>
                <p class="empty-state-text">${escapeHtml(text)}</p>
                <a href="${BASE_URL}/create-session" class="empty-state-btn" data-action="open-create-session-modal" data-session-id="0">Create Session</a>
            </div>
        `;
    }

    function isOwnerSession(session) {
        return Number(session.user_id || 0) === Number(CURRENT_USER_ID || 0);
    }

    function canCurrentUserJoin(session) {
        if (!session || !session.session_link) {
            return false;
        }
        return Number(session.can_join || 0) === 1 || isOwnerSession(session);
    }

    function upsertSessionCache(session) {
        const sessionId = Number(session && session.id ? session.id : 0);
        if (!sessionId) {
            return null;
        }

        const previous = sessionCache.get(sessionId) || {};
        const merged = { ...previous, ...session, id: sessionId };
        sessionCache.set(sessionId, merged);
        return merged;
    }

    function getSessionFromCache(sessionId) {
        const normalizedSessionId = Number(sessionId || 0);
        if (!normalizedSessionId) {
            return null;
        }
        return sessionCache.get(normalizedSessionId) || null;
    }

    function updateSubscribeButtonState(button, status) {
        const normalizedStatus = normalizeSubscriptionStatus(status);
        const subscribed = normalizedStatus !== 'none';

        button.setAttribute('data-status', normalizedStatus);
        button.setAttribute('data-subscribed', subscribed ? '1' : '0');
        button.textContent = getSubscribeButtonLabel(normalizedStatus);

        button.classList.remove('subscribed', 'pending');
        if (normalizedStatus === 'approved') {
            button.classList.add('subscribed');
        }
        if (normalizedStatus === 'pending') {
            button.classList.add('pending');
        }
    }

    function updateSubscriberCountForSession(sessionId, count) {
        if (typeof count === 'undefined' || count === null) {
            return;
        }

        const normalizedSessionId = Number(sessionId || 0);
        const safeCount = Math.max(0, Number(count || 0));
        document.querySelectorAll(`.subscriber-count[data-session-id="${normalizedSessionId}"]`).forEach(element => {
            element.textContent = formatSubscriberCount(safeCount);
        });

        const cachedSession = getSessionFromCache(normalizedSessionId);
        if (cachedSession) {
            cachedSession.sub_count = safeCount;
            upsertSessionCache(cachedSession);
        }
    }

    function createSessionCard(session) {
        const safeSession = upsertSessionCache(session) || session;
        const isExpired = Number(safeSession.is_expired || 0) === 1 || (safeSession.deleted_at && !safeSession.is_deleted);
        const isOngoing = !isExpired && isSessionOngoing(safeSession);
        const audienceLabel = getAudienceLabel(safeSession.audience);
        const tags = buildSessionTagMarkup(safeSession.tags);
        const subscriberCount = Math.max(0, Number(safeSession.sub_count || 0));
        const authorName = getAuthorFullName(safeSession);
        const authorNameHtml = escapeHtml(authorName);
        const authorId = Number(safeSession.user_id || 0);
        const authorLink = authorId ? `${BASE_URL}/view/profile/${authorId}` : '';
        const authorLinkHtml = authorLink
            ? `<a class="session-creator-link" href="${authorLink}" target="_blank" rel="noopener">${authorNameHtml}</a>`
            : `<span class="session-creator-name">${authorNameHtml}</span>`;
        const authorUniversity = escapeHtml(safeSession.creator_university || safeSession.university || 'Unknown University');
        const authorAvatarUrl = escapeAttribute(getProfileImageUrl(safeSession.creator_profile_picture));
        const expiredBadge = isExpired ? '<span class="session-expired-badge">Expired</span>' : '';
        const ongoingBadge = isOngoing ? '<span class="session-ongoing-badge">Ongoing</span>' : '';
        const sessionId = Number(safeSession.id || 0);

        return `
            <div class="session-card ${isExpired ? 'expired' : ''}" data-session-id="${sessionId}" role="button" tabindex="0"
                 aria-label="Open session details">
                <div class="session-header">
                    <div>
                        <span class="session-audience">${escapeHtml(audienceLabel)}</span>
                        <div style="margin-top: 0.5rem;">
                            <span class="session-subject">${escapeHtml(safeSession.subject || 'General')}</span>
                        </div>
                    </div>
                    <div class="session-status-badges">${ongoingBadge}${expiredBadge}</div>
                </div>
                <h3 class="session-title">${escapeHtml(safeSession.title || 'Untitled Session')}</h3>
                <p class="session-description">${escapeHtml(safeSession.description || 'No description available.')}</p>
                <div class="session-meta">
                    <div class="session-meta-item">
                        <span class="session-meta-label">Date:</span>
                        <span class="session-meta-value">${formatDate(safeSession.date)}</span>
                    </div>
                    <div class="session-meta-item">
                        <span class="session-meta-label">Time:</span>
                        <span class="session-meta-value">${escapeHtml(safeSession.time || '-')} <span class="session-duration">[${escapeHtml(String(safeSession.duration || '-'))}h]</span></span>
                    </div>
                </div>
                ${tags ? `<div class="session-tags">${tags}</div>` : ''}
                <div class="session-creator">
                    <img class="session-creator-avatar" src="${authorAvatarUrl}" alt="${escapeAttribute(authorName)}" loading="lazy" />
                    <div class="session-creator-details">
                        ${authorLinkHtml}
                        <span class="session-creator-university">${authorUniversity}</span>
                    </div>
                </div>
                <span class="subscriber-count" data-session-id="${sessionId}">${formatSubscriberCount(subscriberCount)}</span>
                <div class="session-open-hint">Click to open full session view</div>
            </div>
        `;
    }

    function renderMainSessionModal(session) {
        const sessionId = Number(session.id || 0);
        if (!sessionId) {
            return;
        }

        const isOwner = isOwnerSession(session);
        const showPrivateSubscriberList = isOwner && String(session.audience || '') === 'private';
        const canJoin = canCurrentUserJoin(session);
        const audienceLabel = getAudienceLabel(session.audience);
        const authorName = getAuthorFullName(session);
        const authorId = Number(session.user_id || 0);
        const authorLink = authorId ? `${BASE_URL}/view/profile/${authorId}` : '';
        const authorLinkHtml = authorLink
            ? `<a class="session-creator-link" href="${authorLink}" target="_blank" rel="noopener">${escapeHtml(authorName)}</a>`
            : `<span class="session-creator-name">${escapeHtml(authorName)}</span>`;
        const authorUniversity = escapeHtml(session.creator_university || session.university || 'Unknown University');
        const authorAvatarUrl = escapeAttribute(getProfileImageUrl(session.creator_profile_picture));
        const subscriberCount = Math.max(0, Number(session.sub_count || 0));
        const subscriptionStatus = normalizeSubscriptionStatus(session.subscription_status || (Number(session.is_subscribed) === 1 ? 'approved' : 'none'));
        const subscribeBtnClass = subscriptionStatus === 'approved'
            ? 'session-subscribe-btn subscribed'
            : (subscriptionStatus === 'pending' ? 'session-subscribe-btn pending' : 'session-subscribe-btn');
        const tags = buildSessionTagMarkup(session.tags);
        const isExpired = Number(session.is_expired || 0) === 1 || (session.deleted_at && !session.is_deleted);

        const actionButtons = [];
        if (isOwner) {
            actionButtons.push(`
                <button type="button" class="session-action-btn session-edit-btn" data-modal-action="edit-session"
                        data-session-id="${sessionId}">Edit</button>
            `);
            actionButtons.push(`
                <button type="button" class="session-action-btn session-delete-btn" data-modal-action="delete-session"
                        data-session-id="${sessionId}">Delete</button>
            `);
            if (showPrivateSubscriberList) {
                actionButtons.push(`
                    <button type="button" class="session-action-btn session-subscribe-list-btn"
                            data-modal-action="refresh-subscriber-list" data-session-id="${sessionId}">Subscribe List</button>
                `);
            }
        }

        if (canJoin) {
            actionButtons.push(`
                <a href="${escapeAttribute(session.session_link)}" target="_blank" rel="noopener"
                   class="session-action-btn session-join-btn session-main-action-link">Join Session</a>
            `);
        }

        if (!isOwner) {
            actionButtons.push(`
                <button
                    type="button"
                    class="session-action-btn ${subscribeBtnClass}"
                    data-session-id="${sessionId}"
                    data-status="${subscriptionStatus}"
                    data-subscribed="${subscriptionStatus !== 'none' ? 1 : 0}"
                >${getSubscribeButtonLabel(subscriptionStatus)}</button>
            `);
        }

        document.getElementById('sessionMainModalTitle').textContent = session.title || 'Session Details';
        document.getElementById('sessionMainModalBody').innerHTML = `
            <div class="session-main-details">
                <div class="session-header">
                    <div>
                        <span class="session-audience">${escapeHtml(audienceLabel)}</span>
                        <div style="margin-top: 0.5rem;">
                            <span class="session-subject">${escapeHtml(session.subject || 'General')}</span>
                        </div>
                    </div>
                    <div class="session-status-badges">
                        ${isExpired ? '<span class="session-expired-badge">Expired</span>' : ''}
                    </div>
                </div>

                <h3 class="session-title">${escapeHtml(session.title || 'Untitled Session')}</h3>
                <p class="session-description" style="max-height:none;-webkit-line-clamp:unset;">${escapeHtml(session.description || 'No description available.')}</p>

                <div class="session-meta">
                    <div class="session-meta-item">
                        <span class="session-meta-label">Date:</span>
                        <span class="session-meta-value">${formatDate(session.date)}</span>
                    </div>
                    <div class="session-meta-item">
                        <span class="session-meta-label">Time:</span>
                        <span class="session-meta-value">${escapeHtml(session.time || '-')} <span class="session-duration">[${escapeHtml(String(session.duration || '-'))}h]</span></span>
                    </div>
                </div>

                ${tags ? `<div class="session-tags">${tags}</div>` : ''}

                <div class="session-creator">
                    <img class="session-creator-avatar" src="${authorAvatarUrl}" alt="${escapeAttribute(authorName)}" loading="lazy" />
                    <div class="session-creator-details">
                        ${authorLinkHtml}
                        <span class="session-creator-university">${authorUniversity}</span>
                    </div>
                </div>

                <span class="subscriber-count" data-session-id="${sessionId}">${formatSubscriberCount(subscriberCount)}</span>

                ${actionButtons.length > 0 ? `<div class="session-main-actions">${actionButtons.join('')}</div>` : ''}

                ${showPrivateSubscriberList ? `
                    <hr class="session-main-divider" />
                    <h4 class="session-main-subscriber-section-title">Subscribe List</h4>
                    <p class="session-main-subscriber-summary">Manage pending and existing private-session subscribers.</p>
                    <div id="sessionMainSubscriberBody"></div>
                ` : ''}
            </div>
        `;

        if (showPrivateSubscriberList) {
            renderSubscriberListLoading();
        }
    }

    function openSessionModal(session) {
        const safeSession = upsertSessionCache(session) || session;
        const sessionId = Number(safeSession.id || 0);
        if (!sessionId) {
            showPeerLearningError('Invalid session ID.');
            return;
        }

        activeSessionId = sessionId;
        sessionForSubscribersId = null;
        renderMainSessionModal(safeSession);

        const modal = document.getElementById('sessionMainModal');
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        updateBodyModalState();
        syncUrlToActiveSession(sessionId, currentTab);

        if (isOwnerSession(safeSession) && String(safeSession.audience || '') === 'private') {
            sessionForSubscribersId = sessionId;
            fetchSubscriberList(sessionId);
        }
    }

    function closeSessionModal() {
        const modal = document.getElementById('sessionMainModal');
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        activeSessionId = null;
        sessionForSubscribersId = null;
        updateBodyModalState();
        resetPeerLearningUrl();
    }

    function showCreateSessionModalLoading() {
        if (!createSessionModalBodyElement) {
            return;
        }

        createSessionModalBodyElement.innerHTML = `
            <div class="loading-spinner" style="padding: 1.8rem 0;">
                <div class="spinner"></div>
            </div>
        `;
    }

    function renderCreateSessionModalError(message) {
        if (!createSessionModalBodyElement) {
            return;
        }

        createSessionModalBodyElement.innerHTML = `
            <div class="create-session-modal-error">${escapeHtml(message || 'Failed to load the session form.')}</div>
        `;
    }

    function openCreateSessionModal(sessionId = 0) {
        const normalizedSessionId = Math.max(0, Number(sessionId || 0));
        activeCreateSessionId = normalizedSessionId;

        if (!createSessionModalElement || !createSessionModalBodyElement) {
            return;
        }

        showCreateSessionModalLoading();
        if (createSessionModalTitleElement) {
            createSessionModalTitleElement.textContent = normalizedSessionId > 0 ? 'Edit Study Session' : 'Create Study Session';
        }
        createSessionModalElement.classList.add('show');
        createSessionModalElement.setAttribute('aria-hidden', 'false');
        updateBodyModalState();

        const query = normalizedSessionId > 0 ? `&session_id=${normalizedSessionId}` : '';
        fetch(`${BASE_URL}/api?controller=SessionController&action=getSessionFormModal${query}`, {
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(result => {
                if (!result.success || !result.data || !result.data.html) {
                    throw new Error(result.message || result.error || 'Failed to load the session form.');
                }

                if (createSessionModalTitleElement) {
                    createSessionModalTitleElement.textContent = result.data.title || (normalizedSessionId > 0 ? 'Edit Study Session' : 'Create Study Session');
                }
                createSessionModalBodyElement.innerHTML = result.data.html;
            })
            .catch(error => {
                console.error('Create-session modal load error:', error);
                if (typeof window.showToast === 'function') {
                    window.showToast(error.message || 'Failed to load the session form.', 'error');
                }
                renderCreateSessionModalError(error.message || 'Failed to load the session form.');
            });
    }

    function closeCreateSessionModal() {
        if (!createSessionModalElement) {
            return;
        }

        createSessionModalElement.classList.remove('show');
        createSessionModalElement.setAttribute('aria-hidden', 'true');
        if (createSessionModalBodyElement) {
            createSessionModalBodyElement.innerHTML = '';
        }

        createSessionSubmitInFlight = false;
        activeCreateSessionId = 0;
        updateBodyModalState();
    }

    function submitCreateSessionModalForm(formElement) {
        if (!formElement || createSessionSubmitInFlight) {
            return;
        }

        createSessionSubmitInFlight = true;
        const submitButton = formElement.querySelector('.btn-create');
        const previousSubmitText = submitButton ? submitButton.textContent : '';

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';
        }

        const formData = new FormData(formElement);

        fetch(`${BASE_URL}/api?controller=SessionController&action=submitSessionModal`, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(response => response.json())
            .then(result => {
                if (!result.success) {
                    if (result.validation && result.data && result.data.html && createSessionModalBodyElement) {
                        createSessionModalBodyElement.innerHTML = result.data.html;
                        return;
                    }

                    if (result.data && result.data.html && createSessionModalBodyElement) {
                        createSessionModalBodyElement.innerHTML = result.data.html;
                    }

                    throw new Error(result.message || result.error || 'Failed to save session.');
                }

                const payload = result.data || {};
                const returnedSession = payload.session ? upsertSessionCache(payload.session) : null;
                const updatedSessionId = Number(payload.session_id || (returnedSession && returnedSession.id) || 0);
                const successMessage = result.message || (payload.operation === 'update'
                    ? 'Session updated successfully.'
                    : 'Session created successfully.');

                closeCreateSessionModal();
                loadTabData('my-sessions', 1);

                if (typeof window.showToast === 'function') {
                    window.showToast(successMessage, 'success');
                }

                const allSessionsContainer = document.getElementById('all-sessions-container');
                if (allSessionsContainer && allSessionsContainer.innerHTML) {
                    loadTabData('all-sessions', 1);
                }

                if (updatedSessionId && activeSessionId === updatedSessionId && sessionMainModalElement && sessionMainModalElement.classList.contains('show')) {
                    if (returnedSession) {
                        renderMainSessionModal(returnedSession);
                        if (isOwnerSession(returnedSession) && String(returnedSession.audience || '') === 'private') {
                            sessionForSubscribersId = updatedSessionId;
                            fetchSubscriberList(updatedSessionId);
                        }
                    } else {
                        fetchSessionForView(updatedSessionId)
                            .then(session => {
                                renderMainSessionModal(session);
                                if (isOwnerSession(session) && String(session.audience || '') === 'private') {
                                    sessionForSubscribersId = updatedSessionId;
                                    fetchSubscriberList(updatedSessionId);
                                }
                            })
                            .catch(error => {
                                console.error('Failed to refresh updated session:', error);
                            });
                    }
                }
            })
            .catch(error => {
                console.error('Create-session modal submit error:', error);
                showPeerLearningError(error.message || 'Failed to save session. Please try again.');
            })
            .finally(() => {
                createSessionSubmitInFlight = false;

                if (!createSessionModalBodyElement) {
                    return;
                }

                const nextSubmitButton = createSessionModalBodyElement.querySelector('.btn-create');
                if (nextSubmitButton) {
                    nextSubmitButton.disabled = false;
                    nextSubmitButton.textContent = previousSubmitText || nextSubmitButton.textContent;
                }
            });
    }

    function fetchSessionForView(sessionId) {
        return fetch(`${BASE_URL}/api?controller=SessionController&action=getSessionForView&session_id=${sessionId}`, {
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(result => {
                if (!result.success || !result.data) {
                    throw new Error(result.message || result.error || 'Failed to load session details.');
                }
                return upsertSessionCache(result.data);
            });
    }

    function openSessionModalById(sessionId) {
        const normalizedId = normalizePositiveInt(sessionId);
        if (!normalizedId) {
            return Promise.reject(new Error('Invalid session ID.'));
        }

        const cached = getSessionFromCache(normalizedId);
        if (cached) {
            openSessionModal(cached);
            return Promise.resolve(cached);
        }

        return fetchSessionForView(normalizedId).then(session => {
            openSessionModal(session);
            return session;
        });
    }

    function switchTab(tabName) {
        const normalizedTab = normalizeTabName(tabName);

        document.querySelectorAll('.peer-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        const targetTabButton = document.querySelector(`[data-tab="${normalizedTab}"]`);
        if (targetTabButton) {
            targetTabButton.classList.add('active');
        }

        document.querySelectorAll('.peer-content').forEach(content => {
            content.classList.remove('active');
        });
        const targetTabContent = document.getElementById(normalizedTab);
        if (targetTabContent) {
            targetTabContent.classList.add('active');
        }

        currentTab = normalizedTab;
        syncSearchUiToCurrentTab();

        const targetElements = getTabElements(normalizedTab);
        const targetContainer = targetElements.container;
        const expectedMode = isSearchMode(normalizedTab) ? 'search' : 'default';

        if (!targetContainer || !targetContainer.innerHTML || targetContainer.dataset.mode !== expectedMode) {
            return loadTabData(normalizedTab, 1);
        }

        return Promise.resolve();
    }

    function loadMySessions(page) {
        const container = document.getElementById('my-sessions-container');
        const loading = document.getElementById('my-sessions-loading');
        const loadMoreBtn = document.getElementById('my-sessions-load-more');

        if (page === 1) {
            loading.style.display = 'flex';
        }

        return fetch(`${BASE_URL}/api?controller=SessionController&action=getMyessions&page=${page}`)
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';

                if (!data.success) {
                    throw new Error(data.error || 'Failed to load your sessions');
                }

                if (data.data && data.data.length > 0) {
                    if (page === 1) {
                        container.innerHTML = '';
                        container.dataset.mode = 'default';
                    }

                    data.data.forEach(session => {
                        upsertSessionCache(session);
                        container.insertAdjacentHTML('beforeend', createSessionCard(session));
                    });

                    loadMoreBtn.style.display = data.count >= 10 ? 'block' : 'none';
                    mySessionsPage = page + 1;
                } else if (page === 1) {
                    container.innerHTML = createEmptyState('No sessions yet', 'Create your first study session to get started!');
                    container.dataset.mode = 'default';
                    loadMoreBtn.style.display = 'none';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                console.error('Error loading sessions:', error);
                showPeerLearningError(error.message || 'Failed to load your sessions. Please try again.');
                if (page === 1) {
                    container.innerHTML = '<p style="color: #fc8181; text-align: center;">Failed to load sessions</p>';
                    container.dataset.mode = 'default';
                }
            });
    }

    function loadAllSessions(page) {
        const container = document.getElementById('all-sessions-container');
        const loading = document.getElementById('all-sessions-loading');
        const loadMoreBtn = document.getElementById('all-sessions-load-more');

        if (page === 1) {
            loading.style.display = 'flex';
        }

        return fetch(`${BASE_URL}/api?controller=SessionController&action=getAllSessions&page=${page}`)
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';

                if (!data.success) {
                    throw new Error(data.error || 'Failed to load sessions');
                }

                if (data.data && data.data.length > 0) {
                    if (page === 1) {
                        container.innerHTML = '';
                        container.dataset.mode = 'default';
                    }

                    data.data.forEach(session => {
                        upsertSessionCache(session);
                        container.insertAdjacentHTML('beforeend', createSessionCard(session));
                    });

                    loadMoreBtn.style.display = data.count >= 10 ? 'block' : 'none';
                    allSessionsPage = page + 1;
                } else if (page === 1) {
                    container.innerHTML = createEmptyState('No sessions available', 'Create a new session to get started!');
                    container.dataset.mode = 'default';
                    loadMoreBtn.style.display = 'none';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                console.error('Error loading sessions:', error);
                showPeerLearningError(error.message || 'Failed to load sessions. Please try again.');
                if (page === 1) {
                    container.innerHTML = '<p style="color: #fc8181; text-align: center;">Failed to load sessions</p>';
                    container.dataset.mode = 'default';
                }
            });
    }

    function loadSubscribedSessions(page) {
        const container = document.getElementById('subscribed-sessions-container');
        const loading = document.getElementById('subscribed-sessions-loading');
        const loadMoreBtn = document.getElementById('subscribed-sessions-load-more');

        if (page === 1) {
            loading.style.display = 'flex';
        }

        return fetch(`${BASE_URL}/api?controller=SessionController&action=getSubscribedSessions&page=${page}`)
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';

                if (!data.success) {
                    throw new Error(data.error || data.message || 'Failed to load subscribed sessions');
                }

                if (data.data && data.data.length > 0) {
                    if (page === 1) {
                        container.innerHTML = '';
                        container.dataset.mode = 'default';
                    }

                    data.data.forEach(session => {
                        upsertSessionCache(session);
                        container.insertAdjacentHTML('beforeend', createSessionCard(session));
                    });

                    const canLoadMore = typeof data.count !== 'undefined' ? Number(data.count) >= 10 : false;
                    loadMoreBtn.style.display = canLoadMore ? 'block' : 'none';
                    subscribedSessionsPage = page + 1;
                } else if (page === 1) {
                    container.innerHTML = createEmptyState('No subscribed sessions', 'Subscribe to sessions to see them here.');
                    container.dataset.mode = 'default';
                    loadMoreBtn.style.display = 'none';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                console.error('Error loading subscribed sessions:', error);
                showPeerLearningError(error.message || 'Failed to load subscribed sessions. Please try again.');
                if (page === 1) {
                    container.innerHTML = '<p style="color: #fc8181; text-align: center;">Failed to load subscribed sessions</p>';
                    container.dataset.mode = 'default';
                }
            });
    }

    function searchSessions(tabName, page) {
        const normalizedTab = normalizeTabName(tabName);
        if (normalizedTab === 'subscribed-sessions') {
            return Promise.resolve();
        }
        const searchState = getTabSearchState(normalizedTab);
        const query = normalizeSearchQuery(searchState.query);

        if (query.length < 2) {
            return Promise.resolve();
        }

        const { container, loading, loadMoreBtn } = getTabElements(normalizedTab);
        if (!container || !loading || !loadMoreBtn) {
            return Promise.resolve();
        }

        if (page === 1) {
            loading.style.display = 'flex';
        }

        const encodedQuery = encodeURIComponent(query);
        const encodedTab = encodeURIComponent(normalizedTab);

        return fetch(`${BASE_URL}/api?controller=SessionController&action=searchSessions&query=${encodedQuery}&tab=${encodedTab}&page=${page}`)
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';

                if (!data.success) {
                    throw new Error(data.error || 'Failed to search sessions');
                }

                if (data.data && data.data.length > 0) {
                    if (page === 1) {
                        container.innerHTML = '';
                        container.dataset.mode = 'search';
                    }

                    data.data.forEach(session => {
                        upsertSessionCache(session);
                        container.insertAdjacentHTML('beforeend', createSessionCard(session));
                    });

                    loadMoreBtn.style.display = data.count >= 10 ? 'block' : 'none';
                    searchState.page = page + 1;
                } else {
                    if (page === 1) {
                        container.innerHTML = createSearchEmptyState(normalizedTab, query);
                        container.dataset.mode = 'search';
                    }
                    loadMoreBtn.style.display = 'none';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                console.error('Error searching sessions:', error);
                showPeerLearningError(error.message || 'Failed to search sessions. Please try again.');
                if (page === 1) {
                    container.innerHTML = '<p style="color: #fc8181; text-align: center;">Failed to search sessions</p>';
                    container.dataset.mode = 'search';
                }
            });
    }

    function loadTabData(tabName, page) {
        if (isSearchMode(tabName)) {
            return searchSessions(tabName, page);
        }

        if (tabName === 'subscribed-sessions') {
            return loadSubscribedSessions(page);
        }

        if (tabName === 'all-sessions') {
            return loadAllSessions(page);
        }

        return loadMySessions(page);
    }

    function getNextPageForTab(tabName) {
        if (isSearchMode(tabName)) {
            return getTabSearchState(tabName).page;
        }

        if (tabName === 'all-sessions') {
            return allSessionsPage;
        }

        if (tabName === 'subscribed-sessions') {
            return subscribedSessionsPage;
        }

        return mySessionsPage;
    }

    function clearSearchForTab(tabName, reload = false) {
        const normalizedTab = normalizeTabName(tabName);
        const state = getTabSearchState(normalizedTab);
        state.query = '';
        state.page = 1;

        if (normalizedTab === currentTab) {
            syncSearchUiToCurrentTab();
        }

        if (!reload) {
            return Promise.resolve();
        }

        return loadTabData(normalizedTab, 1);
    }

    function submitSearchForCurrentTab() {
        if (!sessionSearchInput) {
            return Promise.resolve();
        }

        if (currentTab === 'subscribed-sessions') {
            showPeerLearningError('Search is available only for My Sessions and All Sessions.');
            return Promise.resolve();
        }

        const query = normalizeSearchQuery(sessionSearchInput.value);
        if (query === '') {
            return clearSearchForTab(currentTab, true);
        }

        if (query.length < 2) {
            showPeerLearningError('Please enter at least 2 characters to search.');
            return Promise.resolve();
        }

        const state = getTabSearchState(currentTab);
        state.query = query;
        state.page = 1;
        syncSearchUiToCurrentTab();

        return searchSessions(currentTab, 1);
    }

    function editSession(sessionId) {
        const normalizedSessionId = Number(sessionId || 0);
        if (!normalizedSessionId) {
            showPeerLearningError('Invalid session ID.');
            return;
        }

        openCreateSessionModal(normalizedSessionId);
    }

    function renderSubscriberListLoading() {
        const body = document.getElementById('sessionMainSubscriberBody');
        if (!body) {
            return;
        }

        body.innerHTML = `
            <div class="loading-spinner" style="padding: 1.5rem 0;">
                <div class="spinner"></div>
            </div>
        `;
    }

    function renderSubscriberList(list) {
        const body = document.getElementById('sessionMainSubscriberBody');
        if (!body) {
            return;
        }

        if (!Array.isArray(list) || list.length === 0) {
            body.innerHTML = '<div class="session-main-subscriber-empty">No subscribers yet.</div>';
            return;
        }

        const rows = list.map(item => {
            const status = normalizeSubscriberDecisionStatus(item.status);
            const fullName = `${item.first_name || ''} ${item.last_name || ''}`.trim() || 'Unknown User';
            const approveDisabled = status === 'approved' ? 'disabled' : '';
            const rejectDisabled = status === 'rejected' ? 'disabled' : '';
            const profileUrl = `${BASE_URL}/view/profile/${item.subscriber_id}`;

            return `
                <div class="subscriber-row" data-subscriber-id="${item.subscriber_id}">
                    <div class="subscriber-name"><a href="${profileUrl}" class="subscriber-name-link" target="_blank">${escapeHtml(fullName)}</a></div>
                    <div class="subscriber-row-actions">
                        <span class="subscriber-status ${status}">${formatDecisionStatus(status)}</span>
                        <button
                            type="button"
                            class="subscriber-decision-btn subscriber-approve-btn"
                            data-action="approve"
                            data-subscriber-id="${item.subscriber_id}"
                            ${approveDisabled}
                        >Approve</button>
                        <button
                            type="button"
                            class="subscriber-decision-btn subscriber-reject-btn"
                            data-action="reject"
                            data-subscriber-id="${item.subscriber_id}"
                            ${rejectDisabled}
                        >Reject</button>
                    </div>
                </div>
            `;
        }).join('');

        body.innerHTML = `<div class="subscriber-list">${rows}</div>`;
    }

    function applySubscriberRowState(row, status) {
        const normalizedStatus = normalizeSubscriberDecisionStatus(status);
        const statusBadge = row.querySelector('.subscriber-status');
        const approveButton = row.querySelector('.subscriber-approve-btn');
        const rejectButton = row.querySelector('.subscriber-reject-btn');

        if (statusBadge) {
            statusBadge.classList.remove('pending', 'approved', 'rejected');
            statusBadge.classList.add(normalizedStatus);
            statusBadge.textContent = formatDecisionStatus(normalizedStatus);
        }

        if (approveButton) {
            approveButton.disabled = normalizedStatus === 'approved';
        }

        if (rejectButton) {
            rejectButton.disabled = normalizedStatus === 'rejected';
        }
    }

    function fetchSubscriberList(sessionId) {
        return fetch(`${BASE_URL}/api?controller=SessionController&action=getSubscriberList&session_id=${sessionId}`, {
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(result => {
                if (!result.success) {
                    throw new Error(result.message || result.error || 'Failed to load subscriber list.');
                }
                renderSubscriberList(result.data || []);
            })
            .catch(error => {
                console.error('Subscriber list error:', error);
                if (typeof window.showToast === 'function') {
                    window.showToast(error.message || 'Failed to load subscriber list.', 'error');
                }
                const body = document.getElementById('sessionMainSubscriberBody');
                if (body) {
                    body.innerHTML = `<div class="session-main-subscriber-empty" style="color:#fc8181;">${escapeHtml(error.message || 'Failed to load subscriber list.')}</div>`;
                }
            });
    }

    function sendSubscriberDecision(button) {
        if (!sessionForSubscribersId) {
            return;
        }

        const actionType = button.getAttribute('data-action');
        const subscriberId = Number(button.getAttribute('data-subscriber-id') || 0);
        if (!subscriberId || (actionType !== 'approve' && actionType !== 'reject')) {
            showPeerLearningError('Invalid subscriber action.');
            return;
        }

        const row = button.closest('.subscriber-row');
        if (!row) {
            return;
        }

        const approveButton = row.querySelector('.subscriber-approve-btn');
        const rejectButton = row.querySelector('.subscriber-reject-btn');
        if (approveButton) {
            approveButton.disabled = true;
        }
        if (rejectButton) {
            rejectButton.disabled = true;
        }

        const formData = new FormData();
        formData.append('session_id', String(sessionForSubscribersId));
        formData.append('subscriber_id', String(subscriberId));

        const endpointAction = actionType === 'approve'
            ? 'approveSubscriberAction'
            : 'rejectSubscriberAction';

        fetch(`${BASE_URL}/api?controller=SessionController&action=${endpointAction}`, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(response => response.json())
            .then(result => {
                if (!result.success) {
                    throw new Error(result.message || result.error || 'Failed to update subscriber status.');
                }

                const nextStatus = (result.data && result.data.status) ? result.data.status : (actionType === 'approve' ? 'approved' : 'rejected');
                applySubscriberRowState(row, nextStatus);

                if (typeof window.showToast === 'function') {
                    window.showToast(result.message || `Subscriber ${nextStatus}.`, 'success');
                }

                if (result.data && typeof result.data.sub_count !== 'undefined') {
                    updateSubscriberCountForSession(sessionForSubscribersId, result.data.sub_count);
                }
            })
            .catch(error => {
                console.error('Subscriber decision error:', error);
                showPeerLearningError(error.message || 'Failed to update subscriber status.');
                if (approveButton) {
                    approveButton.disabled = false;
                }
                if (rejectButton) {
                    rejectButton.disabled = false;
                }
            });
    }

    function deleteSession(sessionId, triggerButton) {
        const normalizedSessionId = Number(sessionId || 0);
        if (!normalizedSessionId) {
            showPeerLearningError('Invalid session ID.');
            return;
        }

        requestConfirmation('Are you sure you want to delete this session? This action cannot be undone.')
            .then(confirmed => {
                if (!confirmed || deleteSubmitInFlight) {
                    return;
                }

                deleteSubmitInFlight = true;

                if (triggerButton) {
                    triggerButton.disabled = true;
                    triggerButton.textContent = 'Deleting...';
                }

                return fetch(`${BASE_URL}/api?controller=SessionController&action=deleteSession`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id=${normalizedSessionId}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.error || 'Failed to delete session');
                        }

                        sessionCache.delete(normalizedSessionId);
                        closeSessionModal();
                        loadTabData('my-sessions', 1);

                        if (typeof window.showToast === 'function') {
                            window.showToast(data.message || 'Session deleted successfully.', 'success');
                        }

                        if (document.getElementById('all-sessions-container').innerHTML) {
                            loadTabData('all-sessions', 1);
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting session:', error);
                        showPeerLearningError(error.message || 'Failed to delete session. Please try again.');
                    })
                    .finally(() => {
                        deleteSubmitInFlight = false;
                        if (triggerButton) {
                            triggerButton.disabled = false;
                            triggerButton.textContent = 'Delete';
                        }
                    });
            });
    }

    function handleSubscribeAction(subscribeButton) {
        const sessionId = Number(subscribeButton.getAttribute('data-session-id') || 0);
        if (!sessionId) {
            showPeerLearningError('Invalid session ID.');
            return;
        }

        const currentStatus = normalizeSubscriptionStatus(subscribeButton.getAttribute('data-status'));
        const isSubscribed = currentStatus !== 'none';
        const action = isSubscribed ? 'unsubscribeAction' : 'subscribeAction';

        subscribeButton.disabled = true;

        const formData = new FormData();
        formData.append('session_id', String(sessionId));

        fetch(`${BASE_URL}/api?controller=SessionController&action=${action}`, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(response => response.json())
            .then(result => {
                if (!result.success) {
                    throw new Error(result.message || result.error || 'Failed to update subscription.');
                }

                const state = result.data || {};
                const nextStatus = normalizeSubscriptionStatus(state.subscription_status || (isSubscribed ? 'none' : 'approved'));
                updateSubscribeButtonState(subscribeButton, nextStatus);

                const cachedSession = getSessionFromCache(sessionId) || { id: sessionId };
                cachedSession.subscription_status = nextStatus;
                cachedSession.is_subscribed = nextStatus === 'none' ? 0 : 1;

                if (typeof state.can_join !== 'undefined') {
                    cachedSession.can_join = Number(state.can_join || 0);
                }

                if (typeof state.sub_count !== 'undefined') {
                    cachedSession.sub_count = Math.max(0, Number(state.sub_count || 0));
                }

                upsertSessionCache(cachedSession);

                if (typeof state.sub_count !== 'undefined') {
                    updateSubscriberCountForSession(sessionId, state.sub_count);
                }

                if (activeSessionId === sessionId) {
                    openSessionModal(cachedSession);
                }

                if (currentTab === 'subscribed-sessions' && nextStatus === 'none') {
                    loadTabData('subscribed-sessions', 1);
                }

                if (typeof window.showToast === 'function') {
                    const defaultMessage = isSubscribed ? 'Unsubscribed successfully.' : 'Subscription updated successfully.';
                    window.showToast(result.message || defaultMessage, 'success');
                }
            })
            .catch(error => {
                console.error('Subscription error:', error);
                showPeerLearningError(error.message || 'Failed to update subscription. Please try again.');
            })
            .finally(() => {
                subscribeButton.disabled = false;
            });
    }

    document.querySelectorAll('.peer-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            switchTab(this.dataset.tab);
        });
    });

    if (sessionSearchForm) {
        sessionSearchForm.addEventListener('submit', function (event) {
            event.preventDefault();
            submitSearchForCurrentTab();
        });
    }

    if (sessionSearchInput) {
        sessionSearchInput.addEventListener('input', function () {
            updateSearchControls();
        });
    }

    if (sessionSearchClearButton) {
        sessionSearchClearButton.addEventListener('click', function () {
            if (sessionSearchInput) {
                sessionSearchInput.value = '';
            }
            updateSearchControls();
            clearSearchForTab(currentTab, true);
        });
    }

    document.getElementById('my-sessions-load-more').addEventListener('click', function () {
        loadTabData('my-sessions', getNextPageForTab('my-sessions'));
    });

    document.getElementById('all-sessions-load-more').addEventListener('click', function () {
        loadTabData('all-sessions', getNextPageForTab('all-sessions'));
    });

    document.getElementById('subscribed-sessions-load-more').addEventListener('click', function () {
        loadTabData('subscribed-sessions', getNextPageForTab('subscribed-sessions'));
    });

    document.addEventListener('submit', function (event) {
        const modalForm = event.target.closest('.js-modal-create-session-form');
        if (!modalForm) {
            return;
        }

        event.preventDefault();
        submitCreateSessionModalForm(modalForm);
    });

    document.addEventListener('click', function (event) {
        const sessionModal = document.getElementById('sessionMainModal');
        const createModal = document.getElementById('createSessionModal');

        if (event.target === createModal) {
            closeCreateSessionModal();
            return;
        }

        if (event.target === sessionModal) {
            closeSessionModal();
            return;
        }

        const createModalCloseButton = event.target.closest('[data-action="close-create-session-modal"]');
        if (createModalCloseButton) {
            closeCreateSessionModal();
            return;
        }

        const createModalOpenTrigger = event.target.closest('[data-action="open-create-session-modal"]');
        if (createModalOpenTrigger) {
            event.preventDefault();
            const sessionId = Number(createModalOpenTrigger.getAttribute('data-session-id') || 0);
            openCreateSessionModal(sessionId);
            return;
        }

        const closeButton = event.target.closest('[data-modal-action="close-session-modal"]');
        if (closeButton) {
            closeSessionModal();
            return;
        }

        const card = event.target.closest('.session-card');
        if (card && !event.target.closest('a, button, input, select, textarea, label')) {
            const sessionId = Number(card.getAttribute('data-session-id') || 0);
            openSessionModalById(sessionId).catch(error => {
                console.error('Failed to open session modal:', error);
                showPeerLearningError(error.message || 'Unable to open this session view.');
            });
            return;
        }

        const modalActionButton = event.target.closest('[data-modal-action]');
        if (modalActionButton) {
            const action = modalActionButton.getAttribute('data-modal-action');
            const sessionId = Number(modalActionButton.getAttribute('data-session-id') || activeSessionId || 0);

            if (action === 'edit-session') {
                editSession(sessionId);
                return;
            }

            if (action === 'delete-session') {
                deleteSession(sessionId, modalActionButton);
                return;
            }

            if (action === 'refresh-subscriber-list') {
                sessionForSubscribersId = sessionId;
                renderSubscriberListLoading();
                fetchSubscriberList(sessionId);
                return;
            }
        }

        const decisionButton = event.target.closest('.subscriber-decision-btn');
        if (decisionButton) {
            sendSubscriberDecision(decisionButton);
            return;
        }

        const subscribeButton = event.target.closest('.session-subscribe-btn');
        if (subscribeButton) {
            handleSubscribeAction(subscribeButton);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (createSessionModalElement && createSessionModalElement.classList.contains('show')) {
                closeCreateSessionModal();
                return;
            }

            if (sessionMainModalElement && sessionMainModalElement.classList.contains('show')) {
                closeSessionModal();
            }
            return;
        }

        const targetCard = event.target && event.target.classList ? event.target : null;
        if (!targetCard || !targetCard.classList.contains('session-card')) {
            return;
        }

        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        event.preventDefault();
        const sessionId = Number(targetCard.getAttribute('data-session-id') || 0);
        openSessionModalById(sessionId).catch(error => {
            console.error('Failed to open session modal:', error);
            showPeerLearningError(error.message || 'Unable to open this session view.');
        });
    });

    window.addEventListener('load', function () {
        syncSearchUiToCurrentTab();
        if (window._peerLearningDeepLinkHandled) {
            return;
        }

        window._peerLearningDeepLinkHandled = true;

        const initialTab = normalizeTabName(pageParams.tab);
        switchTab(initialTab)
            .then(() => {
                const deepLinkSessionId = normalizePositiveInt(pageParams.session_id);
                if (!deepLinkSessionId) {
                    return;
                }

                return openSessionModalById(deepLinkSessionId).catch(error => {
                    console.error('Deep-link session open failed:', error);
                    showPeerLearningError(error.message || 'Unable to open this session from the link.');
                });
            })
            .catch(error => {
                console.error('Peer-learning initialization failed:', error);
                showPeerLearningError('Failed to initialize Peer Learning. Please refresh and try again.');
            });
    });
</script>