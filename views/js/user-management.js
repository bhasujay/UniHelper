/**
 * User Management Component Logic
 * Backend-integrated behavior for admin moderation actions,
 * with paginated list loading and server-side search.
 */

document.addEventListener('DOMContentLoaded', () => {
    const API_BASE = '/unihelper/api';
    const API_CONTROLLER = 'userManagementController';
    const DEFAULT_AVATAR = '/unihelper/public/uploads/profilePictures/default-pfp.png';
    const PAGE_SIZE = 25;
    const SEARCH_DEBOUNCE_MS = 350;

    const tableBody = document.getElementById('um-table-body');
    const searchInput = document.getElementById('um-search-input');
    const slidePanel = document.getElementById('um-slide-panel');
    const closePanelBtn = document.getElementById('um-close-panel');
    const slideBodyContent = document.getElementById('um-slide-body-content');
    const usersView = document.getElementById('um-users-view');
    const reportsView = document.getElementById('um-reports-view');
    const reportsList = document.getElementById('um-reports-list');
    const listHeading = document.querySelector('.um-list-heading');
    const listTitle = document.getElementById('um-list-title');
    const dateColumnTitle = document.getElementById('um-date-column-title');
    const moderationBtn = document.getElementById('um-moderation-btn');
    const loadMoreWrap = document.getElementById('um-load-more-wrap');
    const loadMoreBtn = document.getElementById('um-load-more-btn');

    if (!tableBody || !searchInput || !slidePanel || !usersView || !reportsView || !reportsList) {
        return;
    }

    const panelOverlay = slidePanel.querySelector('.um-slide-panel-overlay');
    const kpiTabs = Array.from(document.querySelectorAll('.um-kpi-tab[data-filter]'));

    function createTabState() {
        return {
            items: [],
            total: 0,
            limit: PAGE_SIZE,
            offset: 0,
            nextOffset: 0,
            hasMore: false,
            initialized: false,
            loading: false,
            searchQuery: ''
        };
    }

    const roleLabelMap = {
        'role-admin': 'Admin',
        'role-undergrad': 'Undergraduate',
        'role-applicant': 'Applicant',
        'role-profile': 'Profile'
    };

    const reportReasonLabelMap = {
        harassment: 'Harassment',
        spam: 'Spam',
        inappropriate_pfp: 'Inappropriate profile picture',
        fake_account: 'Fake account',
        other: 'Other'
    };

    const state = {
        activeFilter: 'all',
        searchQuery: '',
        searchTimer: null,
        summary: {
            totalUsers: 0,
            pendingReports: 0,
            bannedAccounts: 0,
            deletedUsers: 0
        },
        tabs: {
            all: createTabState(),
            reports: createTabState(),
            banned: createTabState(),
            deleted: createTabState()
        }
    };

    function showMessage(message, type = 'success') {
        if (typeof showToast === 'function') {
            showToast(message, type);
            return;
        }
        console.log(message);
    }

    function escapeHtml(value) {
        const text = String(value ?? '');
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function fullName(user) {
        return `${user.firstName || ''} ${user.lastName || ''}`.trim() || `User #${user.id}`;
    }

    function userRoleLabel(user) {
        if (user.role === 'role-admin') {
            return 'Admin';
        }
        if (Number(user.moderator) === 1) {
            return 'Moderator';
        }
        return roleLabelMap[user.role] || 'User';
    }

    function roleClass(roleLabel) {
        const key = String(roleLabel).toLowerCase();
        if (key === 'admin') {
            return 'um-badge-admin';
        }
        if (key === 'moderator') {
            return 'um-badge-mod';
        }
        return 'um-badge-user';
    }

    function formatDate(input, options = { year: 'numeric', month: 'short', day: 'numeric' }) {
        const date = new Date(input);
        if (Number.isNaN(date.getTime())) {
            return '-';
        }
        return date.toLocaleDateString('en-US', options);
    }

    function profileUrl(userId) {
        const numericId = Number.parseInt(String(userId), 10);
        if (Number.isNaN(numericId)) {
            return '/unihelper/view/profile';
        }
        return `/unihelper/view/profile/${numericId}`;
    }

    function imgPath(path) {
        if (!path) {
            return DEFAULT_AVATAR;
        }
        if (path.startsWith('/unihelper/')) {
            return path;
        }
        if (path.startsWith('/uploads/')) {
            return `/unihelper/public${path}`;
        }
        return path;
    }

    function reportReasonLabel(reason) {
        return reportReasonLabelMap[String(reason || '').toLowerCase()] || 'Other';
    }

    function accountStateLabel(user) {
        if (user.source === 'banned') {
            return 'Banned';
        }
        if (user.source === 'deleted') {
            return 'Deleted';
        }
        return 'Active';
    }

    function normalizeUserList(list, source) {
        if (!Array.isArray(list)) {
            return [];
        }

        return list.map((raw) => ({
            id: Number.parseInt(raw.id, 10),
            firstName: raw.firstName || '',
            lastName: raw.lastName || '',
            email: raw.email || '',
            phone: raw.phone || '',
            role: raw.role || 'role-applicant',
            alYear: raw.alYear || null,
            university: raw.university || null,
            universityName: raw.universityName || 'Not set',
            major: raw.major || null,
            profileRole: raw.profileRole || null,
            profilePicture: raw.profilePicture || '/uploads/profilePictures/default-pfp.png',
            createdAt: raw.createdAt || null,
            archivedAt: raw.archivedAt || null,
            public: Number(raw.public) || 0,
            moderator: Number(raw.moderator) || 0,
            source
        })).filter((user) => Number.isInteger(user.id) && user.id > 0);
    }

    function normalizeReportList(list) {
        if (!Array.isArray(list)) {
            return [];
        }

        return list.map((raw) => ({
            reportId: Number.parseInt(raw.reportId, 10),
            reporterUserId: Number.parseInt(raw.reporterUserId, 10),
            reportedUserId: Number.parseInt(raw.reportedUserId, 10),
            reporterName: raw.reporterName || '',
            reporterAvatar: raw.reporterAvatar || '/uploads/profilePictures/default-pfp.png',
            reportedName: raw.reportedName || '',
            reportedAvatar: raw.reportedAvatar || '/uploads/profilePictures/default-pfp.png',
            reason: raw.reason || 'other',
            details: raw.details || '',
            createdAt: raw.createdAt || null
        })).filter((report) => Number.isInteger(report.reportId) && report.reportId > 0);
    }

    function mapSummary(rawSummary) {
        return {
            totalUsers: Number(rawSummary?.totalUsers) || 0,
            pendingReports: Number(rawSummary?.pendingReports) || 0,
            bannedAccounts: Number(rawSummary?.bannedAccounts) || 0,
            deletedUsers: Number(rawSummary?.deletedUsers) || 0
        };
    }

    function normalizePaginationPayload(rawPayload) {
        const items = Array.isArray(rawPayload?.items) ? rawPayload.items : [];
        const total = Number(rawPayload?.total) || 0;
        const limit = Number(rawPayload?.limit) || PAGE_SIZE;
        const offset = Number(rawPayload?.offset) || 0;
        const nextOffset = Number(rawPayload?.nextOffset);

        return {
            items,
            total,
            limit,
            offset,
            nextOffset: Number.isNaN(nextOffset) ? offset + items.length : nextOffset,
            hasMore: Boolean(rawPayload?.hasMore)
        };
    }

    async function apiRequest(action, method = 'GET', payload = null) {
        const query = new URLSearchParams({
            controller: API_CONTROLLER,
            action
        });

        const options = {
            method,
            credentials: 'same-origin'
        };

        if (payload && method === 'GET') {
            Object.keys(payload).forEach((key) => {
                const value = payload[key];
                if (value !== undefined && value !== null && value !== '') {
                    query.set(key, String(value));
                }
            });
        }

        if (payload && method !== 'GET') {
            const formData = new FormData();
            Object.keys(payload).forEach((key) => {
                const value = payload[key];
                if (value !== undefined && value !== null && value !== '') {
                    formData.append(key, String(value));
                }
            });
            options.body = formData;
        }

        const response = await fetch(`${API_BASE}?${query.toString()}`, options);

        let result;
        try {
            result = await response.json();
        } catch (error) {
            throw new Error('Invalid server response.');
        }

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Request failed.');
        }

        return result.data ?? null;
    }

    async function loadSummary() {
        const summary = await apiRequest('getSummary', 'GET');
        state.summary = mapSummary(summary);
    }

    function getListAction(filter, isSearch) {
        if (filter === 'all') {
            return isSearch ? 'searchUsers' : 'getAllUsers';
        }

        if (filter === 'reports') {
            return isSearch ? 'searchPendingReports' : 'getPendingReports';
        }

        if (filter === 'banned') {
            return isSearch ? 'searchBannedUsers' : 'getBannedUsers';
        }

        return isSearch ? 'searchDeletedUsers' : 'getDeletedUsers';
    }

    function resetTabState(filter) {
        if (!state.tabs[filter]) {
            return;
        }
        state.tabs[filter] = createTabState();
    }

    function invalidateAllTabStates() {
        resetTabState('all');
        resetTabState('reports');
        resetTabState('banned');
        resetTabState('deleted');
    }

    async function loadTabData(filter, options = { reset: false }) {
        const tabState = state.tabs[filter];
        if (!tabState || tabState.loading) {
            return;
        }

        const shouldReset = Boolean(options.reset);

        if (shouldReset) {
            resetTabState(filter);
        }

        const activeTab = state.tabs[filter];
        if (!shouldReset && activeTab.initialized && !activeTab.hasMore && activeTab.searchQuery === state.searchQuery) {
            return;
        }

        activeTab.loading = true;
        updateLoadMoreUI();

        try {
            const hasSearch = state.searchQuery.length > 0;
            const action = getListAction(filter, hasSearch);
            const payload = {
                limit: PAGE_SIZE,
                offset: shouldReset ? 0 : activeTab.nextOffset,
                q: state.searchQuery
            };

            const rawData = await apiRequest(action, 'GET', payload);
            const page = normalizePaginationPayload(rawData);

            if (filter === 'reports') {
                const reportItems = normalizeReportList(page.items);
                activeTab.items = shouldReset ? reportItems : activeTab.items.concat(reportItems);
            } else {
                const source = filter === 'all' ? 'all' : filter;
                const userItems = normalizeUserList(page.items, source);
                activeTab.items = shouldReset ? userItems : activeTab.items.concat(userItems);
            }

            activeTab.total = page.total;
            activeTab.limit = page.limit;
            activeTab.offset = page.offset;
            activeTab.nextOffset = page.nextOffset;
            activeTab.hasMore = page.hasMore;
            activeTab.initialized = true;
            activeTab.searchQuery = state.searchQuery;
        } finally {
            activeTab.loading = false;
            updateLoadMoreUI();
        }
    }

    function activeTabItems() {
        const tabState = state.tabs[state.activeFilter];
        if (!tabState) {
            return [];
        }
        return tabState.items;
    }

    function findUserById(userId) {
        const numericId = Number.parseInt(String(userId), 10);
        if (Number.isNaN(numericId)) {
            return null;
        }

        const lists = [state.tabs.all.items, state.tabs.banned.items, state.tabs.deleted.items];
        for (const list of lists) {
            const found = list.find((user) => Number(user.id) === numericId);
            if (found) {
                return found;
            }
        }

        return null;
    }

    function updateKpis() {
        const totalEl = document.getElementById('kpi-total-users');
        const reportEl = document.getElementById('kpi-pending-reports');
        const bannedEl = document.getElementById('kpi-banned-accounts');
        const deletedEl = document.getElementById('kpi-deleted-users');

        if (totalEl) {
            totalEl.textContent = String(state.summary.totalUsers);
        }
        if (reportEl) {
            reportEl.textContent = String(state.summary.pendingReports);
        }
        if (bannedEl) {
            bannedEl.textContent = String(state.summary.bannedAccounts);
        }
        if (deletedEl) {
            deletedEl.textContent = String(state.summary.deletedUsers);
        }
    }

    function renderActionButtons(user) {
        if (state.activeFilter === 'banned') {
            return `
                <button class="um-btn-icon success" title="Unban User" aria-label="Unban User" data-action="unban" data-user-id="${user.id}" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="9 12 11 14 15 10"></polyline>
                    </svg>
                </button>
            `;
        }

        if (state.activeFilter === 'deleted') {
            return `
                <button class="um-btn-icon success" title="Restore User" aria-label="Restore User" data-action="restore" data-user-id="${user.id}" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 .49-9"></path>
                    </svg>
                </button>
            `;
        }

        return `
            <button class="um-btn-icon danger" title="Ban User" aria-label="Ban User" data-action="ban" data-user-id="${user.id}" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                </svg>
            </button>
            <button class="um-btn-icon warn" title="Delete User" aria-label="Delete User" data-action="delete" data-user-id="${user.id}" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                    <path d="M10 11v6"></path>
                    <path d="M14 11v6"></path>
                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                </svg>
            </button>
        `;
    }

    function renderTable(userList) {
        tableBody.innerHTML = '';

        if (userList.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5">
                        <div class="um-empty-state">
                            <svg class="um-empty-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <h3 class="um-empty-title">No users found</h3>
                            <p class="um-empty-text">No matching users for this view.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = userList.map((user) => {
            const roleLabel = userRoleLabel(user);
            const displayDate = state.activeFilter === 'all'
                ? formatDate(user.createdAt)
                : formatDate(user.archivedAt || user.createdAt);
            const safeName = escapeHtml(fullName(user));
            const safeEmail = escapeHtml(user.email || '-');
            const safeUniversity = escapeHtml(user.universityName || 'Not set');

            return `
                <tr data-user-id="${user.id}">
                    <td>
                        <div class="um-user-cell">
                            <a class="um-profile-link" href="${profileUrl(user.id)}" target="_blank" rel="noopener noreferrer">
                                <img src="${imgPath(user.profilePicture)}" alt="${safeName}" class="um-avatar" loading="lazy" onerror="this.src='${DEFAULT_AVATAR}'">
                                <div class="um-user-info">
                                    <span class="um-user-name">${safeName}</span>
                                    <span class="um-user-index">${safeEmail}</span>
                                </div>
                            </a>
                        </div>
                    </td>
                    <td><span style="font-weight:500; color: var(--foreground);">${safeUniversity}</span></td>
                    <td><span class="um-badge ${roleClass(roleLabel)}">${escapeHtml(roleLabel)}</span></td>
                    <td style="color: var(--muted-foreground); font-weight: 500;">${displayDate}</td>
                    <td>
                        <div class="um-actions">
                            ${renderActionButtons(user)}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderReports(reportList) {
        if (reportList.length === 0) {
            reportsList.innerHTML = `
                <div class="um-empty-state">
                    <svg class="um-empty-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <h3 class="um-empty-title">No pending reports</h3>
                    <p class="um-empty-text">There are no user reports in the queue.</p>
                </div>
            `;
            return;
        }

        reportsList.innerHTML = reportList.map((report) => {
            const reporterName = escapeHtml(report.reporterName || `User #${report.reporterUserId}`);
            const reportedName = escapeHtml(report.reportedName || `User #${report.reportedUserId}`);
            const reasonLabel = escapeHtml(reportReasonLabel(report.reason));
            const details = escapeHtml((report.details || '').trim() || '-');

            return `
                <article class="um-report-card" data-report-id="${report.reportId}">
                    <div class="um-report-top">
                        <a class="um-report-user-link" href="${profileUrl(report.reporterUserId)}" target="_blank" rel="noopener noreferrer">
                            <img class="um-report-avatar" src="${imgPath(report.reporterAvatar)}" alt="${reporterName}" onerror="this.src='${DEFAULT_AVATAR}'">
                            <div class="um-report-reporter-meta">
                                <span class="um-report-reporter-name">${reporterName}</span>
                                <span class="um-report-time">${formatDate(report.createdAt, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                            </div>
                        </a>
                        <span class="um-report-badge">Pending</span>
                    </div>

                    <div class="um-report-body">
                        <p class="um-report-line"><span class="um-report-label">Reported User</span><a class="um-report-inline-link" href="${profileUrl(report.reportedUserId)}" target="_blank" rel="noopener noreferrer">${reportedName}</a></p>
                        <p class="um-report-line"><span class="um-report-label">Reason</span><span>${reasonLabel}</span></p>
                        <p class="um-report-line"><span class="um-report-label">Details</span><span>${details}</span></p>
                    </div>

                    <div class="um-report-actions-row">
                        <button class="um-report-action-btn um-report-ignore" data-action="ignore" data-report-id="${report.reportId}" type="button">Ignore</button>
                        <button class="um-report-action-btn um-report-ban" data-action="ban" data-report-id="${report.reportId}" data-user-id="${report.reportedUserId}" type="button">Ban User</button>
                    </div>
                </article>
            `;
        }).join('');
    }

    function refreshTabsUI() {
        kpiTabs.forEach((tab) => {
            const isActive = tab.dataset.filter === state.activeFilter;
            tab.classList.toggle('active', isActive);
            tab.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function updateLoadMoreUI() {
        if (!loadMoreWrap || !loadMoreBtn) {
            return;
        }

        const tabState = state.tabs[state.activeFilter];
        if (!tabState) {
            loadMoreWrap.hidden = true;
            return;
        }

        const show = tabState.initialized && tabState.hasMore;
        loadMoreWrap.hidden = !show;

        if (show) {
            loadMoreBtn.disabled = tabState.loading;
            loadMoreBtn.textContent = tabState.loading ? 'Loading...' : 'Load more';
        }
    }

    function syncView() {
        refreshTabsUI();
        updateKpis();
        updateLoadMoreUI();

        if (state.activeFilter === 'reports') {
            usersView.hidden = true;
            reportsView.hidden = false;
            if (listHeading) {
                listHeading.hidden = true;
            }
            renderReports(activeTabItems());
            return;
        }

        usersView.hidden = false;
        reportsView.hidden = true;

        if (listHeading) {
            listHeading.hidden = false;
        }

        if (listTitle) {
            if (state.activeFilter === 'banned') {
                listTitle.textContent = 'Banned Users';
            } else if (state.activeFilter === 'deleted') {
                listTitle.textContent = 'Deleted Users';
            } else {
                listTitle.textContent = 'All Users';
            }
        }

        if (dateColumnTitle) {
            if (state.activeFilter === 'banned') {
                dateColumnTitle.textContent = 'Banned Date';
            } else if (state.activeFilter === 'deleted') {
                dateColumnTitle.textContent = 'Deleted Date';
            } else {
                dateColumnTitle.textContent = 'Joined Date';
            }
        }

        renderTable(activeTabItems());
    }

    async function refreshAfterMutation(nextFilter = state.activeFilter) {
        state.activeFilter = nextFilter;
        invalidateAllTabStates();

        await Promise.all([
            loadSummary(),
            loadTabData(nextFilter, { reset: true })
        ]);

        syncView();
    }

    async function handleUserAction(action, userId) {
        const numericUserId = Number.parseInt(String(userId), 10);
        if (Number.isNaN(numericUserId)) {
            return;
        }

        const user = findUserById(numericUserId);
        const userName = user ? fullName(user) : `User #${numericUserId}`;

        if (action === 'ban') {
            const confirmed = await window.confirm(`Ban ${userName}? This will archive the user and lock login access.`);
            if (!confirmed) {
                return;
            }

            await apiRequest('banUser', 'POST', { user_id: numericUserId });
            showMessage('User banned successfully.', 'success');
            await refreshAfterMutation('banned');
            return;
        }

        if (action === 'delete') {
            const confirmed = await window.confirm(`Delete ${userName}? This will move the user into deleted accounts.`);
            if (!confirmed) {
                return;
            }

            await apiRequest('deleteUser', 'POST', { user_id: numericUserId });
            showMessage('User moved to deleted accounts.', 'success');
            await refreshAfterMutation('deleted');
            return;
        }

        if (action === 'unban') {
            const confirmed = await window.confirm(`Unban ${userName}? This will restore the original account data.`);
            if (!confirmed) {
                return;
            }

            await apiRequest('unbanUser', 'POST', { user_id: numericUserId });
            showMessage('User unbanned successfully.', 'success');
            await refreshAfterMutation('all');
            return;
        }

        if (action === 'restore') {
            const confirmed = await window.confirm(`Restore ${userName}? This will reactivate the deleted account data.`);
            if (!confirmed) {
                return;
            }

            await apiRequest('restoreDeletedUser', 'POST', { user_id: numericUserId });
            showMessage('Deleted user restored successfully.', 'success');
            await refreshAfterMutation('all');
        }
    }

    async function ignoreReport(reportId) {
        await apiRequest('ignoreReport', 'POST', { report_id: reportId });
        showMessage('Report ignored and removed from queue.', 'success');
        await refreshAfterMutation('reports');
    }

    async function banFromReport(reportId, reportedUserId) {
        const report = state.tabs.reports.items.find((item) => Number(item.reportId) === Number(reportId));
        const targetName = report ? (report.reportedName || `User #${reportedUserId}`) : `User #${reportedUserId}`;

        const confirmed = await window.confirm(`Ban ${targetName}? This will resolve the report and lock account access.`);
        if (!confirmed) {
            return;
        }

        await apiRequest('banUser', 'POST', {
            user_id: reportedUserId,
            report_id: reportId
        });

        showMessage('User banned and report resolved.', 'success');
        await refreshAfterMutation('banned');
    }

    async function runServerSideSearch() {
        try {
            await loadTabData(state.activeFilter, { reset: true });
            syncView();
        } catch (error) {
            showMessage(error.message || 'Failed to search users.', 'error');
        }
    }

    function scheduleServerSideSearch() {
        if (state.searchTimer) {
            clearTimeout(state.searchTimer);
        }

        state.searchTimer = setTimeout(() => {
            void runServerSideSearch();
        }, SEARCH_DEBOUNCE_MS);
    }

    tableBody.addEventListener('click', async (event) => {
        const actionBtn = event.target.closest('.um-btn-icon[data-action]');
        if (actionBtn) {
            event.stopPropagation();
            const action = actionBtn.dataset.action;
            const userId = Number.parseInt(actionBtn.dataset.userId || '', 10);
            if (!action || Number.isNaN(userId)) {
                return;
            }

            try {
                await handleUserAction(action, userId);
            } catch (error) {
                showMessage(error.message || 'Failed to perform user action.', 'error');
            }
            return;
        }

        if (event.target.closest('a')) {
            return;
        }

        const row = event.target.closest('tr[data-user-id]');
        if (!row) {
            return;
        }

        const userId = Number.parseInt(row.dataset.userId || '', 10);
        if (Number.isNaN(userId)) {
            return;
        }

        const user = findUserById(userId);
        if (user) {
            openPanel(user);
        }
    });

    reportsList.addEventListener('click', async (event) => {
        if (event.target.closest('a')) {
            return;
        }

        const actionBtn = event.target.closest('.um-report-action-btn');
        if (!actionBtn) {
            return;
        }

        const action = actionBtn.dataset.action;
        const reportId = Number.parseInt(actionBtn.dataset.reportId || '', 10);
        if (!action || Number.isNaN(reportId)) {
            return;
        }

        try {
            if (action === 'ignore') {
                await ignoreReport(reportId);
                return;
            }

            if (action === 'ban') {
                const userId = Number.parseInt(actionBtn.dataset.userId || '', 10);
                if (!Number.isNaN(userId)) {
                    await banFromReport(reportId, userId);
                }
            }
        } catch (error) {
            showMessage(error.message || 'Failed to process report action.', 'error');
        }
    });

    searchInput.addEventListener('input', (event) => {
        state.searchQuery = String(event.target.value || '').trim();
        scheduleServerSideSearch();
    });

    kpiTabs.forEach((tab) => {
        tab.addEventListener('click', async () => {
            const nextFilter = tab.dataset.filter || 'all';
            state.activeFilter = nextFilter;
            refreshTabsUI();

            const tabState = state.tabs[nextFilter];
            const shouldReset = !tabState.initialized || tabState.searchQuery !== state.searchQuery;

            try {
                if (shouldReset) {
                    await loadTabData(nextFilter, { reset: true });
                }
            } catch (error) {
                showMessage(error.message || 'Failed to load tab data.', 'error');
            }

            syncView();
        });
    });

    loadMoreBtn?.addEventListener('click', async () => {
        try {
            await loadTabData(state.activeFilter, { reset: false });
            syncView();
        } catch (error) {
            showMessage(error.message || 'Failed to load more records.', 'error');
        }
    });

    if (moderationBtn) {
        moderationBtn.addEventListener('click', () => {
            window.location.href = '/unihelper/moderation';
        });
    }

    function openPanel(user) {
        const roleLabel = userRoleLabel(user);
        const profilePic = imgPath(user.profilePicture);
        const joined = formatDate(user.createdAt, { year: 'numeric', month: 'long', day: 'numeric' });
        const accountState = accountStateLabel(user);
        const archiveDate = user.archivedAt
            ? formatDate(user.archivedAt, { year: 'numeric', month: 'long', day: 'numeric' })
            : '-';

        slideBodyContent.innerHTML = `
            <div style="text-align: center; margin-bottom: 1rem;">
                <div style="position: relative; display: inline-block; margin-bottom: 1rem;">
                    <a href="${profileUrl(user.id)}" target="_blank" rel="noopener noreferrer" style="display: inline-block;">
                        <img src="${profilePic}" alt="${escapeHtml(fullName(user))}" style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid var(--card); box-shadow: 0 8px 24px rgba(0,0,0,0.15); background: var(--border);" onerror="this.src='${DEFAULT_AVATAR}'">
                    </a>
                </div>

                <h2 style="margin: 0 0 0.5rem 0; color: var(--foreground); font-size: 1.6rem; font-weight: 700;">${escapeHtml(fullName(user))}</h2>
                <div style="display: flex; gap: 0.75rem; justify-content: center; align-items: center; flex-wrap: wrap;">
                    <span class="um-badge ${roleClass(roleLabel)}">${escapeHtml(roleLabel)}</span>
                    <span class="um-badge um-badge-user">${escapeHtml(accountState)}</span>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <div style="background: var(--key); padding: 1.25rem; border-radius: 1rem; border: 1px solid var(--border);">
                    <h4 style="margin: 0 0 1rem 0; color: var(--muted-foreground); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">Identity Information</h4>
                    <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                            <span style="font-size: 0.9rem; color: var(--muted-foreground);">User ID</span>
                            <span style="color: var(--foreground); font-weight: 600; font-family: monospace;">#${user.id}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; gap: 1rem;">
                            <span style="font-size: 0.9rem; color: var(--muted-foreground);">Email</span>
                            <span style="color: var(--primary); font-weight: 500; font-size: 0.9rem; text-align: right;">${escapeHtml(user.email || '-')}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                            <span style="font-size: 0.9rem; color: var(--muted-foreground);">Phone</span>
                            <span style="color: var(--foreground); font-weight: 500; font-size: 0.9rem;">${escapeHtml(user.phone || 'Not set')}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; gap: 1rem;">
                            <span style="font-size: 0.9rem; color: var(--muted-foreground);">University</span>
                            <span style="color: var(--foreground); font-weight: 500; font-size: 0.9rem; text-align: right;">${escapeHtml(user.universityName || 'Not set')}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                            <span style="font-size: 0.9rem; color: var(--muted-foreground);">Joined on</span>
                            <span style="color: var(--foreground); font-weight: 500; font-size: 0.9rem;">${joined}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.9rem; color: var(--muted-foreground);">Archived on</span>
                            <span style="color: var(--foreground); font-weight: 500; font-size: 0.9rem;">${archiveDate}</span>
                        </div>
                    </div>
                </div>

                <a href="${profileUrl(user.id)}" target="_blank" rel="noopener noreferrer" style="display:inline-flex; align-items:center; justify-content:center; gap:0.5rem; text-decoration:none; padding:0.75rem 1rem; border-radius:0.75rem; border:1px solid var(--border); background:var(--card); color:var(--foreground); font-weight:600;">
                    Open Full Profile
                </a>
            </div>
        `;

        slidePanel.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closePanel() {
        slidePanel.classList.remove('open');
        document.body.style.overflow = '';
    }

    closePanelBtn?.addEventListener('click', closePanel);
    panelOverlay?.addEventListener('click', closePanel);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && slidePanel.classList.contains('open')) {
            closePanel();
        }
    });

    async function initialize() {
        updateKpis();
        syncView();

        try {
            await Promise.all([
                loadSummary(),
                loadTabData('all', { reset: true })
            ]);
        } catch (error) {
            showMessage(error.message || 'Failed to load user management data.', 'error');
        }

        syncView();
    }

    void initialize();
});