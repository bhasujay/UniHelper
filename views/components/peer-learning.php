<?php
// peer-learning.php
// Display study sessions with tabs for "My Sessions" and "All Sessions"
// Uses AJAX with infinite scroll to load sessions dynamically
?>

<style>
    /* Peer Learning Component Styles */
    .peer-learning-container {
        width: 100%;
    }

    .peer-learning-toolbar {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-bottom: 1rem;
        min-height: 3rem;
    }

    .peer-create-session-btn {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        border: 1px solid var(--primary);
        background: var(--primary);
        color: rgb(255, 255, 255);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 0 0 transparent;
        text-decoration: none;
        flex-shrink: 0;
        position: relative;
        overflow: visible;
    }

    .peer-create-session-btn:hover {
        background: var(--primary);
        color: rgb(0, 0, 0);
        transform: translateY(-2px);
        box-shadow: var(--glow-primary);
    }

    .peer-create-session-btn svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .peer-create-session-label {
        position: absolute;
        right: calc(100% + 0.65rem);
        top: 50%;
        transform: translateY(-50%) translateX(10px);
        background: rgba(164, 109, 255, 0.16);
        color: var(--primary);
        border: 1px solid rgba(164, 109, 255, 0.35);
        border-radius: 999px;
        padding: 0.45rem 0.85rem;
        white-space: nowrap;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.2px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.35s ease, transform 0.35s ease;
    }

    .peer-create-session-btn:hover .peer-create-session-label,
    .peer-create-session-btn:focus-visible .peer-create-session-label {
        opacity: 1;
        transform: translateY(-50%) translateX(0);
    }

    .peer-tabs {
        display: flex;
        gap: 0;
        border-bottom: 1px solid var(--border);
        margin-bottom: 2rem;
    }

    .peer-tab {
        padding: 1rem 1.5rem;
        background: transparent;
        border: none;
        color: var(--foreground);
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
        margin-bottom: -1px;
    }

    .peer-tab:hover {
        color: var(--primary);
        background: rgba(164, 109, 255, 0.05);
    }

    .peer-tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    .peer-content {
        display: none;
    }

    .peer-content.active {
        display: block;
    }

    /* Session Cards */
    .sessions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .session-card {
        background: rgba(8, 8, 8, 0.5);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(164, 109, 255, 0.2);
        border-radius: 1rem;
        padding: 1.5rem;
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .session-card:hover {
        transform: translateY(-5px);
        border-color: var(--primary);
        box-shadow: 0 10px 30px var(--glow-primary);
    }

    .session-card.expired {
        opacity: 0.7;
    }

    .session-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
    }

    .session-audience {
        display: inline-block;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.4rem 0.8rem;
        background: var(--gradient-primary);
        color: rgb(0, 0, 0);
        border-radius: 0.35rem;
    }

    .session-status-badges {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .session-expired-badge {
        display: inline-block;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 0.35rem 0.7rem;
        background: #fc8181;
        color: white;
        border-radius: 0.3rem;
    }

    .session-subject {
        display: inline-block;
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--primary);
        text-transform: uppercase;
    }

    .session-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--foreground);
        line-height: 1.4;
    }

    .session-description {
        font-size: 0.9rem;
        color: var(--muted-foreground);
        line-height: 1.6;
        max-height: 75px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .session-meta {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        padding: 1rem 0;
        border-top: 1px solid rgba(164, 109, 255, 0.1);
        border-bottom: 1px solid rgba(164, 109, 255, 0.1);
    }

    .session-meta-item {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
    }

    .session-meta-label {
        color: var(--muted-foreground);
        font-weight: 500;
    }

    .session-meta-value {
        color: var(--foreground);
        font-weight: 600;
    }

    .session-datetime {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9rem;
    }

    .session-datetime-value {
        color: var(--foreground);
        font-weight: 600;
    }

    .session-duration {
        color: var(--primary);
        font-weight: 600;
        margin-left: 0.5rem;
    }

    .session-tags {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .session-tag {
        font-size: 0.75rem;
        padding: 0.35rem 0.7rem;
        background: rgba(164, 109, 255, 0.15);
        color: var(--primary);
        border-radius: 0.3rem;
        border: 1px solid rgba(164, 109, 255, 0.3);
    }

    .session-creator {
        font-size: 0.85rem;
        color: var(--muted-foreground);
        padding-top: 0.5rem;
        border-top: 1px solid rgba(164, 109, 255, 0.1);
    }

    .session-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: auto;
    }

    .session-action-btn {
        flex: 1;
        padding: 0.65rem 1rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .session-join-btn {
        background: var(--gradient-primary);
        color: rgb(0, 0, 0);
    }

    .session-join-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--glow-primary);
    }

    .session-edit-btn {
        background: rgba(164, 109, 255, 0.2);
        color: var(--primary);
        border: 1px solid var(--primary);
    }

    .session-edit-btn:hover {
        background: var(--primary);
        color: rgb(0, 0, 0);
    }

    .session-delete-btn {
        background: #9f0505;
        color: white;
    }

    .session-delete-btn:hover {
        background: #a10808;
    }

    .session-actions-all {
        flex-direction: column;
    }

    .session-subscribe-btn {
        background: transparent;
        color: #fc8181;
        border: 1px solid #fc8181;
    }

    .session-subscribe-btn:hover {
        background: rgba(252, 129, 129, 0.12);
    }

    .session-subscribe-btn.subscribed {
        background: #fc8181;
        color: #ffffff;
        border-color: #fc8181;
    }

    .session-subscribe-btn.subscribed:hover {
        background: #f56565;
    }

    .session-subscribe-btn.pending {
        border-color: #f6ad55;
        color: #f6ad55;
    }

    .session-subscribe-btn.pending:hover {
        background: rgba(246, 173, 85, 0.12);
    }

    .session-subscribe-list-btn {
        background: transparent;
        color: #f87171;
        border: 1px solid #f87171;
    }

    .session-subscribe-list-btn:hover {
        background: rgba(248, 113, 113, 0.14);
    }

    .session-subscribe-btn:disabled {
        opacity: 0.65;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .subscriber-count {
        display: inline-block;
        margin-top: 0.1rem;
        font-size: 0.82rem;
        color: var(--muted-foreground);
        font-weight: 500;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        background: rgba(8, 8, 8, 0.5);
        border: 1px dashed rgba(164, 109, 255, 0.3);
        border-radius: 1rem;
    }

    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-state-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--foreground);
        margin-bottom: 0.5rem;
    }

    .empty-state-text {
        color: var(--muted-foreground);
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }

    .empty-state-btn {
        display: inline-block;
        padding: 0.75rem 2rem;
        background: var(--gradient-primary);
        color: rgb(0, 0, 0);
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .empty-state-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--glow-primary);
    }

    /* Loading State */
    .loading-spinner {
        display: flex;
        justify-content: center;
        padding: 2rem;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid rgba(164, 109, 255, 0.2);
        border-top-color: var(--primary);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Load More Button */
    .load-more-btn {
        display: block;
        margin: 2rem auto;
        padding: 0.75rem 2rem;
        background: transparent;
        color: var(--primary);
        border: 1px solid var(--primary);
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .load-more-btn:hover {
        background: rgba(164, 109, 255, 0.1);
    }

    /* Delete Confirmation Modal */
    .delete-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(12px);
    }

    .delete-modal.show {
        display: flex;
    }

    .delete-modal-content {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 2rem;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    .delete-modal-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--foreground);
        margin-bottom: 1rem;
    }

    .delete-modal-text {
        color: var(--muted-foreground);
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }

    .delete-modal-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .delete-modal-btn {
        padding: 0.65rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .delete-modal-cancel {
        background: var(--border);
        color: var(--foreground);
    }

    .delete-modal-cancel:hover {
        background: rgba(164, 109, 255, 0.1);
    }

    .delete-modal-confirm {
        background: #fc8181;
        color: white;
    }

    .delete-modal-confirm:hover {
        background: #f56565;
    }

    /* Subscriber Management Modal */
    .subscriber-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.72);
        z-index: 10001;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(12px);
    }

    .subscriber-modal.show {
        display: flex;
    }

    .subscriber-modal-content {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 1rem;
        width: min(800px, 94vw);
        max-height: 80vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .subscriber-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.2rem;
        border-bottom: 1px solid rgba(164, 109, 255, 0.2);
    }

    .subscriber-modal-title {
        margin: 0;
        font-size: 1.2rem;
        color: var(--foreground);
    }

    .subscriber-modal-close {
        border: none;
        background: transparent;
        color: var(--muted-foreground);
        font-size: 1.25rem;
        cursor: pointer;
        padding: 0.15rem 0.4rem;
        border-radius: 0.4rem;
    }

    .subscriber-modal-close:hover {
        color: var(--foreground);
        background: rgba(164, 109, 255, 0.16);
    }

    .subscriber-modal-body {
        padding: 1rem 1.2rem 1.2rem;
        overflow-y: auto;
    }

    .subscriber-list {
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
    }

    .subscriber-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        border: 1px solid rgba(164, 109, 255, 0.18);
        border-radius: 0.75rem;
        padding: 0.8rem 0.9rem;
        background: rgba(8, 8, 8, 0.45);
    }

    .subscriber-name {
        color: var(--foreground);
        font-weight: 600;
        font-size: 0.95rem;
    }

    .subscriber-row-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .subscriber-status {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        border: 1px solid transparent;
        padding: 0.28rem 0.7rem;
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .subscriber-status.pending {
        color: #f6ad55;
        border-color: rgba(246, 173, 85, 0.6);
        background: rgba(246, 173, 85, 0.14);
    }

    .subscriber-status.approved {
        color: #48bb78;
        border-color: rgba(72, 187, 120, 0.6);
        background: rgba(72, 187, 120, 0.14);
    }

    .subscriber-status.rejected {
        color: #fc8181;
        border-color: rgba(252, 129, 129, 0.6);
        background: rgba(252, 129, 129, 0.14);
    }

    .subscriber-decision-btn {
        border: none;
        border-radius: 0.45rem;
        padding: 0.42rem 0.8rem;
        color: #fff;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        transition: opacity 0.25s ease;
    }

    .subscriber-decision-btn:hover {
        opacity: 0.9;
    }

    .subscriber-decision-btn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }

    .subscriber-approve-btn {
        background: #2f855a;
    }

    .subscriber-reject-btn {
        background: #9f0505;
    }

    .subscriber-modal-empty {
        text-align: center;
        color: var(--muted-foreground);
        padding: 1.5rem 0.2rem;
    }

    @media (max-width: 768px) {
        .sessions-grid {
            grid-template-columns: 1fr;
        }

        .session-actions {
            flex-direction: column;
        }

        .session-action-btn {
            width: 100%;
        }

        .peer-tabs {
            flex-direction: column;
        }

        .peer-tab {
            padding: 0.75rem 1rem;
        }

        .peer-create-session-label {
            display: none;
        }

        .subscriber-row {
            flex-direction: column;
            align-items: flex-start;
        }

        .subscriber-row-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }
