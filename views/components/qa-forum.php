<link rel="stylesheet" href="/unihelper/views/css/components/qa.css">
<script src="/unihelper/views/js/qa.js"></script>
<script src="/unihelper/views/js/qa-utils.js"></script>

<!-- Ask Question Modal -->
<div class="qa-askmodal" style="display: none;">
    <div class="qa-askmodal-content">
        <button class="qa-askmodal-close" aria-label="Close">×</button>
        <h2 class="qa-askmodal-title">Ask a Question</h2>
        <form class="qa-question-form" id="qaQuestionForm">
            <div class="qa-form-group">
                <label for="qa-question-title" class="qa-form-label">Question Title</label>
                <div 
                    contenteditable="true" 
                    id="qa-question-title" 
                    class="qa-form-input" 
                    placeholder="What's your question?"
                    required
                ></div>
            </div>
            
            <div class="qa-form-group">
                <label for="qa-question-body" class="qa-form-label">Details</label>
                <textarea 
                    id="qa-question-body" 
                    class="qa-form-textarea" 
                    placeholder="Provide more details about your question..."
                    rows="6"
                    required
                ></textarea>
            </div>
            
            <div class="qa-form-group">
                <label class="qa-form-label">Images (Optional)</label>
                <div class="qa-image-tray">
                    <input 
                        type="file" 
                        id="qa-image-input" 
                        class="qa-image-input-hidden" 
                        accept="image/*"
                        multiple
                    >
                    <label for="qa-image-input" class="qa-image-add-box">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </label>
                </div>
            </div>
            
            <div class="qa-form-actions">
                <button type="button" class="btn btn-outline qa-cancel-btn">Cancel</button>
                <button type="submit" class="btn btn-primary">Post Question</button>
            </div>
        </form>
    </div>
</div>

<!-- Answer Modal -->
<div class="qa-answermodal" style="display: none;">
    <div class="qa-answermodal-content">
        <button class="qa-answermodal-close" aria-label="Close">×</button>
        <h2 class="qa-answermodal-title">Submit an Answer</h2>
        <form class="qa-answer-form" id="qaAnswerForm">
            <div class="qa-form-group">
                <label for="qa-answer-body" class="qa-form-label">Your Answer</label>
                <textarea 
                    id="qa-answer-body" 
                    class="qa-form-textarea" 
                    placeholder="Write your answer here..."
                    rows="6"
                    required
                ></textarea>
            </div>
            
            <div class="qa-form-actions">
                <button type="button" class="btn btn-outline qa-cancel-btn">Cancel</button>
                <button type="submit" class="btn btn-primary">Post Answer</button>
            </div>
        </form>
    </div>
</div>

<!-- Question Card Template -->
<div id="qa-question-card-template" class="qa-question-card template" style="display: none;">
    <input type="hidden" id="qa-user-id" value="0">
    <div class="qa-votes">
        <button class="vote-btn upvote" aria-label="Upvote">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                <path d="M12 4 L5 13 H9 V21 H15 V13 H19 L12 4 Z"></path>
            </svg>
        </button>
        <span class="vote-count">0</span>
        <button class="vote-btn downvote" aria-label="Downvote">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                <path d="M12 20 L19 11 H15 V3 H9 V11 H5 L12 20 Z"></path>
            </svg>
        </button>
    </div>
    <div class="qa-content">
        <div class="qa-card-header">
            <div class="qa-user-info">
                <div class="qa-avatar">
                    <img class="qa-avatar-img" src="placeholder-avatar.jpg" alt="User Avatar" style="width: 100%; height: 100%; object-fit: cover;">                
                </div>
                <div class="qa-user-details">
                    <span class="qa-username">Loading...</span>
                    <span class="qa-role">Loading...</span>
                </div>
                <div>
                    <span class="qa-time">Just now</span>
                    <span class="qa-modified"></span>
                </div>
            </div>
            <button class="qa-menu-btn" style="display: none;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="1"></circle>
                    <circle cx="12" cy="5" r="1"></circle>
                    <circle cx="12" cy="19" r="1"></circle>
                </svg>
            </button>
        </div>
        <h2 class="qa-question-title">Question Title</h2>
        <div class="qa-question-body-container">
            <!-- Image preview on the left, text on the right -->
            <div class="qa-question-image-preview" style="display: none;">
                <img src="placeholder.jpg" alt="Question attachment">
                <span class="qa-image-count" style="display: none;">+0</span>
            </div>
            <p class="qa-question-text">Question content...</p>
        </div>
        <div class="qa-card-footer">
            <span class="qa-answer-count">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            0</span>
            <div class="qa-actions">
                <button class="btn btn-outline btn-sm answer-btn">Answer</button>
                <button class="text1 btn btn-primary btn-sm view-question-btn">View</button>
            </div>
        </div>
    </div>
