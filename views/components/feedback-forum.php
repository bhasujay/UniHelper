<?php
require_once __DIR__ . '/../../models/User.php';

use app\models\User;

$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $userModel = new User();
    $currentUser = $userModel->findById($_SESSION['user_id']);
}

$is_admin = ($currentUser && $currentUser->role === 'role-admin') ? 1 : 0;
$defaultName = '';

if ($currentUser) {
    $defaultName = trim(($currentUser->firstName ?? '') . ' ' . ($currentUser->lastName ?? ''));
}
?>

<style>
.feedback-forum-container {
    display: grid;
    gap: 1rem;
    padding: 1.25rem;
    color: var(--foreground);
}

.feedback-layout {
    display: grid;
    grid-template-columns: minmax(300px, 0.95fr) minmax(0, 1.4fr);
    gap: 1rem;
    align-items: start;
}

.feedback-layout.admin-only {
    grid-template-columns: 1fr;
}

.feedback-panel {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.9rem;
    padding: 1.15rem;
}

.feedback-form-section {
    position: sticky;
    top: 1rem;
}

.feedback-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 1rem;
    margin-bottom: 1rem;
}

.feedback-panel-header h2 {
    font-size: 1.15rem;
    line-height: 1.3;
}

.feedback-panel-header p {
    margin-top: 0.2rem;
    font-size: 0.9rem;
    color: var(--muted-foreground);
}

.feedback-form {
    display: grid;
    gap: 0.9rem;
}

.feedback-form .form-group {
    display: grid;
    gap: 0.45rem;
}

.feedback-form label {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--foreground);
}

.feedback-form input,
.feedback-form textarea,
.feedback-search-input {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 0.6rem;
    background: var(--key);
    color: var(--foreground);
    padding: 0.72rem 0.85rem;
    font: inherit;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.feedback-form input::placeholder,
.feedback-form textarea::placeholder,
.feedback-search-input::placeholder {
    color: var(--muted-foreground);
}

.feedback-form input:focus,
.feedback-form textarea:focus,
.feedback-search-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(0, 170, 255, 0.18);
}

.feedback-form textarea {
    min-height: 160px;
    resize: vertical;
}

.feedback-rating-input {
    display: inline-flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 0.2rem;
}

.feedback-rating-input input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.feedback-rating-input label {
    font-size: 1.8rem;
    line-height: 1;
    color: rgba(148, 163, 184, 0.55);
    cursor: pointer;
    user-select: none;
    transition: color 0.18s ease, transform 0.18s ease;
}

.feedback-rating-input label:hover,
.feedback-rating-input label:hover ~ label,
.feedback-rating-input input[type="radio"]:checked ~ label {
    color: #facc15;
}

.feedback-rating-input label:active {
    transform: scale(0.96);
}

.feedback-form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
    margin-top: 0.2rem;
}

.feedback-search-wrap {
    width: min(320px, 100%);
}

.feedback-list {
    display: grid;
    gap: 0.85rem;
    max-height: 72vh;
    overflow-y: auto;
    padding-right: 0.2rem;
}

.feedback-item {
    display: grid;
    gap: 0.75rem;
    padding: 1rem;
    border: 1px solid var(--border);
    border-radius: 0.8rem;
    background: var(--text_background);
}

.feedback-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.8rem;
}

.feedback-item-title {
    font-size: 1.04rem;
    line-height: 1.35;
    color: var(--foreground);
    word-break: break-word;
}

.feedback-item-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    font-size: 0.86rem;
    color: var(--muted-foreground);
}

.feedback-item-rating {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
}

.feedback-item-rating span {
    font-size: 0.98rem;
    line-height: 1;
    color: #facc15;
}

.feedback-item-rating .feedback-star-empty {
    color: rgba(148, 163, 184, 0.65);
}

.feedback-item-content {
    white-space: pre-wrap;
    color: var(--foreground);
    line-height: 1.5;
    word-break: break-word;
}

.feedback-item-actions {
    display: flex;
    gap: 0.5rem;
}

.feedback-delete-btn {
    padding: 0.42rem 0.82rem;
    color: #d76e6e;
    border-color: #7f4646;
}

.feedback-delete-btn:hover {
    border-color: #d76e6e;
}

.feedback-empty-state {
    padding: 1.45rem 1rem;
    border: 1px dashed var(--border);
    border-radius: 0.8rem;
    background: var(--text_background);
    text-align: center;
}

.feedback-empty-state h3 {
    font-size: 1.02rem;
    margin-bottom: 0.35rem;
}

.feedback-empty-state p {
    color: var(--muted-foreground);
    margin: 0 auto;
    max-width: 46ch;
}