</style>

<div class="peer-learning-container">
    <?php if (!isset($user) || $user->role !== 'role-applicant'): ?>
        <div class="peer-learning-toolbar">
            <a href="/UniHelper/create-session" class="peer-create-session-btn" title="Create Session"
                aria-label="Create Session">
                <span class="peer-create-session-label">Create New Session</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14"></path>
                    <path d="M5 12h14"></path>
                </svg>
            </a>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="peer-tabs">
        <button class="peer-tab active" data-tab="my-sessions">My Sessions</button>
        <button class="peer-tab" data-tab="all-sessions">All Sessions</button>
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
</div>

<!-- Delete Confirmation Modal -->
<div class="delete-modal" id="deleteModal">
    <div class="delete-modal-content">
        <h3 class="delete-modal-title">Delete Session?</h3>
        <p class="delete-modal-text">Are you sure you want to delete this session? This action cannot be undone.</p>
        <div class="delete-modal-actions">
            <button class="delete-modal-btn delete-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="delete-modal-btn delete-modal-confirm" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<!-- Subscriber Management Modal -->
<div class="subscriber-modal" id="subscriberModal">
    <div class="subscriber-modal-content">
        <div class="subscriber-modal-header">
            <h3 class="subscriber-modal-title" id="subscriberModalTitle">Subscribe List</h3>
            <button type="button" class="subscriber-modal-close" aria-label="Close"
                onclick="closeSubscriberModal()">✕</button>
        </div>
        <div class="subscriber-modal-body" id="subscriberModalBody"></div>
    </div>
