<?php
// System Admin Moderation Component
// This component provides moderation management interface for system administrators

// TODO: Add database logic here if needed for fetching banned content/users
// $bannedContent = [];
// $bannedUsers = [];
?>

<!-- Moderation Management Interface -->
<div class="moderation-container">
    <div class="tabs-header">
        <div class="tab active" data-target="banned-content-list">📋 Banned Content</div>
        <div class="tab" data-target="banned-users-content">👤 Banned Users</div>
    </div>

    <div class="tab-content-area">
        <div id="banned-content-list">
            <div class="banned-content-card">
                <div class="content-details">
                    <h4>Is this exam question leaked?</h4>
                    <p class="snippet">"Hey guys, did you see the question paper for..."</p>
                </div>
                <div class="moderation-info">
                    <p>Author: <strong>anonymous_user123</strong></p>
                    <p>Moderator: <strong>Moderator_John</strong></p>
                    <p>Reason: <strong>Academic Integrity Violation</strong></p>
                    <p>Status: <strong class="ban-duration">Removed Permanently</strong></p>
                    <div class="card-actions">
                        <a href="#" class="btn btn-success">Revoke Ban</a>
                        <a href="#" class="btn btn-danger">Ban Content</a>
                    </div>
                </div>
            </div>
        </div>

        <div id="banned-users-content">
            <div class="banned-user-card-row">
                <div class="user-card-avatar-section">
                    <div class="user-card-avatar"></div>
                    <div class="user-card-username">anonymous_user123</div>
                </div>
                <div class="user-card-details">
                    <p><strong>Email:</strong> user123@email.com</p>
                    <p><strong>Reason:</strong> Harassment & Inappropriate Content</p>
                    <p><strong>Banned by:</strong> Moderator_Sarah</p>
                    <p><strong>Date:</strong> Dec 15, 2024 | 14:30</p>
                </div>
                <div class="user-card-status-section">
                    <span class="status-badge">BAN ACTIVE</span>
                    <p>Duration: <strong>30 Days</strong></p>
                    <div class="card-actions">
                        <a href="#" class="btn btn-success">Revoke Ban</a>
                        <a href="#" class="btn btn-secondary">Ban User</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --header-bg: #2D3E50;
        --content-bg: #F5F7FA;
        --card-bg: #FFFFFF;
        --text-light: #FFFFFF;
        --text-dark: #34495E;
        --text-muted: #7F8C8D;
        --accent-red: #E74C3C;
        --accent-green: #2ECC71;
        --accent-grey: #95A5A6;
        --border-color: #BDC3C7;
    }

    .moderation-container {
        width: 100%;
        max-width: 960px;
        aspect-ratio: 16 / 9;
        background-color: var(--content-bg);
        border-radius: 4px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .tabs-header {
        display: flex;
        background-color: var(--header-bg);
    }

    .tab {
        padding: 1rem 1.5rem;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-muted);
        border-bottom: 4px solid transparent;
        transition: all 0.2s ease-in-out;
    }

    .tab.active {
        color: var(--text-dark);
        background-color: var(--content-bg);
        border-bottom: 4px solid var(--header-bg);
    }
    
    .tab:not(.active):hover {
        background-color: #34495E;
        color: var(--text-light);
    }

    .tab-content-area {
        flex-grow: 1;
        padding: 1.5rem;
        overflow-y: auto;
    }
    
    /* --- Banned Content Card Styles --- */
    #banned-users-content { display: none; }

    .banned-content-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-left: 5px solid var(--accent-red);
        padding: 1rem 1.5rem;
        margin-bottom: 1rem;
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1rem 1.5rem;
        border-radius: 4px;
    }

    .content-details h4 { margin: 0 0 0.25rem 0; font-size: weight: 600; color: var(--text-dark); }
    .content-details .snippet { font-family: monospace; font-size: 0.9rem; color: var(--text-muted); margin: 0; }
    .moderation-info p { margin: 0.25rem 0; color: var(--text-muted); font-size: 0.85rem; }
    .moderation-info strong { color: var(--text-dark); margin-left: 0.5rem; }
    .ban-duration { font-weight: 700 !important; color: var(--accent-red) !important; }
    
    .card-actions { margin-top: 1rem; display: flex; gap: 0.5rem; }

    /* --- Banned User Card Styles --- */
    .banned-user-card-row {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 4px;
        padding: 1rem 1.5rem;
        margin-bottom: 1rem;
    }

    .user-card-avatar-section { text-align: center; flex-shrink: 0; }
    .user-card-avatar { width: 50px; height: 50px; border-radius: 50%; background-color: #dfe6e9; margin-bottom: 0.25rem; }
    .user-card-username { font-size: 0.8rem; font-weight: 600; color: var(--text-dark); }

    .user-card-details { flex-grow: 1; }
    .user-card-details p { margin: 0.2rem 0; font-size: 0.85rem; color: var(--text-muted); }
    .user-card-details strong { color: var(--text-dark); }

    .user-card-status-section { text-align: right; flex-shrink: 0; }
    .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 700; color: white; margin-bottom: 0.5rem; background-color: var(--accent-red); }
    .user-card-status-section p { margin: 0.2rem 0; font-size: 0.85rem; color: var(--text-muted); }
    .user-card-status-section .card-actions { justify-content: flex-end; }

    /* --- Generic Button Styles --- */
    .btn {
        padding: 0.4rem 0.8rem;
        border: none;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        color: white;
        display: inline-block;
    }
    .btn-success { background-color: var(--accent-green); }
    .btn-danger { background-color: var(--accent-red); }
    .btn-secondary { background-color: var(--accent-grey); }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('.tab');
        const contentPanes = document.querySelectorAll('.tab-content-area > div');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                contentPanes.forEach(pane => { pane.style.display = 'none'; });
                tab.classList.add('active');
                const targetPane = document.getElementById(tab.getAttribute('data-target'));
                if (targetPane) { targetPane.style.display = 'block'; }
            });
        });
    });
</script>