@media (max-width: 980px) {
    .feedback-layout {
        grid-template-columns: 1fr;
    }

    .feedback-form-section {
        position: static;
    }

    .feedback-list {
        max-height: none;
        overflow-y: visible;
        padding-right: 0;
    }
}

@media (max-width: 640px) {
    .feedback-forum-container {
        padding: 1rem;
    }

    .feedback-panel {
        padding: 1rem;
    }

    .feedback-panel-header {
        flex-direction: column;
        align-items: stretch;
    }

    .feedback-form-actions .btn,
    .feedback-item-actions .btn {
        width: 100%;
        justify-content: center;
    }

    .feedback-item-header {
        flex-direction: column;
    }
}
</style>

<div class="feedback-forum-container" data-is-admin="<?= $is_admin ? '1' : '0' ?>">
    <div class="feedback-layout <?= $is_admin ? 'admin-only' : '' ?>">
        <?php if (!$is_admin): ?>
            <section class="feedback-panel feedback-form-section">
                <div class="feedback-panel-header">
                    <div>
                        <h2>Send Your Feedback</h2>
                        <p>Post once and track what has been shared by everyone.</p>
                    </div>
                </div>

                <form id="feedbackForm" class="feedback-form">
                    <div class="form-group">
                        <label for="yourName">Your Name</label>
                        <input
                            type="text"
                            id="yourName"
                            name="name"
                            placeholder="Enter your name"
                            value="<?= htmlspecialchars($defaultName) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="postTitle">Post Title</label>
                        <input
                            type="text"
                            id="postTitle"
                            name="title"
                            placeholder="Summarize the feedback clearly"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="rating5">Your Rating</label>
                        <div class="feedback-rating-input" role="radiogroup" aria-label="Rate UniHelper from 1 to 5 stars">
                            <input type="radio" id="rating5" name="rating" value="5" checked required>
                            <label for="rating5" title="5 stars">&#9733;</label>

                            <input type="radio" id="rating4" name="rating" value="4">
                            <label for="rating4" title="4 stars">&#9733;</label>

                            <input type="radio" id="rating3" name="rating" value="3">
                            <label for="rating3" title="3 stars">&#9733;</label>

                            <input type="radio" id="rating2" name="rating" value="2">
                            <label for="rating2" title="2 stars">&#9733;</label>

                            <input type="radio" id="rating1" name="rating" value="1">
                            <label for="rating1" title="1 star">&#9733;</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="feedbackMessage">Feedback Message</label>
                        <textarea
                            id="feedbackMessage"
                            name="message"
                            placeholder="Write the issue, idea, or improvement suggestion"
                            required
                        ></textarea>
                    </div>

                    <div class="feedback-form-actions">
                        <button type="submit" class="btn btn-primary">Submit Feedback</button>
                        <button type="reset" class="btn btn-outline">Clear</button>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <section class="feedback-panel feedback-list-panel">
            <div class="feedback-panel-header">
                <div>
                    <h2><?= $is_admin ? 'All Feedback' : 'Community Feedback' ?></h2>
                    <p>
                        <?= $is_admin
                            ? 'Read all submissions, search quickly, and remove items when needed.'
                            : 'Read-only feed of the latest user submissions.' ?>
                    </p>
                </div>

                <?php if ($is_admin): ?>
                    <div class="feedback-search-wrap">
                        <input
                            type="search"
                            id="feedbackSearchInput"
                            class="feedback-search-input"
                            placeholder="Search by name, title, or message"
                            aria-label="Search feedback"
                        >
                    </div>
                <?php endif; ?>
            </div>

            <div id="feedbackList" class="feedback-list">
                <div class="feedback-empty-state">
                    <h3>Loading feedback</h3>
                    <p>The board is fetching the latest posts.</p>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const forumContainer = document.querySelector('.feedback-forum-container');
    if (!forumContainer) {
        return;
    }

    const isAdmin = forumContainer.getAttribute('data-is-admin') === '1';
    const feedbackForm = document.getElementById('feedbackForm');
    const feedbackList = document.getElementById('feedbackList');
    const feedbackSearchInput = document.getElementById('feedbackSearchInput');
    const apiBaseUrl = '/UniHelper/api';
    let feedbackCache = [];

    function buildFeedbackApiUrl(action, params = {}) {
        const query = new URLSearchParams({
            controller: 'feedbackController',
            action,
            ...params
        });

        return `${apiBaseUrl}?${query.toString()}`;
    }

    function notify(message, type = 'success') {
        if (typeof window.showToast === 'function') {
            const toastType = type === 'error' ? 'error' : 'success';
            window.showToast(message, toastType);
            return;
        }

        if (type === 'error') {
            console.error(message);
            return;
        }

        console.log(message);
    }

    function renderEmptyState(title, message) {
        feedbackList.innerHTML = `
            <div class="feedback-empty-state">
                <h3>${title}</h3>
                <p>${message}</p>
            </div>
        `;
    }

    function validateFeedbackForm(formData) {
        const errors = [];
        const name = (formData.name || '').trim();
        const title = (formData.title || '').trim();
        const message = (formData.message || '').trim();
        const rating = Number(formData.rating);

        if (!name) {
            errors.push('Name is required');
        } else if (name.length < 2) {
            errors.push('Name must be at least 2 characters');
        } else if (name.length > 100) {
            errors.push('Name must not exceed 100 characters');
        }

        if (!title) {
            errors.push('Post title is required');
        } else if (title.length < 5) {
            errors.push('Post title must be at least 5 characters');
        } else if (title.length > 255) {
            errors.push('Post title must not exceed 255 characters');
        }

        if (!message) {
            errors.push('Feedback message is required');
        } else if (message.length < 10) {
            errors.push('Feedback message must be at least 10 characters');
        } else if (message.length > 5000) {
            errors.push('Feedback message must not exceed 5000 characters');
        }

        if (!Number.isInteger(rating) || rating < 1 || rating > 5) {
            errors.push('Please select a rating between 1 and 5 stars');
        }

        return errors;
    }

    function formatDate(rawValue) {
        const parsedDate = rawValue ? new Date(rawValue) : new Date();

        if (Number.isNaN(parsedDate.getTime())) {
            return new Date().toLocaleDateString();
        }

        return parsedDate.toLocaleDateString();
    }

    function normalizeRating(rawValue) {
        const parsed = Number(rawValue);

        if (!Number.isFinite(parsed)) {
            return 0;
        }

        const rounded = Math.round(parsed);
        if (rounded < 1) {
            return 0;
        }

        if (rounded > 5) {
            return 5;
        }

        return rounded;
    }

    function createRatingElement(ratingValue) {
        const rating = normalizeRating(ratingValue);
        const ratingElement = document.createElement('div');
        ratingElement.className = 'feedback-item-rating';
        ratingElement.setAttribute('aria-label', rating > 0 ? `Rating ${rating} out of 5` : 'No rating');

        for (let starIndex = 1; starIndex <= 5; starIndex += 1) {
            const star = document.createElement('span');
            star.textContent = '\u2605';

            if (starIndex > rating) {
                star.className = 'feedback-star-empty';
            }

            ratingElement.appendChild(star);
        }

        return ratingElement;
    }

    function createFeedbackItem(feedback) {
        const item = document.createElement('article');
        item.className = 'feedback-item';
        item.setAttribute('data-feedback-id', String(feedback.id || ''));

        const header = document.createElement('div');
        header.className = 'feedback-item-header';

        const title = document.createElement('h3');
        title.className = 'feedback-item-title';
        title.textContent = feedback.title || 'Untitled feedback';
        header.appendChild(title);

        if (isAdmin) {
            const actions = document.createElement('div');
            actions.className = 'feedback-item-actions';

            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'btn btn-outline feedback-delete-btn';
            deleteButton.textContent = 'Delete';
            deleteButton.addEventListener('click', function () {
                deleteFeedback(feedback.id);
            });

            actions.appendChild(deleteButton);
            header.appendChild(actions);
        }

        const meta = document.createElement('div');
        meta.className = 'feedback-item-meta';

        const author = document.createElement('span');
        author.textContent = `By ${feedback.name || 'Unknown user'}`;

        const date = document.createElement('span');
        date.textContent = formatDate(feedback.created_at);

        meta.appendChild(author);
        meta.appendChild(date);

        const rating = createRatingElement(feedback.rating);

        const content = document.createElement('p');
        content.className = 'feedback-item-content';
        content.textContent = feedback.message || '';

        item.appendChild(header);
        item.appendChild(meta);
        item.appendChild(rating);
        item.appendChild(content);

        return item;
    }

    function renderFeedbackList(feedbackItems, isFiltered = false) {
        feedbackList.innerHTML = '';

        if (!Array.isArray(feedbackItems) || feedbackItems.length === 0) {
            if (isFiltered) {
                renderEmptyState('No matches found', 'Try a different keyword in the search box.');
                return;
            }

            if (isAdmin) {
                renderEmptyState('No feedback available', 'User submissions will appear here.');
                return;
            }

            renderEmptyState('No feedback yet', 'Be the first person to share an idea or issue.');
            return;
        }

        const fragment = document.createDocumentFragment();

        feedbackItems.forEach(function (feedback) {
            fragment.appendChild(createFeedbackItem(feedback));
        });

        feedbackList.appendChild(fragment);
    }

    function applySearchAndRender() {
        if (!isAdmin || !feedbackSearchInput) {
            renderFeedbackList(feedbackCache, false);
            return;
        }

        const searchTerm = feedbackSearchInput.value.trim().toLowerCase();
        if (!searchTerm) {
            renderFeedbackList(feedbackCache, false);
            return;
        }

        const filtered = feedbackCache.filter(function (feedback) {
            return [feedback.name, feedback.title, feedback.message, feedback.rating].some(function (value) {
                return String(value || '').toLowerCase().includes(searchTerm);
            });
        });

        renderFeedbackList(filtered, true);
    }

    async function requestConfirmation(message) {
        if (typeof window.confirm !== 'function') {
            return true;
        }

        const result = window.confirm(message);
        if (result && typeof result.then === 'function') {
            return Boolean(await result);
        }

        return Boolean(result);
    }

    async function deleteFeedback(feedbackId) {
        if (!isAdmin || !feedbackId) {
            return;
        }

        const confirmed = await requestConfirmation('Are you sure you want to delete this feedback?');
        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(buildFeedbackApiUrl('deleteFeedback'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ id: feedbackId })
            });

            if (!response.ok) {
                throw new Error('Failed to delete feedback: ' + response.status);
            }

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Failed to delete feedback');
            }

            feedbackCache = feedbackCache.filter(function (feedback) {
                return String(feedback.id) !== String(feedbackId);
            });

            applySearchAndRender();
            notify('Feedback deleted successfully!', 'success');
        } catch (error) {
            console.error('Error deleting feedback:', error);
            notify(error.message || 'An error occurred while deleting feedback.', 'error');
        }
    }

    async function loadFeedback() {
        try {
            const response = await fetch(buildFeedbackApiUrl('getFeedback'), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Failed to load feedback: ' + response.status);
            }

            const data = await response.json();
            if (!data.success || !Array.isArray(data.data)) {
                throw new Error(data.message || 'Failed to load feedback');
            }

            feedbackCache = data.data.slice().sort(function (a, b) {
                return new Date(b.created_at) - new Date(a.created_at);
            });

            applySearchAndRender();
        } catch (error) {
            console.error('Error loading feedback:', error);
            renderEmptyState('Failed to load feedback', 'Please refresh the page and try again.');
            notify('Failed to load feedback. Please refresh the page.', 'error');
        }
    }

    async function submitFeedback(feedbackData) {
        if (!feedbackForm) {
            return;
        }

        const submitButton = feedbackForm.querySelector('.btn-primary');
        const originalText = submitButton ? submitButton.textContent : '';

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';
        }

        try {
            const response = await fetch(buildFeedbackApiUrl('submitFeedback'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    name: feedbackData.name,
                    title: feedbackData.title,
                    message: feedbackData.message,
                    rating: feedbackData.rating
                })
            });

            if (!response.ok) {
                throw new Error('Failed to submit feedback: ' + response.status);
            }

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Failed to submit feedback');
            }

            feedbackForm.reset();
            const defaultRatingInput = feedbackForm.querySelector('input[name="rating"][value="5"]');
            if (defaultRatingInput) {
                defaultRatingInput.checked = true;
            }
            notify('Feedback submitted successfully!', 'success');
            await loadFeedback();
        } catch (error) {
            console.error('Error submitting feedback:', error);
            notify(error.message || 'An error occurred while submitting feedback.', 'error');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        }
    }

    if (feedbackSearchInput) {
        feedbackSearchInput.addEventListener('input', applySearchAndRender);
    }

    if (feedbackForm) {
        feedbackForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const nameInput = document.getElementById('yourName');
            const titleInput = document.getElementById('postTitle');
            const messageInput = document.getElementById('feedbackMessage');
            const ratingInput = feedbackForm.querySelector('input[name="rating"]:checked');

            const formData = {
                name: nameInput ? nameInput.value : '',
                title: titleInput ? titleInput.value : '',
                message: messageInput ? messageInput.value : '',
                rating: ratingInput ? ratingInput.value : ''
            };

            const validationErrors = validateFeedbackForm(formData);
            if (validationErrors.length > 0) {
                notify(validationErrors.join(', '), 'error');
                return;
            }

            await submitFeedback(formData);
        });
    }

    loadFeedback();
});
</script>