</div>

<script>
    const BASE_URL = '/UniHelper';
    let currentTab = 'my-sessions';
    let mySessionsPage = 1;
    let allSessionsPage = 1;
    let sessionToDelete = null;
    let sessionForSubscribersId = null;

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

        const safeCount = Math.max(0, Number(count || 0));
        document.querySelectorAll(`.subscriber-count[data-session-id="${sessionId}"]`).forEach(element => {
            element.textContent = formatSubscriberCount(safeCount);
        });
    }

    document.querySelectorAll('.peer-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            switchTab(this.dataset.tab);
        });
    });

    function switchTab(tabName) {
        document.querySelectorAll('.peer-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

        document.querySelectorAll('.peer-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(tabName).classList.add('active');

        currentTab = tabName;

        if (tabName === 'my-sessions' && !document.getElementById('my-sessions-container').innerHTML) {
            loadMyessions(1);
        } else if (tabName === 'all-sessions' && !document.getElementById('all-sessions-container').innerHTML) {
            loadAllSessions(1);
        }
    }

    function loadMyessions(page) {
        const container = document.getElementById('my-sessions-container');
        const loading = document.getElementById('my-sessions-loading');
        const loadMoreBtn = document.getElementById('my-sessions-load-more');

        if (page === 1) {
            loading.style.display = 'flex';
        }

        fetch(`${BASE_URL}/api?controller=SessionController&action=getMyessions&page=${page}`)
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';

                if (!data.success) {
                    throw new Error(data.error || 'Failed to load your sessions');
                }

                if (data.data && data.data.length > 0) {
                    if (page === 1) {
                        container.innerHTML = '';
                    }

                    data.data.forEach(session => {
                        container.innerHTML += createSessionCard(session, true);
                    });

                    if (data.count >= 10) {
                        loadMoreBtn.style.display = 'block';
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }

                    mySessionsPage = page + 1;
                } else if (page === 1) {
                    container.innerHTML = createEmptyState('No sessions yet', 'Create your first study session to get started!');
                    loadMoreBtn.style.display = 'none';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                console.error('Error loading sessions:', error);
                alert(error.message || 'Failed to load your sessions. Please try again.');
                container.innerHTML = '<p style="color: #fc8181; text-align: center;">Failed to load sessions</p>';
            });
    }

    function loadAllSessions(page) {
        const container = document.getElementById('all-sessions-container');
        const loading = document.getElementById('all-sessions-loading');
        const loadMoreBtn = document.getElementById('all-sessions-load-more');

        if (page === 1) {
            loading.style.display = 'flex';
        }

        fetch(`${BASE_URL}/api?controller=SessionController&action=getAllSessions&page=${page}`)
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';

                if (!data.success) {
                    throw new Error(data.error || 'Failed to load sessions');
                }

                if (data.data && data.data.length > 0) {
                    if (page === 1) {
                        container.innerHTML = '';
                    }

                    data.data.forEach(session => {
                        container.innerHTML += createSessionCard(session, false);
                    });

                    if (data.count >= 10) {
                        loadMoreBtn.style.display = 'block';
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }

                    allSessionsPage = page + 1;
                } else if (page === 1) {
                    container.innerHTML = createEmptyState('No sessions available', 'Create a new session to get started!');
                    loadMoreBtn.style.display = 'none';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                console.error('Error loading sessions:', error);
                alert(error.message || 'Failed to load sessions. Please try again.');
                container.innerHTML = '<p style="color: #fc8181; text-align: center;">Failed to load sessions</p>';
            });
    }

    function createSessionCard(session, showEditDelete) {
        const isExpired = session.is_expired || (session.deleted_at && !session.is_deleted);
        const audienceLabel = getAudienceLabel(session.audience);
        const tags = session.tags ? session.tags.split(',').map(tag => `<span class="session-tag">${tag.trim()}</span>`).join('') : '';
        const subscriptionStatus = normalizeSubscriptionStatus(session.subscription_status || (Number(session.is_subscribed) === 1 ? 'approved' : 'none'));
        const isSubscribed = subscriptionStatus !== 'none';
        const subscriberCount = Math.max(0, Number(session.sub_count || 0));
        const subscribeBtnClass = subscriptionStatus === 'approved'
            ? 'session-subscribe-btn subscribed'
            : (subscriptionStatus === 'pending' ? 'session-subscribe-btn pending' : 'session-subscribe-btn');
        const subscribeBtnText = getSubscribeButtonLabel(subscriptionStatus);
        const canJoin = Number(session.can_join || 0) === 1;

        let actions = '';
        if (showEditDelete) {
            const subscribeListButton = session.audience === 'private'
                ? `<button
                        type="button"
                        class="session-action-btn session-subscribe-list-btn"
                        data-session-id="${session.id}"
                        data-session-title="${escapeAttribute(session.title || 'Private Session')}"
                    >Subscribe List</button>`
                : '';

            actions = `
                <div class="session-actions">
                    <button class="session-action-btn session-edit-btn" onclick="editSession(${session.id})">Edit</button>
                    <button class="session-action-btn session-delete-btn" onclick="openDeleteModal(${session.id})">Delete</button>
                    ${subscribeListButton}
                </div>
            `;
        } else {
            const joinButton = (session.session_link && canJoin)
                ? `<a href="${session.session_link}" target="_blank" class="session-action-btn session-join-btn" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">Join Session</a>`
                : '';

            actions = `
                <div class="session-actions session-actions-all">
                    ${joinButton}
                    <button
                        type="button"
                        class="session-action-btn ${subscribeBtnClass}"
                        data-session-id="${session.id}"
                        data-status="${subscriptionStatus}"
                        data-subscribed="${isSubscribed ? 1 : 0}"
                    >${subscribeBtnText}</button>
                    <span class="subscriber-count" data-session-id="${session.id}">${formatSubscriberCount(subscriberCount)}</span>
                </div>
            `;
        }

        const subscriberCountMeta = showEditDelete
            ? `<span class="subscriber-count" data-session-id="${session.id}">${formatSubscriberCount(subscriberCount)}</span>`
            : '';

        const expiredBadge = isExpired ? '<span class="session-expired-badge">Expired</span>' : '';

        return `
            <div class="session-card ${isExpired ? 'expired' : ''}">
                <div class="session-header">
                    <div>
                        <span class="session-audience">${audienceLabel}</span>
                        <div style="margin-top: 0.5rem;">
                            <span class="session-subject">${session.subject}</span>
                        </div>
                    </div>
                    <div class="session-status-badges">${expiredBadge}</div>
                </div>
                <h3 class="session-title">${session.title}</h3>
                <p class="session-description">${session.description}</p>
                <div class="session-meta">
                    <div class="session-meta-item">
                        <span class="session-meta-label">Date:</span>
                        <span class="session-meta-value">${formatDate(session.date)}</span>
                    </div>
                    <div class="session-meta-item">
                        <span class="session-meta-label">Time:</span>
                        <span class="session-meta-value">${session.time} <span class="session-duration">[${session.duration}h]</span></span>
                    </div>
                </div>
                ${tags ? `<div class="session-tags">${tags}</div>` : ''}
                <div class="session-creator">
                    Author: ${session.creator_name || 'Unknown'} • ${session.creator_university || session.university || 'Unknown University'}
                </div>
                ${subscriberCountMeta}
                ${actions}
            </div>
        `;
    }

    function formatSubscriberCount(count) {
        const safeCount = Math.max(0, Number(count || 0));
        return `${safeCount} subscriber${safeCount === 1 ? '' : 's'}`;
    }

    function createEmptyState(title, text) {
        return `
            <div class="empty-state">
                <div class="empty-state-icon">📚</div>
                <h3 class="empty-state-title">${title}</h3>
                <p class="empty-state-text">${text}</p>
                <a href="${BASE_URL}/create-session" class="empty-state-btn">Create Session</a>
            </div>
        `;
    }

    function formatDate(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function editSession(sessionId) {
        window.location.href = `${BASE_URL}/create-session?session_id=${sessionId}`;
    }

    function openDeleteModal(sessionId) {
        sessionToDelete = sessionId;
        document.getElementById('deleteModal').classList.add('show');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('show');
        sessionToDelete = null;
    }

    function openSubscriberModal(buttonElement) {
        const sessionId = Number(buttonElement.getAttribute('data-session-id') || 0);
        if (!sessionId) {
            alert('Invalid session ID.');
            return;
        }

        sessionForSubscribersId = sessionId;
        const sessionTitle = buttonElement.getAttribute('data-session-title') || 'Private Session';
        document.getElementById('subscriberModalTitle').textContent = `Subscribe List - ${sessionTitle}`;
        document.getElementById('subscriberModal').classList.add('show');
        renderSubscriberModalLoading();
        fetchSubscriberList(sessionId);
    }

    function closeSubscriberModal() {
        document.getElementById('subscriberModal').classList.remove('show');
        sessionForSubscribersId = null;
        document.getElementById('subscriberModalBody').innerHTML = '';
    }

    function renderSubscriberModalLoading() {
        document.getElementById('subscriberModalBody').innerHTML = `
            <div class="loading-spinner" style="padding: 1.5rem 0;">
                <div class="spinner"></div>
            </div>
        `;
    }

    function renderSubscriberList(list) {
        const body = document.getElementById('subscriberModalBody');

        if (!Array.isArray(list) || list.length === 0) {
            body.innerHTML = '<div class="subscriber-modal-empty">No subscribers yet.</div>';
            return;
        }

        const rows = list.map(item => {
            const status = normalizeSubscriberDecisionStatus(item.status);
            const fullName = `${item.first_name || ''} ${item.last_name || ''}`.trim() || 'Unknown User';
            const approveDisabled = status === 'approved' ? 'disabled' : '';
            const rejectDisabled = status === 'rejected' ? 'disabled' : '';

            return `
                <div class="subscriber-row" data-subscriber-id="${item.subscriber_id}">
                    <div class="subscriber-name">${escapeHtml(fullName)}</div>
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
        fetch(`${BASE_URL}/api?controller=SessionController&action=getSubscriberList&session_id=${sessionId}`, {
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
                document.getElementById('subscriberModalBody').innerHTML =
                    `<div class="subscriber-modal-empty" style="color:#fc8181;">${escapeHtml(error.message || 'Failed to load subscriber list.')}</div>`;
            });
    }

    function sendSubscriberDecision(button) {
        if (!sessionForSubscribersId) {
            return;
        }

        const actionType = button.getAttribute('data-action');
        const subscriberId = Number(button.getAttribute('data-subscriber-id') || 0);
        if (!subscriberId || (actionType !== 'approve' && actionType !== 'reject')) {
            alert('Invalid subscriber action.');
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

                if (result.data && typeof result.data.sub_count !== 'undefined') {
                    updateSubscriberCountForSession(sessionForSubscribersId, result.data.sub_count);
                }
            })
            .catch(error => {
                console.error('Subscriber decision error:', error);
                alert(error.message || 'Failed to update subscriber status.');
                if (approveButton) {
                    approveButton.disabled = false;
                }
                if (rejectButton) {
                    rejectButton.disabled = false;
                }
            });
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
        if (sessionToDelete) {
            deleteSession(sessionToDelete);
        }
    });

    function deleteSession(sessionId) {
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Deleting...';

        fetch(`${BASE_URL}/api?controller=SessionController&action=deleteSession`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id=${sessionId}`
        })
            .then(response => response.json())
            .then(data => {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Delete';

                if (!data.success) {
                    throw new Error(data.error || 'Failed to delete session');
                }

                closeDeleteModal();
                loadMyessions(1);
            })
            .catch(error => {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Delete';
                console.error('Error deleting session:', error);
                alert(error.message || 'Failed to delete session. Please try again.');
            });
    }

    document.addEventListener('click', function (event) {
        const subscribeListButton = event.target.closest('.session-subscribe-list-btn');
        if (subscribeListButton) {
            openSubscriberModal(subscribeListButton);
            return;
        }

        const decisionButton = event.target.closest('.subscriber-decision-btn');
        if (decisionButton) {
            sendSubscriberDecision(decisionButton);
            return;
        }

        const subscribeButton = event.target.closest('.session-subscribe-btn');
        if (!subscribeButton) {
            return;
        }

        const sessionId = Number(subscribeButton.getAttribute('data-session-id') || 0);
        if (!sessionId) {
            alert('Invalid session ID.');
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

                if (typeof state.sub_count !== 'undefined') {
                    updateSubscriberCountForSession(sessionId, state.sub_count);
                } else {
                    const countElement = subscribeButton.parentElement.querySelector('.subscriber-count');
                    if (countElement) {
                        const currentCount = Number((countElement.textContent.match(/\d+/) || ['0'])[0]);
                        const nextCount = isSubscribed ? Math.max(0, currentCount - 1) : currentCount + 1;
                        updateSubscriberCountForSession(sessionId, nextCount);
                    }
                }
            })
            .catch(error => {
                console.error('Subscription error:', error);
                alert(error.message || 'Failed to update subscription. Please try again.');
            })
            .finally(() => {
                subscribeButton.disabled = false;
            });
    });

    document.getElementById('my-sessions-load-more').addEventListener('click', function () {
        loadMyessions(mySessionsPage);
    });

    document.getElementById('all-sessions-load-more').addEventListener('click', function () {
        loadAllSessions(allSessionsPage);
    });

    document.getElementById('deleteModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    document.getElementById('subscriberModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeSubscriberModal();
        }
    });

    window.addEventListener('load', function () {
        loadMyessions(1);
    });
</script>