(function () {
    'use strict';

    const root = document.getElementById('sysOverviewRoot');
    if (!root) {
        return;
    }

    const API_PREFIX = '/unihelper/api?controller=AdminOverviewController&action=';

    const state = {
        window: 'active',
        preset: '30d',
        from: null,
        to: null,
        page: 1,
        limit: 10,
        search: '',
        role: '',
        roleChart: null,
        activityChart: null,
        requestToken: 0,
        totalPages: 1,
        previousBodyOverflow: '',
        previousMainOverflow: ''
    };

    const els = {
        stateText: document.getElementById('sysOverviewState'),
        windowButtons: Array.from(document.querySelectorAll('.sys-window-btn')),
        rangePreset: document.getElementById('sysRangePreset'),
        fromDate: document.getElementById('sysFromDate'),
        toDate: document.getElementById('sysToDate'),
        applyFilters: document.getElementById('sysApplyFilters'),
        resetFilters: document.getElementById('sysResetFilters'),
        kpiUsers: document.getElementById('sysKpiUsers'),
        kpiPosts: document.getElementById('sysKpiPosts'),
        kpiQuestions: document.getElementById('sysKpiQuestions'),
        kpiAnswers: document.getElementById('sysKpiAnswers'),
        kpiSessions: document.getElementById('sysKpiSessions'),
        kpiNotifications: document.getElementById('sysKpiNotifications'),
        kpiUnreadNotifications: document.getElementById('sysKpiUnreadNotifications'),
        kpiPendingConnections: document.getElementById('sysKpiPendingConnections'),
        kpiAcceptedConnections: document.getElementById('sysKpiAcceptedConnections'),
        topUsersList: document.getElementById('sysTopUsersList'),
        roleChart: document.getElementById('sysRoleChart'),
        activityChart: document.getElementById('sysActivityChart'),
        userSearch: document.getElementById('sysUserSearch'),
        roleFilter: document.getElementById('sysRoleFilter'),
        searchBtn: document.getElementById('sysSearchBtn'),
        tableBody: document.getElementById('sysUserTableBody'),
        prevPage: document.getElementById('sysPrevPage'),
        nextPage: document.getElementById('sysNextPage'),
        paginationText: document.getElementById('sysPaginationText'),
        detailModal: document.getElementById('sysDetailModal'),
        mainContent: document.querySelector('.main-content'),
        detailPanel: document.querySelector('#sysDetailModal .sys-detail-panel'),
        detailGrid: document.querySelector('#sysDetailModal .sys-detail-grid'),
        detailBackdrop: document.getElementById('sysDetailBackdrop'),
        detailClose: document.getElementById('sysDetailClose'),
        detailSubtitle: document.getElementById('sysDetailSubtitle'),
        detailMetrics: document.getElementById('sysDetailMetrics'),
        detailPosts: document.getElementById('sysDetailPosts'),
        detailQuestions: document.getElementById('sysDetailQuestions'),
        detailAnswers: document.getElementById('sysDetailAnswers'),
        detailSessions: document.getElementById('sysDetailSessions'),
        detailConnections: document.getElementById('sysDetailConnections'),
        detailNotifications: document.getElementById('sysDetailNotifications')
    };

    // Keep the detail modal in the global page layer so it is never clipped by dashboard containers.
    if (els.detailModal && els.detailModal.parentElement && els.detailModal.parentElement !== document.body) {
        els.detailModal.parentElement.removeChild(els.detailModal);
        document.body.appendChild(els.detailModal);
    }

    bindEvents();
    applyPresetRange();
    loadDashboardData();

    function bindEvents() {
        els.windowButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const nextWindow = button.getAttribute('data-window') || 'active';
                if (state.window === nextWindow) {
                    return;
                }

                state.window = nextWindow;
                state.page = 1;
                syncWindowButtons();
                loadDashboardData();
            });
        });

        els.rangePreset.addEventListener('change', function () {
            state.preset = String(els.rangePreset.value || 'all');
            applyPresetRange();
        });

        els.applyFilters.addEventListener('click', function () {
            if (!readDateFilterState()) {
                return;
            }

            state.page = 1;
            loadDashboardData();
        });

        els.resetFilters.addEventListener('click', function () {
            state.preset = '30d';
            els.rangePreset.value = '30d';
            applyPresetRange();
            state.page = 1;
            state.search = '';
            state.role = '';
            els.userSearch.value = '';
            els.roleFilter.value = '';
            loadDashboardData();
        });

        els.searchBtn.addEventListener('click', function () {
            state.search = String(els.userSearch.value || '').trim();
            state.role = String(els.roleFilter.value || '').trim();
            state.page = 1;
            loadUserActivityList();
        });

        els.userSearch.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                els.searchBtn.click();
            }
        });

        els.prevPage.addEventListener('click', function () {
            if (state.page <= 1) {
                return;
            }

            state.page -= 1;
            loadUserActivityList();
        });

        els.nextPage.addEventListener('click', function () {
            if (state.page >= state.totalPages) {
                return;
            }

            state.page += 1;
            loadUserActivityList();
        });

        els.tableBody.addEventListener('click', function (event) {
            const detailButton = event.target.closest('[data-user-detail-id]');
            if (!detailButton) {
                return;
            }

            const userId = parseInt(detailButton.getAttribute('data-user-detail-id') || '0', 10);
            if (!userId) {
                return;
            }

            loadUserActivityDetail(userId);
        });

        if (els.detailClose) {
            els.detailClose.addEventListener('click', closeDetailModal);
        }

        if (els.detailBackdrop) {
            els.detailBackdrop.addEventListener('click', closeDetailModal);
        }

        els.detailModal.addEventListener('click', function (event) {
            if (event.target === els.detailModal || event.target === els.detailBackdrop) {
                closeDetailModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !els.detailModal.hidden) {
                closeDetailModal();
            }
        });
    }

    function syncWindowButtons() {
        els.windowButtons.forEach(function (button) {
            const isActive = button.getAttribute('data-window') === state.window;
            button.classList.toggle('active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    function setStateText(text) {
        els.stateText.textContent = text;
    }

    function applyPresetRange() {
        const custom = state.preset === 'custom';
        els.fromDate.disabled = !custom;
        els.toDate.disabled = !custom;

        if (custom) {
            return;
        }

        const range = computePresetRange(state.preset);
        els.fromDate.value = range.from || '';
        els.toDate.value = range.to || '';

        state.from = range.from;
        state.to = range.to;
    }

    function readDateFilterState() {
        if (state.preset !== 'custom') {
            const range = computePresetRange(state.preset);
            state.from = range.from;
            state.to = range.to;
            return true;
        }

        const fromValue = String(els.fromDate.value || '').trim();
        const toValue = String(els.toDate.value || '').trim();

        if ((fromValue && !toValue) || (!fromValue && toValue)) {
            if (typeof showToast === 'function') {
                showToast('Provide both custom From and To dates.', 'error');
            }
            return false;
        }

        if (fromValue && toValue && fromValue > toValue) {
            if (typeof showToast === 'function') {
                showToast('From date cannot be greater than To date.', 'error');
            }
            return false;
        }

        state.from = fromValue || null;
        state.to = toValue || null;
        return true;
    }

    function computePresetRange(preset) {
        if (preset === 'all') {
            return { from: null, to: null };
        }

        const dayMap = {
            '7d': 7,
            '30d': 30,
            '90d': 90
        };

        const days = dayMap[preset];
        if (!days) {
            return { from: null, to: null };
        }

        const now = new Date();
        const end = formatDateForInput(now);

        const startDate = new Date(now);
        startDate.setDate(startDate.getDate() - (days - 1));
        const start = formatDateForInput(startDate);

        return { from: start, to: end };
    }

    function formatDateForInput(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    async function loadDashboardData() {
        const token = ++state.requestToken;
        setStateText('Loading analytics...');

        try {
            await Promise.all([
                loadOverview(token),
                loadUserActivityList(token)
            ]);

            if (token !== state.requestToken) {
                return;
            }

            const label = state.window === 'active' ? 'Active window' : 'Deleted/Expired window';
            setStateText(label + ' loaded');
        } catch (error) {
            if (token !== state.requestToken) {
                return;
            }

            setStateText('Failed to load analytics');
            if (typeof showToast === 'function') {
                showToast('Failed to load system overview data.', 'error');
            }
        }
    }

    async function loadOverview(token) {
        const params = buildCommonParams();
        const payload = await fetchJson('getSystemOverview', params);

        if (token && token !== state.requestToken) {
            return;
        }

        if (!payload || payload.success !== true || !payload.data) {
            throw new Error('Overview payload is invalid.');
        }

        renderSummary(payload.data.summary || {});
        renderTopUsers(payload.data.top_users || []);
        renderRoleChart(payload.data.role_distribution || []);
        renderActivityChart(payload.data.activity_distribution || []);
    }

    async function loadUserActivityList(token) {
        const params = buildCommonParams();
        params.page = String(state.page);
        params.limit = String(state.limit);

        if (state.search) {
            params.q = state.search;
        }

        if (state.role) {
            params.role = state.role;
        }

        const payload = await fetchJson('getUserActivityList', params);

        if (token && token !== state.requestToken) {
            return;
        }

        if (!payload || payload.success !== true || !payload.data || !payload.data.pagination) {
            throw new Error('User list payload is invalid.');
        }

        const rows = Array.isArray(payload.data.items) ? payload.data.items : [];
        const pagination = payload.data.pagination;

        state.page = Number(pagination.page || 1);
        state.totalPages = Number(pagination.total_pages || 1);

        renderUserTable(rows);
        renderPagination(pagination);
    }

    async function loadUserActivityDetail(userId) {
        const params = buildCommonParams();
        params.user_id = String(userId);

        try {
            setStateText('Loading user detail...');
            const payload = await fetchJson('getUserActivityDetail', params);

            if (!payload || payload.success !== true || !payload.data) {
                throw new Error('User detail payload is invalid.');
            }

            renderDetailModal(payload.data);
            setStateText('User detail loaded');
        } catch (error) {
            closeDetailModal();
            if (typeof showToast === 'function') {
                showToast('Failed to load user detail.', 'error');
            }
            setStateText('Failed to load user detail');
        }
    }

    function buildCommonParams() {
        const params = {
            window: state.window
        };

        if (state.from) {
            params.from = state.from;
        }

        if (state.to) {
            params.to = state.to;
        }

        return params;
    }

    async function fetchJson(action, params) {
        const query = new URLSearchParams(params || {}).toString();
        const url = API_PREFIX + action + (query ? '&' + query : '');

        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error('Request failed with status ' + response.status);
        }

        return response.json();
    }

    function renderSummary(summary) {
        els.kpiUsers.textContent = formatCount(summary.total_users);
        els.kpiPosts.textContent = formatCount(summary.total_posts);
        els.kpiQuestions.textContent = formatCount(summary.total_questions);
        els.kpiAnswers.textContent = formatCount(summary.total_answers);
        els.kpiSessions.textContent = formatCount(summary.total_sessions);
        els.kpiNotifications.textContent = formatCount(summary.total_notifications);
        els.kpiUnreadNotifications.textContent = 'Unread: ' + formatCount(summary.unread_notifications);
        els.kpiPendingConnections.textContent = formatCount(summary.pending_connections);
        els.kpiAcceptedConnections.textContent = formatCount(summary.accepted_connections);
    }

    function renderTopUsers(items) {
        if (!Array.isArray(items) || items.length === 0) {
            els.topUsersList.innerHTML = '<div class="sys-empty">No user activity found for this range.</div>';
            return;
        }

        const html = items.map(function (item) {
            const fullName = escapeHtml((item.first_name || '') + ' ' + (item.last_name || ''));
            return [
                '<article class="sys-top-user-item">',
                '<p class="sys-top-user-name">' + fullName.trim() + '</p>',
                '<p class="sys-top-user-role">' + escapeHtml(roleLabel(item.role)) + '</p>',
                '<p class="sys-top-user-score">Score: ' + formatCount(item.activity_score) + '</p>',
                '</article>'
            ].join('');
        }).join('');

        els.topUsersList.innerHTML = html;
    }

    function renderRoleChart(distribution) {
        const labels = [];
        const values = [];

        (Array.isArray(distribution) ? distribution : []).forEach(function (item) {
            labels.push(roleLabel(item.role));
            values.push(Number(item.count || 0));
        });

        if (state.roleChart) {
            state.roleChart.destroy();
            state.roleChart = null;
        }

        if (!window.Chart) {
            els.roleChart.replaceWith(createChartFallback('Chart.js is unavailable.'));
            return;
        }

        state.roleChart = new window.Chart(els.roleChart, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: ['#2ecc71', '#1f9bff', '#f39c12', '#e74c3c', '#8e44ad', '#16a085']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    function renderActivityChart(distribution) {
        const labels = [];
        const values = [];

        (Array.isArray(distribution) ? distribution : []).forEach(function (item) {
            labels.push(String(item.label || 'Unknown'));
            values.push(Number(item.count || 0));
        });

        if (state.activityChart) {
            state.activityChart.destroy();
            state.activityChart = null;
        }

        if (!window.Chart) {
            els.activityChart.replaceWith(createChartFallback('Chart.js is unavailable.'));
            return;
        }

        state.activityChart = new window.Chart(els.activityChart, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: ['#00aaff', '#2ecc71', '#f39c12', '#ff6a84', '#8e44ad', '#16a085']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    function createChartFallback(message) {
        const fallback = document.createElement('div');
        fallback.className = 'sys-empty';
        fallback.textContent = message;
        return fallback;
    }

    function renderUserTable(rows) {
        if (!Array.isArray(rows) || rows.length === 0) {
            els.tableBody.innerHTML = '<tr><td colspan="12" class="sys-empty">No users found for current filters.</td></tr>';
            return;
        }

        els.tableBody.innerHTML = rows.map(function (item) {
            const userName = escapeHtml((item.first_name || '') + ' ' + (item.last_name || '')).trim();
            const userId = Number(item.id || 0);
            const pendingCombined = formatCount(item.pending_sent_count) + '/' + formatCount(item.pending_received_count);

            return [
                '<tr>',
                '<td><strong>' + userName + '</strong><p class="sys-user-meta">ID: ' + formatCount(item.id) + ' | Joined: ' + formatDate(item.created_at) + '</p></td>',
                '<td><span class="sys-pill">' + escapeHtml(roleLabel(item.role)) + '</span></td>',
                '<td><p class="sys-user-meta">' + escapeHtml(item.email || '-') + '</p><p class="sys-user-meta">' + escapeHtml(item.phone || '-') + '</p></td>',
                '<td>' + formatCount(item.posts_count) + '</td>',
                '<td>' + formatCount(item.questions_count) + '</td>',
                '<td>' + formatCount(item.answers_count) + '</td>',
                '<td>' + formatCount(item.sessions_count) + '</td>',
                '<td>' + pendingCombined + '</td>',
                '<td>' + formatCount(item.accepted_connections_count) + '</td>',
                '<td>' + formatCount(item.notifications_count) + '<p class="sys-user-meta">Unread ' + formatCount(item.notifications_unread_count) + '</p></td>',
                '<td><strong>' + formatCount(item.activity_score) + '</strong></td>',
                '<td><button type="button" class="sys-btn sys-btn-primary" data-user-detail-id="' + userId + '">View</button></td>',
                '</tr>'
            ].join('');
        }).join('');
    }

    function renderPagination(pagination) {
        const page = Number(pagination.page || 1);
        const totalPages = Number(pagination.total_pages || 1);
        const total = Number(pagination.total || 0);

        els.paginationText.textContent = 'Page ' + page + ' of ' + totalPages + ' | Total users: ' + formatCount(total);
        els.prevPage.disabled = page <= 1;
        els.nextPage.disabled = page >= totalPages;
    }

    function renderDetailModal(payload) {
        const user = payload.user || {};
        const metrics = payload.metrics || {};
        const activity = payload.activity || {};

        const fullName = ((user.first_name || '') + ' ' + (user.last_name || '')).trim();
        const subtitleParts = [
            fullName || 'Unknown User',
            roleLabel(user.role),
            user.email || '-',
            user.phone || '-'
        ];

        els.detailSubtitle.textContent = subtitleParts.join(' | ');

        const metricItems = [
            ['Posts', metrics.posts_count],
            ['Questions', metrics.questions_count],
            ['Answers', metrics.answers_count],
            ['Sessions', metrics.sessions_count],
            ['Pending Sent', metrics.pending_sent_count],
            ['Pending Received', metrics.pending_received_count],
            ['Accepted', metrics.accepted_connections_count],
            ['Notifications', metrics.notifications_count],
            ['Unread Notifications', metrics.notifications_unread_count]
        ];

        els.detailMetrics.innerHTML = metricItems.map(function (pair) {
            return '<span class="sys-pill">' + escapeHtml(pair[0]) + ': ' + formatCount(pair[1]) + '</span>';
        }).join('');

        renderDetailList(els.detailPosts, activity.feed_posts, function (item) {
            const title = escapeHtml(item.title || 'Untitled post');
            const status = Number(item.is_deleted || 0) === 1 || item.deleted_at ? 'archived' : 'active';
            return [
                '<p class="sys-detail-item-title">' + title + '</p>',
                '<p class="sys-detail-item-sub">Type: ' + escapeHtml(item.post_type || '-') + ' | State: ' + status + ' | Created: ' + formatDate(item.created_at) + '</p>'
            ].join('');
        });

        renderDetailList(els.detailQuestions, activity.questions, function (item) {
            return [
                '<p class="sys-detail-item-title">' + escapeHtml(item.question || 'Untitled question') + '</p>',
                '<p class="sys-detail-item-sub">Status: ' + escapeHtml(item.status || '-') + ' | Created: ' + formatDate(item.added_time) + '</p>'
            ].join('');
        });

        renderDetailList(els.detailAnswers, activity.answers, function (item) {
            return [
                '<p class="sys-detail-item-title">Answer #' + formatCount(item.a_id) + ' on Question #' + formatCount(item.q_id) + '</p>',
                '<p class="sys-detail-item-sub">' + escapeHtml(item.text_excerpt || '') + '</p>',
                '<p class="sys-detail-item-sub">Status: ' + escapeHtml(item.status || '-') + ' | Created: ' + formatDate(item.added_time) + '</p>'
            ].join('');
        });

        renderDetailList(els.detailSessions, activity.sessions, function (item) {
            const sessionState = escapeHtml(item.session_state || '-');
            const schedule = [item.date || '-', item.time || '-'].join(' ');
            return [
                '<p class="sys-detail-item-title">' + escapeHtml(item.title || 'Untitled session') + '</p>',
                '<p class="sys-detail-item-sub">Subject: ' + escapeHtml(item.subject || '-') + ' | Audience: ' + escapeHtml(item.audience || '-') + '</p>',
                '<p class="sys-detail-item-sub">Schedule: ' + escapeHtml(schedule) + ' | State: ' + sessionState + '</p>'
            ].join('');
        });

        renderDetailList(els.detailConnections, activity.connections, function (item) {
            const counterpart = item.counterpart_name || ('User #' + formatCount(item.counterpart_id));
            return [
                '<p class="sys-detail-item-title">' + escapeHtml(item.direction || '-') + ' | ' + escapeHtml(item.status || '-') + '</p>',
                '<p class="sys-detail-item-sub">Counterpart: ' + escapeHtml(counterpart) + ' (' + escapeHtml(roleLabel(item.counterpart_role)) + ')</p>',
                '<p class="sys-detail-item-sub">Created: ' + formatDate(item.created_at) + '</p>'
            ].join('');
        });

        renderDetailList(els.detailNotifications, activity.notifications, function (item) {
            const readState = Number(item.is_read || 0) === 1 ? 'read' : 'unread';
            return [
                '<p class="sys-detail-item-title">' + escapeHtml(item.module || 'other') + ' | ' + readState + '</p>',
                '<p class="sys-detail-item-sub">' + escapeHtml(item.message || '') + '</p>',
                '<p class="sys-detail-item-sub">Created: ' + formatDate(item.created_at) + '</p>'
            ].join('');
        });

        openDetailModal();

        if (els.detailGrid) {
            els.detailGrid.scrollTop = 0;
        }

        if (els.detailClose) {
            setTimeout(function () {
                els.detailClose.focus();
            }, 40);
        }
    }

    function renderDetailList(container, items, renderItem) {
        if (!Array.isArray(items) || items.length === 0) {
            container.innerHTML = '<div class="sys-empty">No records in this window and date range.</div>';
            return;
        }

        container.innerHTML = items.map(function (item) {
            return '<article class="sys-detail-item">' + renderItem(item) + '</article>';
        }).join('');
    }

    function closeDetailModal() {
        if (els.detailModal.hidden) {
            return;
        }

        els.detailModal.hidden = true;
        els.detailModal.classList.add('is-hidden');

        document.body.style.overflow = state.previousBodyOverflow || '';
        state.previousBodyOverflow = '';

        if (els.mainContent) {
            els.mainContent.style.overflow = state.previousMainOverflow || '';
            state.previousMainOverflow = '';
        }
    }

    function openDetailModal() {
        if (!els.detailModal) {
            return;
        }

        if (els.detailModal.parentElement && els.detailModal.parentElement !== document.body) {
            els.detailModal.parentElement.removeChild(els.detailModal);
            document.body.appendChild(els.detailModal);
        }

        if (els.detailModal.hidden) {
            state.previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';

            if (els.mainContent) {
                state.previousMainOverflow = els.mainContent.style.overflow;
                els.mainContent.style.overflow = 'hidden';
            }
        }

        els.detailModal.classList.remove('is-hidden');
        els.detailModal.hidden = false;
    }

    function formatCount(value) {
        const num = Number(value || 0);
        if (!isFinite(num)) {
            return '0';
        }

        return num.toLocaleString();
    }

    function formatDate(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return date.toLocaleString();
    }

    function roleLabel(role) {
        const map = {
            'role-applicant': 'Applicant',
            'role-undergrad': 'Undergraduate',
            'role-profile': 'Profile',
            'role-admin': 'Administrator'
        };

        return map[role] || String(role || '-');
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
})();