</div>

<!-- Question main view -->
<div class="qa-question-view" style="display: none;">
    <span id="qaViewModalQuestionId" style="display: none;"></span>
    <div class="qa-view-container">
        <!-- Question Section -->
        <div class="qa-view-question">
            <!-- Main Content -->
            <div class="qa-view-content">
                <div class="qa-view-header">
                    <div class="qa-user-info">
                        <button type="button" class="qa-nav-left-btn" aria-label="Previous image">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <div class="qa-avatar">
                            <img class="qa-avatar-img" src="placeholder-avatar.jpg" alt="User Avatar" style="width: 100%; height: 100%; object-fit: cover;">                
                        </div>
                        <div class="qa-user-details">
                            <span class="qa-username">Loading...</span>
                            <span class="qa-role">Loading...</span>
                        </div>
                        <div>
                            <span class="qa-time">Just now</span>
                            <span class="qa-modified"></span>
                        </div>
                    </div>
                    <button class="qa-menu-btn" style="display: none;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="1"></circle>
                            <circle cx="12" cy="5" r="1"></circle>
                            <circle cx="12" cy="19" r="1"></circle>
                        </svg>
                    </button>
                </div>

                <h1 class="qa-view-title">How do I solve this differential equation?</h1>
                
                <p class="qa-view-body">I'm stuck on this problem and need help understanding the steps...I'm stuck on this problem and need help understanding the steps...I'm stuck on this problem and need help understanding the steps...I'm stuck on this problem and need help understanding the steps...</p>

                <!-- Image Gallery -->
                <div class="qa-view-images">
                    <button class="qa-img-nav qa-img-prev">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <div class="qa-img-container">
                        <img src="placeholder" alt="Question image">
                    </div>
                    <button class="qa-img-nav qa-img-next">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>

                <div class="qa-view-footer">
                    
                    <div class="qa-votes">
                        <button class="vote-btn upvote" aria-label="Upvote">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                                <path d="M12 4 L5 13 H9 V21 H15 V13 H19 L12 4 Z"></path>
                            </svg>
                        </button>
                        <span class="vote-count">0</span>
                        <button class="vote-btn downvote" aria-label="Downvote">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" role="img">
                                <path d="M12 20 L19 11 H15 V3 H9 V11 H5 L12 20 Z"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <span class="qa-answer-count">0 Answers</span>
                    <button class="btn btn-primary btn-sm answer-btn">Answer</button>
                </div>
            </div>
        </div>

        <!-- Answers Section -->
        <div class="qa-view-answers">
            <!-- Answer Card Template -->
            <div class="qa-answer-card">
                <div class="qa-avatar">
                    <img class="qa-avatar-img" src="placeholder-avatar.jpg" alt="User Avatar">
                </div>
                <div class="qa-answer-header">
                    <span class="qa-username">John Doe</span>
                    <span class="qa-role">Professor</span>
                    <span class="qa-time">1 hour ago</span>
                </div>
                <div class="qa-answer-body">Here's how you solve it step by step...</div>
                <button class="qa-menu-btn" style="display: none;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="12" cy="5" r="1"></circle>
                        <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- three dot menu dropdown -->
<div class="qa-menu-dropdown" style="display: none;">
    <button class="qa-menu-item close-btn">×</button>
    <button class="qa-menu-item edit-btn" style="display: none;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Edit</button>
    <button class="qa-menu-item delete-btn" style="display: none;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="m19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Delete</button>
    <button class="qa-menu-item report-btn" style="display: none;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21V3"></path><path d="M4 15l8-8 8 8"></path></svg> Report</button>
</div>

<!------------------------------------------------------------------>

<!-- Q&A Forum -->
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
     <!-- Skeletal loading figures -->

    <?php for ($i = 0; $i < 3; $i++): ?>
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
                    <button class="qa-menu-btn skeleton-icon" style="display: none;">
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
    <?php endfor; ?>
</div>



<div class="qa-search-results qa-main" aria-hidden="true">
    <!-- for the search results -->
</div>