<?php
use app\models\QnaPost;

// Get the correct dashboard type for the form action URL
$dashboardType = '';
if (isset($user) && $user->role === 'role-applicant') {
    $dashboardType = 'applicant';
} elseif (isset($user) && $user->role === 'role-undergrad') {
    $dashboardType = 'undergraduate';
} elseif (isset($user) && $user->role === 'role-profile') {
    $dashboardType = 'profile';
} else {
    $dashboardType = 'applicant'; // Default fallback
}

// Initialize QnaPost model
$qnaModel = new QnaPost();

// Get recent questions
$questions = $qnaModel->getRecentQuestions(10, 0);

// Get all tags for the question form
$allTags = $qnaModel->getAllTags();

// Default view is browse questions
$activeView = isset($_GET['view']) ? $_GET['view'] : 'browse';

// Check for success/error messages
$successMessage = '';
$errorMessage = '';

if (isset($_GET['success']) && $_GET['success'] === 'question_posted') {
    $successMessage = 'Your question has been posted successfully!';
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'empty_fields':
            $errorMessage = 'Please fill in all required fields.';
            break;
        case 'post_failed':
            $errorMessage = 'There was a problem posting your question. Please try again.';
            break;
    }
}
?>

<div class="component-header">
    <h1>Q&amp;A Forum</h1>
    <p>Ask questions about university applications, admissions, and get answers from the community</p>
</div>

<?php if (!empty($successMessage)): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($successMessage) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($errorMessage) ?>
    </div>
<?php endif; ?>

<!-- Tab Navigation -->
<div class="qa-tabs">
    <button type="button" class="tab-button <?= $activeView === 'browse' ? 'active' : '' ?>" data-view="browse-questions">Browse Questions</button>
    <button type="button" class="tab-button <?= $activeView === 'ask' ? 'active' : '' ?>" data-view="ask-question">Ask a Question</button>
</div>

