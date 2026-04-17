<link rel="stylesheet" href="/unihelper/views/css/components/user-management.css">

<div class="user-management-container">
    <!-- Header -->
    <div class="um-header">
        <h2 class="um-title">User Management</h2>
        
        <div class="um-search-container">
            <svg class="um-search-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="um-search-input" class="um-search-input" placeholder="Search users by name...">
        </div>
        
        <div class="um-header-actions">
            <button class="btn btn-primary um-add-btn" id="um-moderation-btn" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem">
                    <path d="M12 3l7 4v5c0 5-3.5 9.7-7 11-3.5-1.3-7-6-7-11V7l7-4z"></path>
                </svg>
                Moderation
            </button>
        </div>
    </div>

    <!-- KPI Stats Row -->
    <div class="um-kpi-row">
        <button class="um-kpi-card um-kpi-tab active" data-filter="all" type="button" aria-pressed="true">
            <div class="um-kpi-icon um-icon-students">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="um-kpi-info">
                <span class="um-kpi-label">Total Users</span>
                <span class="um-kpi-value" id="kpi-total-users">0</span>
            </div>
        </button>

        <button class="um-kpi-card um-kpi-tab" data-filter="reports" type="button" aria-pressed="false">
            <div class="um-kpi-icon um-icon-reports">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
            <div class="um-kpi-info">
                <span class="um-kpi-label">Pending Reports</span>
                <span class="um-kpi-value" id="kpi-pending-reports">0</span>
            </div>
        </button>

        <button class="um-kpi-card um-kpi-tab" data-filter="banned" type="button" aria-pressed="false">
            <div class="um-kpi-icon um-icon-banned">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                </svg>
            </div>
            <div class="um-kpi-info">
                <span class="um-kpi-label">Banned Accounts</span>
                <span class="um-kpi-value" id="kpi-banned-accounts">0</span>
            </div>
        </button>

        <button class="um-kpi-card um-kpi-tab" data-filter="deleted" type="button" aria-pressed="false">
            <div class="um-kpi-icon um-icon-deleted">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                    <path d="M10 11v6"></path>
                    <path d="M14 11v6"></path>
                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                </svg>
            </div>
            <div class="um-kpi-info">
                <span class="um-kpi-label">Deleted Users</span>
                <span class="um-kpi-value" id="kpi-deleted-users">0</span>
            </div>
        </button>
    </div>

    <div class="um-list-heading">
        <h3 id="um-list-title">All Users</h3>
    </div>

    <!-- Main Data Table -->
    <div class="um-table-wrapper" id="um-users-view">
        <table class="um-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>University</th>
                    <th>Role</th>
                    <th id="um-date-column-title">Joined Date</th>
                    <th style="text-align: right; padding-right: 2rem;">Actions</th>
                </tr>
            </thead>
            <tbody id="um-table-body">
                <!-- Javascript will inject template literals here -->
            </tbody>
        </table>
    </div>

    <div class="um-reports-view" id="um-reports-view" hidden>
        <div class="um-reports-list" id="um-reports-list">
            <!-- Javascript will inject pending report cards here -->
        </div>
    </div>

    <div class="um-load-more-wrap" id="um-load-more-wrap" hidden>
        <button class="btn btn-outline um-load-more-btn" id="um-load-more-btn" type="button">Load more</button>
    </div>

    <!-- Slide-over Panel for Show Details -->
    <div id="um-slide-panel" class="um-slide-panel">
        <div class="um-slide-panel-overlay"></div>
        <div class="um-slide-panel-content">
            <div class="um-slide-header">
                <h3>User Details</h3>
                <button id="um-close-panel" class="um-close-btn" aria-label="Close details">&times;</button>
            </div>
            <div class="um-slide-body" id="um-slide-body-content">
                <!-- User details content injected here via JS -->
            </div>
        </div>
    </div>
</div>

<script src="/unihelper/views/js/user-management.js"></script>
