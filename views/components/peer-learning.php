<link rel="stylesheet" href="/unihelper/views/css/components/peer-learning.css">
<!-- ═══════════════════════════════════════════════════════
     PEER LEARNING COMPONENT
     HTML templates + layout. JS is in views/js/peer-learning.js
     ═══════════════════════════════════════════════════════ -->

<!-- ── HIDDEN TEMPLATES (cloned by JS, never shown directly) ── -->

<!-- Session Card Template -->
<div id="tpl-peer-card" class="peer-card template" style="display:none">
    <div class="peer-card-badges">
        <span class="js-audience-badge peer-badge"></span>
        <span class="js-status-badge peer-badge"></span>
    </div>
    <div class="peer-card-major js-major"></div>
    <div class="peer-card-title js-title"></div>
    <div class="peer-card-desc js-desc"></div>
    <div class="peer-card-meta">
        <span class="peer-card-meta-item js-date">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <span class="js-date-text"></span>
        </span>
        <span class="peer-card-meta-item js-time">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span class="js-time-text"></span>
        </span>
        <span class="peer-card-meta-item js-duration">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 22h14"></path><path d="M5 2h14"></path><path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"></path><path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"></path></svg>
            <span class="js-duration-text"></span>
        </span>
    </div>
    <div class="peer-card-tags js-tags"></div>
    <div class="peer-card-author">
        <a class="js-author-link" href="#">
            <img class="peer-card-author-avatar js-author-avatar" src="" alt="">
        </a>
        <div class="peer-card-author-info">
            <a class="peer-card-author-name js-author-name" href="#"></a>
            <span class="peer-card-author-uni js-author-uni"></span>
        </div>
        <span class="peer-card-subs js-subs">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            <span class="js-subs-count">0</span>
        </span>
    </div>
</div>

<!-- Tag Chip Template -->
<template id="tpl-peer-tag">
    <span class="peer-card-tag"></span>
</template>

<!-- Subscriber Item Template -->
<div id="tpl-subscriber-item" class="peer-subscriber-item template" style="display:none">
    <a class="js-sub-profile-link" href="#">
        <img class="peer-subscriber-avatar js-sub-avatar" src="" alt="">
    </a>
    <div class="peer-subscriber-info">
        <a class="peer-subscriber-name js-sub-name" href="#"></a>
        <span class="peer-subscriber-time js-sub-time"></span>
    </div>
    <span class="peer-subscriber-status js-sub-status"></span>
    <div class="peer-subscriber-actions js-sub-actions" style="display:none">
        <button class="btn-approve js-btn-approve">Accept</button>
        <button class="btn-reject js-btn-reject">Reject</button>
    </div>
</div>

<!-- Empty State Template -->
<div id="tpl-peer-empty" class="peer-empty template" style="display:none">
    <svg viewBox="0 0 24 24" width="56" height="56" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
    </svg>
    <p class="js-empty-title"></p>
    <span class="js-empty-subtitle"></span>
</div>

<!-- ── CREATE / EDIT MODAL (will be moved to body by JS) ── -->
<div class="peer-modal-overlay" id="peerCreateModal" style="display:none">
    <div class="peer-modal">
        <div class="peer-modal-header">
            <h2 id="peerCreateModalTitle">Create Session</h2>
            <button type="button" class="peer-modal-close" id="peerCreateModalClose">&times;</button>
        </div>
        <div class="peer-modal-body" id="peerCreateModalBody">
            <!-- form HTML loaded dynamically from getSessionForm -->
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     FEED VIEW (tabs + card grid)
     ═══════════════════════════════════════════════════════ -->
<div id="peerFeedView">
    <div class="peer-header">
        <h1>Peer Learning</h1>
        <div class="peer-header-actions">
            <div class="peer-search-wrapper">
                <input type="text" id="peerSearchInput" placeholder="Search sessions...">
                <svg class="search-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path>
                </svg>
            </div>
            <button class="btn btn-primary" id="peerCreateBtn">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Create Session
            </button>
        </div>
    </div>

    <div class="peer-tabs-bar">
        <div class="peer-tabs">
            <button class="peer-tab active" data-tab="all-sessions">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                All Sessions
            </button>
            <button class="peer-tab" data-tab="my-sessions">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                My Sessions
            </button>
            <button class="peer-tab" data-tab="subscribed-sessions">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
                Subscribed
            </button>
        </div>
    </div>

    <!-- All Sessions Panel -->
    <div class="peer-panel active" id="panel-all-sessions">
        <div class="peer-panel-tools" id="allSessionsFilters">
            <button type="button" class="peer-mini-filter active" data-status-filter="all">All</button>
            <button type="button" class="peer-mini-filter" data-status-filter="ongoing">Live</button>
            <button type="button" class="peer-mini-filter" data-status-filter="scheduled">Scheduled</button>
        </div>
        <div class="peer-loading" id="loading-all-sessions"><span>Loading sessions...</span></div>
        <div class="peer-card-grid" id="grid-all-sessions"></div>
        <div class="peer-load-more" id="more-all-sessions"><button type="button">Load More Sessions</button></div>
    </div>

    <!-- My Sessions Panel -->
    <div class="peer-panel" id="panel-my-sessions">
        <div class="peer-loading" id="loading-my-sessions" style="display:none"><span>Loading sessions...</span></div>
        <div class="peer-card-grid" id="grid-my-sessions"></div>
        <div class="peer-load-more" id="more-my-sessions"><button type="button">Load More Sessions</button></div>
    </div>

    <!-- Subscribed Sessions Panel -->
    <div class="peer-panel" id="panel-subscribed-sessions">
        <div class="peer-loading" id="loading-subscribed-sessions" style="display:none"><span>Loading sessions...</span></div>
        <div class="peer-card-grid" id="grid-subscribed-sessions"></div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     DETAIL VIEW (single session — replaces feed, like QA)
     ═══════════════════════════════════════════════════════ -->
<div class="peer-detail-view" id="peerDetailView" style="display:none">
    <button type="button" class="peer-detail-back" id="peerDetailBack">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
        Back to sessions
    </button>
    <div class="peer-detail-card" id="peerDetailCard">
        <!-- populated by JS using DOM manipulation, not innerHTML -->
    </div>
</div>

<script src="/unihelper/views/js/peer-learning.js"></script>