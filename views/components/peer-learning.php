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
        to { transform: rotate(360deg); }
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
    }
</style>

<div class="peer-learning-container">
    <?php if (!isset($user) || $user->role !== 'role-applicant'): ?>
        <div class="peer-learning-toolbar">
            <a href="/UniHelper/create-session" class="peer-create-session-btn" title="Create Session" aria-label="Create Session">
                <span class="peer-create-session-label">Create New Session</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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

<script>
    const BASE_URL = '/unihelper';
    let currentTab = 'my-sessions';
    let mySessionsPage = 1;
    let allSessionsPage = 1;
    let sessionToDelete = null;

    // Initialize tabs
    document.querySelectorAll('.peer-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            switchTab(this.dataset.tab);
        });
    });

    // Switch between tabs
    function switchTab(tabName) {
        // Update active tab button
        document.querySelectorAll('.peer-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

        // Update active content
        document.querySelectorAll('.peer-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(tabName).classList.add('active');

        currentTab = tabName;

        // Load sessions if empty
        if (tabName === 'my-sessions' && !document.getElementById('my-sessions-container').innerHTML) {
            loadMyessions(1);
        } else if (tabName === 'all-sessions' && !document.getElementById('all-sessions-container').innerHTML) {
            loadAllSessions(1);
        }
    }

    // Load user's sessions
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

                    // Show load more button if more sessions available
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

    // Load all sessions
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

                    // Show load more button if more sessions available
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

    // Create session card HTML
    function createSessionCard(session, showEditDelete) {
        const isExpired = session.is_expired || (session.deleted_at && !session.is_deleted);
        const audienceLabel = session.audience === 'my_university' ? 'My University' : 'All Universities';
        const tags = session.tags ? session.tags.split(',').map(tag => `<span class="session-tag">${tag.trim()}</span>`).join('') : '';
        
        let actions = '';
        if (showEditDelete) {
            actions = `
                <div class="session-actions">
                    <button class="session-action-btn session-edit-btn" onclick="editSession(${session.id})">Edit</button>
                    <button class="session-action-btn session-delete-btn" onclick="openDeleteModal(${session.id})">Delete</button>
                </div>
            `;
        } else if (session.session_link) {
            actions = `
                <div class="session-actions">
                    <a href="${session.session_link}" target="_blank" class="session-action-btn session-join-btn" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">Join Session</a>
                </div>
            `;
        }

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
                    ${session.creator_name || 'Unknown'} • ${session.university || 'Unknown University'}
                </div>
                ${actions}
            </div>
        `;
    }

    // Create empty state HTML
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

    // Format date to readable format
    function formatDate(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    // Edit session (redirect to edit page - to be implemented)
    function editSession(sessionId) {
        window.location.href = `${BASE_URL}/create-session?session_id=${sessionId}`;
    }

    // Open delete confirmation modal
    function openDeleteModal(sessionId) {
        sessionToDelete = sessionId;
        document.getElementById('deleteModal').classList.add('show');
    }

    // Close delete modal
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('show');
        sessionToDelete = null;
    }

    // Confirm delete
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (sessionToDelete) {
            deleteSession(sessionToDelete);
        }
    });

    // Delete session via API
    function deleteSession(sessionId) {
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Deleting...';

        fetch(`${BASE_URL}/api?controller=SessionController&action=deleteSession`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
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

    // Load more buttons
    document.getElementById('my-sessions-load-more').addEventListener('click', function() {
        loadMyessions(mySessionsPage);
    });

    document.getElementById('all-sessions-load-more').addEventListener('click', function() {
        loadAllSessions(allSessionsPage);
    });

    // Close modal when clicking outside
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Load initial data
    window.addEventListener('load', function() {
        loadMyessions(1);
    });
</script>
