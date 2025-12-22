<link rel="stylesheet" href="/unihelper/views/css/components/qa.css">
<script src="/unihelper/views/js/qa.js"></script>

<div class="qa-header">
    <h1 class="qa-title">Q&A forum</h1>
    <div class="qa-controls">
        <div class="qa-searchbar">
            <input type="text" placeholder="" class="search-input">
            <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
        </div>
    </div>
    <div class="qa-tags-bar">
        <?php for ($i = 0; $i < 10; $i++): ?>
            <button class="tag-btn skeleton-text skeleton-tag"></button>
        <?php endfor; ?>
    </div>
</div>

<div class="qa-main">
    <!-- Question Card skeleton -->
    <div class="qa-question-card">
            <div class="qa-votes">
                <button class="vote-btn upvote skeleton-icon" aria-label="Upvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 4 L5 13 H9 V21 H15 V13 H19 L12 4 Z"></path>
                    </svg>
                </button>
                <span class="vote-count skeleton-text skeleton-count"></span>
                <button class="vote-btn downvote skeleton-icon" aria-label="Downvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 20 L19 11 H15 V3 H9 V11 H5 L12 20 Z"></path>
                    </svg>
                </button>
            </div>
        <div class="qa-content">
            <div class="qa-card-header">
                <div class="qa-user-info">
                    <div class="qa-avatar skeleton-avatar"></div>
                    <div class="qa-user-details">
                        <span class="qa-username skeleton-text skeleton-username"></span>
                        <span class="qa-role skeleton-text skeleton-role"></span>
                    </div>
                    <span class="qa-time skeleton-text skeleton-time"></span>
                </div>
                <button class="qa-menu-btn skeleton-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="12" cy="5" r="1"></circle>
                        <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                </button>
            </div>
            <h3 class="qa-question-title skeleton-text skeleton-question-title"></h3>
            <p class="qa-question-text skeleton-text skeleton-question-text"></p>
            <div class="qa-question-image">
                <img src="" alt="Question attachment">
            </div>
            <div class="qa-card-footer">
                <span class="qa-answer-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </span>
                <span class="qa-view-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </span>
                <div class="qa-actions">
                    <button class="btn btn-outline btn-sm skeleton-text skeleton-btn"></button>
                    <button class="text1 btn btn-primary btn-sm skeleton-text skeleton-btn"></button>
                </div>
            </div>
        </div>
    </div>

    <div class="qa-question-card">
            <div class="qa-votes">
                <button class="vote-btn upvote skeleton-icon" aria-label="Upvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 4 L5 13 H9 V21 H15 V13 H19 L12 4 Z"></path>
                    </svg>
                </button>
                <span class="vote-count skeleton-text skeleton-count"></span>
                <button class="vote-btn downvote skeleton-icon" aria-label="Downvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 20 L19 11 H15 V3 H9 V11 H5 L12 20 Z"></path>
                    </svg>
                </button>
            </div>
        <div class="qa-content">
            <div class="qa-card-header">
                <div class="qa-user-info">
                    <div class="qa-avatar skeleton-avatar"></div>
                    <div class="qa-user-details">
                        <span class="qa-username skeleton-text skeleton-username"></span>
                        <span class="qa-role skeleton-text skeleton-role"></span>
                    </div>
                    <span class="qa-time skeleton-text skeleton-time"></span>
                </div>
                <button class="qa-menu-btn skeleton-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="12" cy="5" r="1"></circle>
                        <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                </button>
            </div>
            <h3 class="qa-question-title skeleton-text skeleton-question-title"></h3>
            <p class="qa-question-text skeleton-text skeleton-question-text"></p>
            <div class="qa-question-image">
                <img src="" alt="Question attachment">
            </div>
            <div class="qa-card-footer">
                <span class="qa-answer-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </span>
                <span class="qa-view-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </span>
                <div class="qa-actions">
                    <button class="btn btn-outline btn-sm skeleton-text skeleton-btn"></button>
                    <button class="text1 btn btn-primary btn-sm skeleton-text skeleton-btn"></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="qa-search-results qa-main" aria-hidden="true">
     <!-- this is for the search result capturing -->
     <div class="qa-question-card">
            <div class="qa-votes">
                <button class="vote-btn upvote skeleton-icon" aria-label="Upvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 4 L5 13 H9 V21 H15 V13 H19 L12 4 Z"></path>
                    </svg>
                </button>
                <span class="vote-count skeleton-text skeleton-count"></span>
                <button class="vote-btn downvote skeleton-icon" aria-label="Downvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 20 L19 11 H15 V3 H9 V11 H5 L12 20 Z"></path>
                    </svg>
                </button>
            </div>
        <div class="qa-content">
            <div class="qa-card-header">
                <div class="qa-user-info">
                    <div class="qa-avatar skeleton-avatar"></div>
                    <div class="qa-user-details">
                        <span class="qa-username skeleton-text skeleton-username"></span>
                        <span class="qa-role skeleton-text skeleton-role"></span>
                    </div>
                    <span class="qa-time skeleton-text skeleton-time"></span>
                </div>
                <button class="qa-menu-btn skeleton-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="12" cy="5" r="1"></circle>
                        <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                </button>
            </div>
            <h3 class="qa-question-title skeleton-text skeleton-question-title"></h3>
            <p class="qa-question-text skeleton-text skeleton-question-text"></p>
            <div class="qa-question-image">
                <img src="" alt="Question attachment">
            </div>
            <div class="qa-card-footer">
                <span class="qa-answer-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </span>
                <span class="qa-view-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </span>
                <div class="qa-actions">
                    <button class="btn btn-outline btn-sm skeleton-text skeleton-btn"></button>
                    <button class="text1 btn btn-primary btn-sm skeleton-text skeleton-btn"></button>
                </div>
            </div>
        </div>
    </div>

    <div class="qa-question-card">
            <div class="qa-votes">
                <button class="vote-btn upvote skeleton-icon" aria-label="Upvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 4 L5 13 H9 V21 H15 V13 H19 L12 4 Z"></path>
                    </svg>
                </button>
                <span class="vote-count skeleton-text skeleton-count"></span>
                <button class="vote-btn downvote skeleton-icon" aria-label="Downvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 20 L19 11 H15 V3 H9 V11 H5 L12 20 Z"></path>
                    </svg>
                </button>
            </div>
        <div class="qa-content">
            <div class="qa-card-header">
                <div class="qa-user-info">
                    <div class="qa-avatar skeleton-avatar"></div>
                    <div class="qa-user-details">
                        <span class="qa-username skeleton-text skeleton-username"></span>
                        <span class="qa-role skeleton-text skeleton-role"></span>
                    </div>
                    <span class="qa-time skeleton-text skeleton-time"></span>
                </div>
                <button class="qa-menu-btn skeleton-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="12" cy="5" r="1"></circle>
                        <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                </button>
            </div>
            <h3 class="qa-question-title skeleton-text skeleton-question-title"></h3>
            <p class="qa-question-text skeleton-text skeleton-question-text"></p>
            <div class="qa-question-image">
                <img src="" alt="Question attachment">
            </div>
            <div class="qa-card-footer">
                <span class="qa-answer-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </span>
                <span class="qa-view-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </span>
                <div class="qa-actions">
                    <button class="btn btn-outline btn-sm skeleton-text skeleton-btn"></button>
                    <button class="text1 btn btn-primary btn-sm skeleton-text skeleton-btn"></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="qa-search-results qa-main" aria-hidden="true">
     <!-- this is for the search result capturing -->
     <div class="qa-question-card">
            <div class="qa-votes">
                <button class="vote-btn upvote skeleton-icon" aria-label="Upvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 4 L5 13 H9 V21 H15 V13 H19 L12 4 Z"></path>
                    </svg>
                </button>
                <span class="vote-count skeleton-text skeleton-count"></span>
                <button class="vote-btn downvote skeleton-icon" aria-label="Downvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 20 L19 11 H15 V3 H9 V11 H5 L12 20 Z"></path>
                    </svg>
                </button>
            </div>
        <div class="qa-content">
            <div class="qa-card-header">
                <div class="qa-user-info">
                    <div class="qa-avatar skeleton-avatar"></div>
                    <div class="qa-user-details">
                        <span class="qa-username skeleton-text skeleton-username"></span>
                        <span class="qa-role skeleton-text skeleton-role"></span>
                    </div>
                    <span class="qa-time skeleton-text skeleton-time"></span>
                </div>
                <button class="qa-menu-btn skeleton-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="12" cy="5" r="1"></circle>
                        <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                </button>
            </div>
            <h3 class="qa-question-title skeleton-text skeleton-question-title"></h3>
            <p class="qa-question-text skeleton-text skeleton-question-text"></p>
            <div class="qa-question-image">
                <img src="" alt="Question attachment">
            </div>
            <div class="qa-card-footer">
                <span class="qa-answer-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </span>
                <span class="qa-view-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </span>
                <div class="qa-actions">
                    <button class="btn btn-outline btn-sm skeleton-text skeleton-btn"></button>
                    <button class="text1 btn btn-primary btn-sm skeleton-text skeleton-btn"></button>
                </div>
            </div>
        </div>
    </div>

    <div class="qa-question-card">
            <div class="qa-votes">
                <button class="vote-btn upvote skeleton-icon" aria-label="Upvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 4 L5 13 H9 V21 H15 V13 H19 L12 4 Z"></path>
                    </svg>
                </button>
                <span class="vote-count skeleton-text skeleton-count"></span>
                <button class="vote-btn downvote skeleton-icon" aria-label="Downvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 20 L19 11 H15 V3 H9 V11 H5 L12 20 Z"></path>
                    </svg>
                </button>
            </div>
        <div class="qa-content">
            <div class="qa-card-header">
                <div class="qa-user-info">
                    <div class="qa-avatar skeleton-avatar"></div>
                    <div class="qa-user-details">
                        <span class="qa-username skeleton-text skeleton-username"></span>
                        <span class="qa-role skeleton-text skeleton-role"></span>
                    </div>
                    <span class="qa-time skeleton-text skeleton-time"></span>
                </div>
                <button class="qa-menu-btn skeleton-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="12" cy="5" r="1"></circle>
                        <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                </button>
            </div>
            <h3 class="qa-question-title skeleton-text skeleton-question-title"></h3>
            <p class="qa-question-text skeleton-text skeleton-question-text"></p>
            <div class="qa-question-image">
                <img src="" alt="Question attachment">
            </div>
            <div class="qa-card-footer">
                <span class="qa-answer-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </span>
                <span class="qa-view-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </span>
                <div class="qa-actions">
                    <button class="btn btn-outline btn-sm skeleton-text skeleton-btn"></button>
                    <button class="text1 btn btn-primary btn-sm skeleton-text skeleton-btn"></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="qa-search-results qa-main" aria-hidden="true">
     <!-- this is for the search result capturing -->
     <div class="qa-question-card">
            <div class="qa-votes">
                <button class="vote-btn upvote skeleton-icon" aria-label="Upvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 4 L5 13 H9 V21 H15 V13 H19 L12 4 Z"></path>
                    </svg>
                </button>
                <span class="vote-count skeleton-text skeleton-count"></span>
                <button class="vote-btn downvote skeleton-icon" aria-label="Downvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 20 L19 11 H15 V3 H9 V11 H5 L12 20 Z"></path>
                    </svg>
                </button>
            </div>
        <div class="qa-content">
            <div class="qa-card-header">
                <div class="qa-user-info">
                    <div class="qa-avatar skeleton-avatar"></div>
                    <div class="qa-user-details">
                        <span class="qa-username skeleton-text skeleton-username"></span>
                        <span class="qa-role skeleton-text skeleton-role"></span>
                    </div>
                    <span class="qa-time skeleton-text skeleton-time"></span>
                </div>
                <button class="qa-menu-btn skeleton-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="12" cy="5" r="1"></circle>
                        <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                </button>
            </div>
            <h3 class="qa-question-title skeleton-text skeleton-question-title"></h3>
            <p class="qa-question-text skeleton-text skeleton-question-text"></p>
            <div class="qa-question-image">
                <img src="" alt="Question attachment">
            </div>
            <div class="qa-card-footer">
                <span class="qa-answer-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </span>
                <span class="qa-view-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </span>
                <div class="qa-actions">
                    <button class="btn btn-outline btn-sm skeleton-text skeleton-btn"></button>
                    <button class="text1 btn btn-primary btn-sm skeleton-text skeleton-btn"></button>
                </div>
            </div>
        </div>
    </div>

    <div class="qa-question-card">
            <div class="qa-votes">
                <button class="vote-btn upvote skeleton-icon" aria-label="Upvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 4 L5 13 H9 V21 H15 V13 H19 L12 4 Z"></path>
                    </svg>
                </button>
                <span class="vote-count skeleton-text skeleton-count"></span>
                <button class="vote-btn downvote skeleton-icon" aria-label="Downvote">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                        <path d="M12 20 L19 11 H15 V3 H9 V11 H5 L12 20 Z"></path>
                    </svg>
                </button>
            </div>
        <div class="qa-content">
            <div class="qa-card-header">
                <div class="qa-user-info">
                    <div class="qa-avatar skeleton-avatar"></div>
                    <div class="qa-user-details">
                        <span class="qa-username skeleton-text skeleton-username"></span>
                        <span class="qa-role skeleton-text skeleton-role"></span>
                    </div>
                    <span class="qa-time skeleton-text skeleton-time"></span>
                </div>
                <button class="qa-menu-btn skeleton-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="12" cy="5" r="1"></circle>
                        <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                </button>
            </div>
            <h3 class="qa-question-title skeleton-text skeleton-question-title"></h3>
            <p class="qa-question-text skeleton-text skeleton-question-text"></p>
            <div class="qa-question-image">
                <img src="" alt="Question attachment">
            </div>
            <div class="qa-card-footer">
                <span class="qa-answer-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </span>
                <span class="qa-view-count skeleton-text skeleton-stat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </span>
                <div class="qa-actions">
                    <button class="btn btn-outline btn-sm skeleton-text skeleton-btn"></button>
                    <button class="text1 btn btn-primary btn-sm skeleton-text skeleton-btn"></button>
                </div>
            </div>
        </div>
    </div>
</div>