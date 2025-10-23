<?php
// My Sessions Component
// This component displays user's study sessions with management capabilities

// TODO: Add database logic here if needed for fetching user sessions
// $userSessions = [];
// $currentUser = $_SESSION['user_id'] ?? null;
?>

<!-- My Sessions Card -->
<div class="my-session-card">
    <div class="session-top">
        <div class="session-header">
            <h3 class="session-title">Linear Algebra Problem Solving</h3>
            <span class="status-badge">🟢 Upcoming</span>
        </div>
        <ul class="session-details-list">
            <li><svg viewBox="0 0 20 20"><path d="M14 2h-1v-1h-2v1h-4v-1h-2v1h-1c-0.553 0-1 0.447-1 1v12c0 0.553 0.447 1 1 1h12c0.553 0 1-0.447 1-1v-12c0-0.553-0.447-1-1-1zM14 15h-12v-8h12v8z"></path></svg> Today, Dec 15, 2024</li>
            <li><svg viewBox="0 0 20 20"><path d="M10 2c-4.418 0-8 3.582-8 8s3.582 8 8 8 8-3.582 8-8-3.582-8-8-8zM10 14c-2.209 0-4-1.791-4-4s1.791-4 4-4 4 1.791 4 4-1.791 4-4 4zM11 6h-2v5h5v-2h-3z"></path></svg> 3:00 PM - 5:00 PM (2 hours)</li>
            <li><svg viewBox="0 0 20 20"><path d="M10 2c-3.866 0-7 3.134-7 7 0 4.162 4.635 9.313 6.205 10.686 0.219 0.194 0.518 0.314 0.824 0.314s0.605-0.119 0.824-0.314c1.569-1.373 6.146-6.524 6.146-10.686 0-3.866-3.134-7-7-7zM10 11c-1.105 0-2-0.895-2-2s0.895-2 2-2 2 0.895 2 2-0.895 2-2 2z"></path></svg> Online (Zoom)</li>
        </ul>
    </div>
    
    <div class="session-footer">
        <div class="participant-info">
            <span>👥 8 Students viewed</span>
            <div class="avatar-stack">
                <div class="avatar"></div>
                <div class="avatar"></div>
                <div class="avatar"></div>
            </div>
        </div>
        <div class="host-actions">
            <a href="#" class="copy-link"><svg viewBox="0 0 20 20"><path d="M11 10c-0.552 0-1 0.448-1 1s0.448 1 1 1h3c0.552 0 1-0.448 1-1s-0.448-1-1-1h-3zM15 6h-3c-0.552 0-1 0.448-1 1s0.448 1 1 1h3c0.552 0 1-0.448 1-1s-0.448-1-1-1zM18 4h-1v-1c0-1.657-1.343-3-3-3h-8c-1.657 0-3 1.343-3 3v1h-1c-0.552 0-1 0.448-1 1v12c0 0.552 0.448 1 1 1h16c0.552 0 1-0.448 1-1v-12c0-0.552-0.448-1-1-1zM5 3c0-0.551 0.449-1 1-1h8c0.551 0 1 0.449 1 1v1h-10v-1zM17 17h-14v-10h14v10z"></path></svg>Copy Invite Link</a>
            <a href="#" class="action-btn btn-grey"><svg viewBox="0 0 20 20"><path d="M12.3 3.7l-1 1c-0.4 0.4-0.4 1 0 1.4l4 4c0.4 0.4 1 0.4 1.4 0l1-1c0.4-0.4 0.4-1 0-1.4l-4-4c-0.4-0.4-1-0.4-1.4 0zM11.3 5.7l-8.6 8.6c-0.2 0.2-0.3 0.4-0.3 0.7v2c0 0.6 0.4 1 1 1h2c0.3 0 0.5-0.1 0.7-0.3l8.6-8.6-4.4-4.4z"></path></svg>Edit Details</a>
            <a href="#" class="action-btn btn-red"><svg viewBox="0 0 20 20"><path d="M6 2l2-2h4l2 2h4v2h-16v-2h4zM3 6h14l-1 14h-12l-1-14z"></path></svg>Cancel Session</a>
        </div>
    </div>
</div>

<style>
    :root {
        --card-bg: #E8F4FD;
        --text-primary: #2D3E50;
        --text-secondary: #5D6D7E;
        --accent-green: #27AE60;
        --accent-red: #E74C3C;
        --accent-blue: #3498DB;
        --accent-grey: #7F8C8D;
        --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .my-session-card {
        background-color: var(--card-bg);
        border-radius: 12px;
        box-shadow: var(--card-shadow);
        padding: 1.5rem 2rem;
        width: 100%;
        max-width: 720px;
        aspect-ratio: 3 / 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .session-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .session-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }

    .status-badge {
        background-color: var(--accent-green);
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 9999px; /* Pill shape */
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        flex-shrink: 0;
    }

    .session-details-list {
        list-style: none;
        padding: 0;
        margin: 1rem 0;
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .session-details-list li {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: var(--text-secondary);
    }
    .session-details-list li svg {
        width: 16px;
        height: 16px;
        fill: var(--text-secondary);
    }

    .session-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #d6e8f8;
        padding-top: 1rem;
    }
    
    .participant-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.9rem;
        color: var(--text-secondary);
    }

    .avatar-stack { display: flex; }
    .avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 2px solid var(--card-bg);
        background-color: #b0c4de;
    }
    .avatar:not(:first-child) { margin-left: -10px; }
    
    .host-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .action-btn {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 600;
        color: white;
        text-decoration: none;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    .action-btn:hover { opacity: 0.85; }
    .action-btn svg { width: 14px; height: 14px; fill: white; }

    .btn-grey { background-color: var(--accent-grey); }
    .btn-red { background-color: var(--accent-red); }

    .copy-link {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-secondary);
        text-decoration: none;
        transition: color 0.2s;
    }
    .copy-link:hover { color: var(--text-primary); }
    .copy-link svg { width: 14px; height: 14px; fill: currentColor; }
</style>