<!-- Ask Question View -->
<div id="ask-question-view" class="qa-view" style="display: <?= $activeView === 'ask' ? 'block' : 'none' ?>">
    <div class="ask-question-container">
        <form id="questionForm" method="post" action="/unihelper/dashboard/<?= $dashboardType ?>/qa-forum/post">
            <input type="hidden" name="action" value="ask_question">
            <div class="form-group">
                <label for="questionTitle">Question Title</label>
                <input type="text" id="questionTitle" name="title" placeholder="What's your question? Be specific." required>
            </div>
            <div class="form-group">
                <label for="questionBody">Question Details</label>
                <textarea id="questionBody" name="body" placeholder="Provide more context about your question..." rows="8" required></textarea>
            </div>
            <div class="form-group">
                <label for="questionTags">Tags</label>
                <div class="tags-input-container">
                    <input type="text" id="tagInput" placeholder="Add a tag (press Enter to add)">
                    <div class="tags-container" id="tagsContainer"></div>
                    <input type="hidden" name="tags" id="tagsHidden" value="[]">
                </div>
                <div class="tag-suggestions">
                    <p>Suggested tags:</p>
                    <div class="suggested-tags">
                        <?php foreach ($allTags as $tag): ?>
                            <span class="tag suggested-tag" data-tag-id="<?= $tag['tag_id'] ?>"><?= htmlspecialchars($tag['tag_name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Post Your Question</button>
            </div>
        </form>
    </div>
</div>

<!-- Browse Questions View -->
<div id="browse-questions-view" class="qa-view" style="display: <?= $activeView === 'browse' ? 'block' : 'none' ?>">
    <!-- Question List -->
    <div class="qa-container">
        <?php if (empty($questions)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 16v-4M12 8h.01"></path>
                    </svg>
                </div>
                <h3>No questions yet</h3>
                <p>Be the first to ask a question in the community.</p>
                <button type="button" class="btn btn-primary ask-question-btn" data-view="ask-question">
                    <span class="btn-text">Ask a Question</span>
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($questions as $question): ?>
                <div class="dashboard-card q-and-a-card">
                    <div class="qa-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?= isset($question['score']) ? ($question['score'] > 0 ? '+' . $question['score'] : $question['score']) : '0' ?></span>
                            <span class="stat-label">Score</span>
                        </div>
                        <div class="stat-item <?= $question['answer_count'] > 0 ? 'answers-prominent' : '' ?>">
                            <span class="stat-number"><?= $question['answer_count'] ?></span>
                            <span class="stat-label">Answers</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= isset($question['views']) ? $question['views'] : '0' ?></span>
                            <span class="stat-label">Views</span>
                        </div>
                    </div>

                    <div class="qa-content">
                        <!-- Add three-dot menu for author only -->
                        <?php if (isset($user) && isset($question['user_id']) && $user->id == $question['user_id']): ?>
                        <div class="three-dot-menu">
                            <button class="three-dot-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="6" r="2"></circle>
                                    <circle cx="12" cy="12" r="2"></circle>
                                    <circle cx="12" cy="18" r="2"></circle>
                                </svg>
                            </button>
                            <div class="qa-qa-dropdown-menu">
                                <a href="#" class="qa-dropdown-item edit-question" data-question-id="<?= $question['post_id'] ?>">Edit Question</a>
                                <a href="#" class="qa-dropdown-item delete-question" data-question-id="<?= $question['post_id'] ?>">Delete Question</a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="qa-main">
                            <h3><?= htmlspecialchars_decode($question['title'], ENT_QUOTES) ?></h3>
                            <p class="question-snippet"><?= htmlspecialchars_decode(substr($question['body'], 0, 450), ENT_QUOTES) . (strlen($question['body']) > 450 ? '...' : '') ?></p>
                            <div class="qa-tags">
                                <?php foreach ($question['tags'] as $tag): ?>
                                    <span class="tag"><?= htmlspecialchars_decode($tag['tag_name'], ENT_QUOTES) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="qa-footer">
                            <div class="qa-author">
                                <?php if (isset($question['profile_picture']) && $question['profile_picture']): ?>
                                    <img src="<?= htmlspecialchars($question['profile_picture']) ?>" alt="Author's profile picture">
                                <?php else: ?>
                                    <div class="author-initial"><?= substr($question['first_name'] ?? 'U', 0, 1) ?></div>
                                <?php endif; ?>
                                <span><?= htmlspecialchars(($question['first_name'] ?? 'User') . ' ' . substr($question['last_name'] ?? '', 0, 1)) ?></span>
                            </div>
                            <div class="qa-action-buttons">
                                <a href="#" class="btn btn-outline view-answers-btn" data-question-id="<?= $question['post_id'] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <span class="btn-text">View Answers (<?= $question['answer_count'] ?>)</span>
                                </a>
                                <a href="#" class="btn btn-primary answer-btn" data-question-id="<?= $question['post_id'] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                                    </svg>
                                    <span class="btn-text">Answer</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Question View -->
<div id="edit-question-view" class="qa-view" style="display: none">
    <div class="ask-question-container">
        <h2>Edit Question</h2>
        <form id="editQuestionForm" method="post" action="/unihelper/dashboard/<?= $dashboardType ?>/qa-forum/post">
            <input type="hidden" name="action" value="update_question">
            <input type="hidden" name="post_id" id="editPostId">
            <div class="form-group">
                <label for="editQuestionTitle">Question Title</label>
                <input type="text" id="editQuestionTitle" name="title" placeholder="What's your question? Be specific." required>
            </div>
            <div class="form-group">
                <label for="editQuestionBody">Question Details</label>
                <textarea id="editQuestionBody" name="body" placeholder="Provide more context about your question..." rows="8" required></textarea>
            </div>
            <div class="form-group">
                <label for="editQuestionTags">Tags</label>
                <div class="tags-input-container">
                    <input type="text" id="editTagInput" placeholder="Add a tag (press Enter to add)">
                    <div class="tags-container" id="editTagsContainer"></div>
                    <input type="hidden" name="tags" id="editTagsHidden" value="[]">
                </div>
                <div class="tag-suggestions">
                    <p>Suggested tags:</p>
                    <div class="suggested-tags">
                        <?php foreach ($allTags as $tag): ?>
                            <span class="tag edit-suggested-tag" data-tag-id="<?= $tag['tag_id'] ?>"><?= htmlspecialchars($tag['tag_name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" id="cancelEditBtn" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Question</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Tab styling */
.qa-tabs {
    display: flex;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid var(--border);
}

.tab-button {
    background: none;
    border: none;
    padding: 1rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
    color: var(--muted-foreground);
    cursor: pointer;
    position: relative;
    transition: color 0.2s;
}

.tab-button:hover {
    color: var(--foreground);
}

.tab-button.active {
    color: var(--primary);
}

.tab-button.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: var(--primary);
}

.qa-view {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.ask-question-container {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 2rem;
}

/* Make sure empty state button is aligned with tab style */
.empty-state .btn {
    margin-top: 1rem;
}

/* Make Q&A cards MUCH MORE COMPACT - VERTICALLY SHORTER */
.q-and-a-card {
    padding: 0.5rem 0.75rem !important;
    margin-bottom: 0.5rem !important;
}

.qa-stats {
    gap: 0.5rem !important;
    margin-right: 0.75rem !important;
}

.qa-stats .stat-item {
    padding: 0.25rem 0 !important;
}

.qa-stats .stat-number {
    font-size: 1rem !important;
    margin-bottom: 0 !important;
    line-height: 1.2 !important;
}

.qa-stats .stat-label {
    font-size: 0.65rem !important;
    line-height: 1 !important;
}

.qa-main h3 {
    font-size: 1rem !important;
    margin-bottom: 0.25rem !important;
    line-height: 1.3 !important;
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
    hyphens: auto;
    max-width: 100%;
}

.qa-main .question-snippet {
    font-size: 0.8rem !important;
    line-height: 1.4 !important;
    margin-bottom: 0.25rem !important;
    color: var(--muted-foreground);
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
    hyphens: auto;
    max-width: 100%;
    white-space: pre-wrap;
}

.qa-tags {
    margin-bottom: 0.25rem !important;
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.qa-tags .tag {
    padding: 0.15rem 0.5rem !important;
    font-size: 0.7rem !important;
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
    max-width: 100%;
}

.qa-footer {
    margin-top: 0.5rem !important;
    padding-top: 0.5rem !important;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.qa-author {
    font-size: 0.75rem !important;
}

.qa-author img,
.qa-author .author-initial {
    width: 24px !important;
    height: 24px !important;
    font-size: 0.75rem !important;
}

/* Three-dot menu styling */
.three-dot-menu {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    z-index: 10;
}

.three-dot-button {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0.2rem;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--muted-foreground);
}

.three-dot-button:hover {
    background-color: var(--accent);
    color: var(--accent-foreground);
}

.qa-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    min-width: 150px;
    display: none;
    z-index: 100;
}

.qa-dropdown-menu.show {
    display: block;
}

.qa-dropdown-item {
    display: block;
    padding: 0.5rem 1rem;
    color: var(--foreground);
    text-decoration: none;
    font-size: 0.875rem;
}

.qa-dropdown-item:hover {
    background-color: var(--accent);
}

.qa-dropdown-item.delete-question {
    color: var(--destructive);
}

/* Action buttons styling */
.qa-content {
    position: relative;
}

.qa-action-buttons {
    display: flex;
    gap: 0.4rem;
    margin-top: 0;
    flex-wrap: wrap;
}

.qa-action-buttons .btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    padding: 0.3rem 0.7rem;
    border-radius: 0.4rem;
    font-weight: 500;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 0.8rem;
}

.qa-action-buttons .btn svg {
    stroke: currentColor;
    width: 13px;
    height: 13px;
}

.qa-action-buttons .btn-outline {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.318);
    color: var(--text);
}

.qa-action-buttons .btn-outline:hover {
    background: var(--accent);
    transform: translateY(-2px);
}

.qa-action-buttons .btn-primary {
    background: var(--gradient-primary);
    border: none;
    color: rgb(0, 0, 0);
}

.qa-action-buttons .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--glow-primary), 0 10px 25px rgba(0, 0, 0, 0.3);
}

.qa-action-buttons .btn-text {
    color: inherit !important;
    font-size: 0.75rem;
    font-weight: 600;
}

.qa-action-buttons .btn-primary .btn-text {
    color: #111 !important;
}

/* Loading indicator */
body.loading::after {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

body.loading::before {
    content: "";
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 50px;
    height: 50px;
    border: 5px solid var(--primary);
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    z-index: 10000;
}

@keyframes spin {
    from { transform: translate(-50%, -50%) rotate(0deg); }
    to { transform: translate(-50%, -50%) rotate(360deg); }
}

/* Edit form styles */
#edit-question-view h2 {
    margin-bottom: 1.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM loaded");
    
    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-button');
    const qaViews = document.querySelectorAll('.qa-view');
    const askQuestionBtn = document.querySelector('.ask-question-btn');
    
    function showView(viewId) {
        console.log("Showing view:", viewId);
        
        // Hide all views
        qaViews.forEach(view => {
            view.style.display = 'none';
        });
        
        // Show the selected view
        document.getElementById(viewId + '-view').style.display = 'block';
        
        // Update active tab
        tabButtons.forEach(tab => {
            if (tab.dataset.view === viewId) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });
    }
    
    // Add click event to tab buttons
    tabButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            console.log("Tab clicked:", this.dataset.view);
            e.preventDefault();
            showView(this.dataset.view);
        });
    });
    
    // Add click event to "Ask a Question" button in empty state
    if (askQuestionBtn) {
        askQuestionBtn.addEventListener('click', function(e) {
            console.log("Ask question button clicked");
            e.preventDefault();
            showView(this.dataset.view);
        });
    }
    
    // Tags input functionality
    const tagInput = document.getElementById('tagInput');
    const tagsContainer = document.getElementById('tagsContainer');
    const tagsHidden = document.getElementById('tagsHidden');
    const suggestedTags = document.querySelectorAll('.suggested-tag');
    
    let tags = [];
    
    function updateTags() {
        // Update the hidden field with JSON string
        if (tagsHidden) {
            // Prevent HTML encoding by using a direct string assignment
            tagsHidden.value = JSON.stringify(tags);
            console.log("Tags hidden value updated:", tagsHidden.value);
        }
        
        // Update the visual tags
        if (tagsContainer) {
            tagsContainer.innerHTML = '';
            tags.forEach((tag, index) => {
                const tagElement = document.createElement('span');
                tagElement.className = 'tag';
                tagElement.innerHTML = `${tag} <span class="tag-remove" data-index="${index}">&times;</span>`;
                tagsContainer.appendChild(tagElement);
            });
            
            // Add event listeners for remove buttons
            document.querySelectorAll('.tag-remove').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    tags.splice(index, 1);
                    updateTags();
                });
            });
        }
    }
    
    // Add tag when pressing Enter
    if (tagInput) {
        tagInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                
                const tag = tagInput.value.trim().toLowerCase();
                if (tag && !tags.includes(tag)) {
                    tags.push(tag);
                    updateTags();
                    tagInput.value = '';
                }
            }
        });
    }
    
    // Add suggested tag when clicked
    suggestedTags.forEach(tagElement => {
        tagElement.addEventListener('click', function() {
            const tag = this.textContent.trim().toLowerCase();
            if (!tags.includes(tag)) {
                tags.push(tag);
                updateTags();
            }
        });
    });
    
    // Three-dot menu functionality
    const threeDotsButtons = document.querySelectorAll('.three-dot-button');
    
    // Close all dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.three-dot-menu')) {
            document.querySelectorAll('.qa-dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
    
    // Toggle dropdown when clicking three-dots
    threeDotsButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.nextElementSibling;
            
            // Close all other dropdowns
            document.querySelectorAll('.qa-dropdown-menu').forEach(menu => {
                if (menu !== dropdown) {
                    menu.classList.remove('show');
                }
            });
            
            // Toggle current dropdown
            dropdown.classList.toggle('show');
        });
    });
    
    // Handle edit/delete button clicks
    document.querySelectorAll('.edit-question').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const questionId = this.getAttribute('data-question-id');
            console.log('Edit question:', questionId);
            
            // Fetch the question data from the server
            fetchQuestionData(questionId);
        });
    });
    
    // Function to fetch question data for editing
    function fetchQuestionData(questionId) {
        // Show loading state
        document.body.classList.add('loading');
        
        // Make API request to get question data
        fetch(`/unihelper/dashboard/<?= $dashboardType ?>/qa-forum/question/${questionId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Populate the edit form with question data
                document.getElementById('editPostId').value = data.post_id;
                document.getElementById('editQuestionTitle').value = data.title;
                document.getElementById('editQuestionBody').value = data.body;
                
                // Handle tags
                let editTags = [];
                if (data.tags && Array.isArray(data.tags)) {
                    editTags = data.tags.map(tag => tag.tag_name);
                }
                
                // Update the edit form tags
                editTagsArray = editTags;
                updateEditTags();
                
                // Show the edit form
                showView('edit-question');
                
                // Remove loading state
                document.body.classList.remove('loading');
            })
            .catch(error => {
                console.error('Error fetching question data:', error);
                alert('Failed to load question data. Please try again.');
                document.body.classList.remove('loading');
            });
    }
    
    // Cancel edit button
    document.getElementById('cancelEditBtn').addEventListener('click', function() {
        showView('browse-questions');
    });
    
    // Tab functionality
    function showView(viewId) {
        console.log("Showing view:", viewId);
        
        // Hide all views
        qaViews.forEach(view => {
            view.style.display = 'none';
        });
        
        // Show the selected view
        document.getElementById(viewId + '-view').style.display = 'block';
        
        // Update active tab (only for browse and ask tabs)
        if (viewId === 'browse-questions' || viewId === 'ask-question') {
            tabButtons.forEach(tab => {
                if (tab.dataset.view === viewId) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
        }
    }
    
    // Edit form tags functionality
    const editTagInput = document.getElementById('editTagInput');
    const editTagsContainer = document.getElementById('editTagsContainer');
    const editTagsHidden = document.getElementById('editTagsHidden');
    const editSuggestedTags = document.querySelectorAll('.edit-suggested-tag');
    
    let editTagsArray = [];
    
    function updateEditTags() {
        // Update the hidden field with JSON string
        if (editTagsHidden) {
            // Prevent HTML encoding by using a direct string assignment
            editTagsHidden.value = JSON.stringify(editTagsArray);
            console.log("Edit tags hidden value updated:", editTagsHidden.value);
        } else {
            console.error("editTagsHidden element not found");
        }
        
        // Update the visual tags
        if (editTagsContainer) {
            editTagsContainer.innerHTML = '';
            editTagsArray.forEach((tag, index) => {
                const tagElement = document.createElement('span');
                tagElement.className = 'tag';
                tagElement.innerHTML = `${tag} <span class="tag-remove" data-index="${index}">&times;</span>`;
                editTagsContainer.appendChild(tagElement);
            });
            
            // Add event listeners for remove buttons
            document.querySelectorAll('#editTagsContainer .tag-remove').forEach(button => {
                button.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    editTagsArray.splice(index, 1);
                    updateEditTags();
                });
            });
        }
    }
    
    // Add tag when pressing Enter in edit form
    if (editTagInput) {
        editTagInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                
                const tag = editTagInput.value.trim().toLowerCase();
                if (tag && !editTagsArray.includes(tag)) {
                    editTagsArray.push(tag);
                    updateEditTags();
                    editTagInput.value = '';
                }
            }
        });
    }
    
    // Add suggested tag when clicked in edit form
    editSuggestedTags.forEach(tagElement => {
        tagElement.addEventListener('click', function() {
            const tag = this.textContent.trim().toLowerCase();
            if (!editTagsArray.includes(tag)) {
                editTagsArray.push(tag);
                updateEditTags();
            }
        });
    });
    
    // Handle delete button clicks
    document.querySelectorAll('.delete-question').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const questionId = this.getAttribute('data-question-id');
            if (confirm('Are you sure you want to delete this question?')) {
                // Redirect to the delete route
                window.location.href = `/unihelper/dashboard/<?= $dashboardType ?>/qa-forum/delete/${questionId}`;
            }
        });
    });
    
    // Handle action button clicks
    document.querySelectorAll('.view-answers-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const questionId = this.getAttribute('data-question-id');
            console.log('View answers for question:', questionId);
            // Placeholder for view answers functionality
        });
    });
    
    document.querySelectorAll('.answer-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const questionId = this.getAttribute('data-question-id');
            console.log('Answer question:', questionId);
            // Placeholder for answer functionality
        });
    });
    
    // Form submission handler
    const questionForm = document.getElementById('questionForm');
    if (questionForm) {
        questionForm.addEventListener('submit', function(e) {
            // Ensure tags are properly updated before submission
            if (tagsHidden) {
                console.log("Before submit, tags array:", tags);
                tagsHidden.value = JSON.stringify(tags);
                console.log("Final tags value:", tagsHidden.value);
            }
        });
    }
    
    // Add form submission handler for the edit form
    const editQuestionForm = document.getElementById('editQuestionForm');
    if (editQuestionForm) {
        editQuestionForm.addEventListener('submit', function(e) {
            // Ensure tags are properly updated before submission
            if (editTagsHidden) {
                console.log("Before edit submit, tags array:", editTagsArray);
                editTagsHidden.value = JSON.stringify(editTagsArray);
                console.log("Final edit tags value:", editTagsHidden.value);
            }
        });
    }
});
</script>


