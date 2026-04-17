<?php
if (($user->role ?? '') !== 'role-admin') {
    echo "<div class='error'>Access denied. Administrator role required.</div>";
    return;
}
?>

<section class="sys-overview-shell" id="sysOverviewRoot">
    <header class="sys-overview-header">
        <div>
            <h2 class="sys-overview-title">System Overview</h2>
            <p class="sys-overview-subtitle">Platform-wide analytics for users, content, sessions, connections, and notification subscriber activity.</p>
        </div>
        <div class="sys-overview-state" id="sysOverviewState">Loading analytics...</div>
    </header>

    <div class="sys-toolbar">
        <div class="sys-window-switch" role="tablist" aria-label="Overview windows">
            <button type="button" class="sys-window-btn active" data-window="active" aria-selected="true">Active Data</button>
            <button type="button" class="sys-window-btn" data-window="archived" aria-selected="false">Deleted / Expired</button>
        </div>

        <div class="sys-filter-row">
            <label class="sys-filter-group" for="sysRangePreset">
                <span>Range</span>
                <select id="sysRangePreset" class="sys-filter-input">
                    <option value="all">All Time</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d" selected>Last 30 Days</option>
                    <option value="90d">Last 90 Days</option>
                    <option value="custom">Custom</option>
                </select>
            </label>

            <label class="sys-filter-group" for="sysFromDate">
                <span>From</span>
                <input type="date" id="sysFromDate" class="sys-filter-input" disabled>
            </label>

            <label class="sys-filter-group" for="sysToDate">
                <span>To</span>
                <input type="date" id="sysToDate" class="sys-filter-input" disabled>
            </label>

            <button type="button" id="sysApplyFilters" class="sys-btn sys-btn-primary">Apply</button>
            <button type="button" id="sysResetFilters" class="sys-btn">Reset</button>
        </div>
    </div>

    <div class="sys-kpi-grid" id="sysKpiGrid">
        <article class="sys-kpi-card">
            <p class="sys-kpi-label">Users</p>
            <p class="sys-kpi-value" id="sysKpiUsers">0</p>
        </article>
        <article class="sys-kpi-card">
            <p class="sys-kpi-label">Feed Posts</p>
            <p class="sys-kpi-value" id="sysKpiPosts">0</p>
        </article>
        <article class="sys-kpi-card">
            <p class="sys-kpi-label">Questions</p>
            <p class="sys-kpi-value" id="sysKpiQuestions">0</p>
        </article>
        <article class="sys-kpi-card">
            <p class="sys-kpi-label">Answers</p>
            <p class="sys-kpi-value" id="sysKpiAnswers">0</p>
        </article>
        <article class="sys-kpi-card">
            <p class="sys-kpi-label">Sessions</p>
            <p class="sys-kpi-value" id="sysKpiSessions">0</p>
        </article>
        <article class="sys-kpi-card">
            <p class="sys-kpi-label">Notifications</p>
            <p class="sys-kpi-value" id="sysKpiNotifications">0</p>
            <p class="sys-kpi-meta" id="sysKpiUnreadNotifications">Unread: 0</p>
        </article>
        <article class="sys-kpi-card">
            <p class="sys-kpi-label">Pending Requests</p>
            <p class="sys-kpi-value" id="sysKpiPendingConnections">0</p>
        </article>
        <article class="sys-kpi-card">
            <p class="sys-kpi-label">Accepted Connections</p>
            <p class="sys-kpi-value" id="sysKpiAcceptedConnections">0</p>
        </article>
    </div>

    <div class="sys-chart-grid">
        <article class="sys-chart-card">
            <div class="sys-chart-head">
                <h3>User Role Distribution</h3>
            </div>
            <div class="sys-chart-wrap">
                <canvas id="sysRoleChart" aria-label="User role distribution chart"></canvas>
            </div>
        </article>

        <article class="sys-chart-card">
            <div class="sys-chart-head">
                <h3>Activity Distribution</h3>
            </div>
            <div class="sys-chart-wrap">
                <canvas id="sysActivityChart" aria-label="Activity distribution chart"></canvas>
            </div>
        </article>
    </div>

    <article class="sys-top-users-card">
        <div class="sys-section-head">
            <h3>Top Active Users</h3>
            <p class="sys-section-sub">Users ranked by combined activity score in the selected range.</p>
        </div>
        <div class="sys-top-users-list" id="sysTopUsersList"></div>
    </article>

    <article class="sys-table-card">
        <div class="sys-section-head sys-section-head-space">
            <div>
                <h3>User Activity Breakdown</h3>
                <p class="sys-section-sub">Click a user to drill down into posts, Q&A, sessions, friend requests, and notification subscriber activity.</p>
            </div>
            <div class="sys-table-filters">
                <input type="search" id="sysUserSearch" class="sys-filter-input" placeholder="Search by name, email, or phone">
                <select id="sysRoleFilter" class="sys-filter-input">
                    <option value="">All Roles</option>
                    <option value="role-applicant">Applicant</option>
                    <option value="role-undergrad">Undergraduate</option>
                    <option value="role-profile">Profile</option>
                    <option value="role-admin">Administrator</option>
                </select>
                <button type="button" id="sysSearchBtn" class="sys-btn sys-btn-primary">Search</button>
            </div>
        </div>

        <div class="sys-table-wrap">
            <table class="sys-user-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Contact</th>
                        <th>Posts</th>
                        <th>Q</th>
                        <th>A</th>
                        <th>Sessions</th>
                        <th>Pending</th>
                        <th>Accepted</th>
                        <th>Notifications</th>
                        <th>Score</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="sysUserTableBody"></tbody>
            </table>
        </div>

        <div class="sys-pagination" id="sysPagination">
            <button type="button" id="sysPrevPage" class="sys-btn">Previous</button>
            <span id="sysPaginationText">Page 1 of 1</span>
            <button type="button" id="sysNextPage" class="sys-btn">Next</button>
        </div>
    </article>
</section>

<div class="sys-detail-modal" id="sysDetailModal" hidden>
    <div class="sys-detail-backdrop" id="sysDetailBackdrop"></div>
    <div class="sys-detail-panel" role="dialog" aria-modal="true" aria-labelledby="sysDetailTitle">
        <header class="sys-detail-header">
            <div>
                <h3 id="sysDetailTitle">User Activity Detail</h3>
                <p id="sysDetailSubtitle" class="sys-detail-subtitle"></p>
            </div>
            <button type="button" id="sysDetailClose" class="sys-detail-close" aria-label="Close">&times;</button>
        </header>

        <div class="sys-detail-metrics" id="sysDetailMetrics"></div>

        <div class="sys-detail-grid">
            <section class="sys-detail-section">
                <h4>Feed Posts</h4>
                <div class="sys-detail-list" id="sysDetailPosts"></div>
            </section>
            <section class="sys-detail-section">
                <h4>Questions</h4>
                <div class="sys-detail-list" id="sysDetailQuestions"></div>
            </section>
            <section class="sys-detail-section">
                <h4>Answers</h4>
                <div class="sys-detail-list" id="sysDetailAnswers"></div>
            </section>
            <section class="sys-detail-section">
                <h4>Sessions</h4>
                <div class="sys-detail-list" id="sysDetailSessions"></div>
            </section>
            <section class="sys-detail-section">
                <h4>Friend Requests / Connections</h4>
                <div class="sys-detail-list" id="sysDetailConnections"></div>
            </section>
            <section class="sys-detail-section">
                <h4>Notification Subscriber Activity</h4>
                <div class="sys-detail-list" id="sysDetailNotifications"></div>
            </section>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="/unihelper/views/js/system-overview.js"></script>
