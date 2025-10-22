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
                        <div class="qa-main">
                            <h3><?= htmlspecialchars($question['title']) ?></h3>
                            <p class="question-snippet"><?= htmlspecialchars(substr($question['body'], 0, 150)) . (strlen($question['body']) > 150 ? '...' : '') ?></p>
                            <div class="qa-tags">
                                <?php foreach ($question['tags'] as $tag): ?>
                                    <span class="tag"><?= htmlspecialchars($tag['tag_name']) ?></span>
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
                            <a href="/unihelper/dashboard/<?= $dashboardType ?>/qa-forum/question/<?= $question['post_id'] ?>" class="btn btn-primary">
                                <span class="btn-text">Answer</span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
            tagsHidden.value = JSON.stringify(tags);
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
});
</script>


