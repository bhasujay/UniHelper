<?php
// Feedback Forum Component
?>

<style>
.feedback-forum-container {
    position: relative;
    display: grid;
    gap: 1.5rem;
    padding: 1.5rem;
    color: var(--foreground);
}

.feedback-forum-container::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at top left, var(--bg-blur2), transparent 32%),
        radial-gradient(circle at bottom right, var(--bg-blur1), transparent 36%);
    pointer-events: none;
    z-index: 0;
}

.feedback-forum-container > * {
    position: relative;
    z-index: 1;
}

.feedback-panel,
.feedback-item,
.feedback-empty-state {
    background: linear-gradient(180deg, color-mix(in srgb, var(--card) 92%, transparent), color-mix(in srgb, var(--text_background) 88%, transparent));
    border: 1px solid var(--border);
    border-radius: 1.25rem;
    box-shadow: var(--glow-secondary);
}


.feedback-content {
    display: grid;
    grid-template-columns: minmax(320px, 0.95fr) minmax(0, 1.45fr);
    gap: 1.5rem;
    align-items: start;
}

.feedback-panel {
    padding: 1.35rem;
}

.feedback-panel-header {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.feedback-panel-header h2 {
    font-size: 1.2rem;
}

.feedback-panel-header p,
.feedback-list-caption {
    color: var(--muted-foreground);
    font-size: 0.92rem;
}

.feedback-form {
    display: grid;
    gap: 1rem;
}

.feedback-form-grid {
    display: grid;
    gap: 1rem;
}

.form-group {
    display: grid;
    gap: 0.45rem;
}

.form-group label {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--foreground);
}

.form-group input,
.form-group textarea {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 0.95rem;
    background: color-mix(in srgb, var(--key) 72%, transparent);
    color: var(--foreground);
    padding: 0.9rem 1rem;
    outline: none;
    transition: border-color 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease;
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: var(--muted-foreground);
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: color-mix(in srgb, var(--primary) 70%, transparent);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary) 15%, transparent);
}

.form-group textarea {
    min-height: 180px;
    resize: vertical;
}

.feedback-form-note {
    color: var(--muted-foreground);
    font-size: 0.84rem;
}

.form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.btn-submit,
.btn-clear,
.feedback-action-btn {
    border: none;
    border-radius: 0.9rem;
    padding: 0.85rem 1.2rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.25s ease, box-shadow 0.25s ease, opacity 0.25s ease;
}

.btn-submit {
    background: var(--btn-gradient-primary);
    color: #fff;
    box-shadow: var(--glow-secondary);
}

.btn-clear {
    background: transparent;
    color: var(--foreground);
    border: 1px solid var(--border);
}

.btn-submit:hover,
.btn-clear:hover,
.feedback-action-btn:hover {
    transform: translateY(-1px);
}

.feedback-list-panel {
    display: grid;
    gap: 1rem;
}

.feedback-list {
    display: grid;
    gap: 1rem;
}

.feedback-item {
    padding: 1.2rem;
    display: grid;
    gap: 0.9rem;
}

.feedback-item-header {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
}

.feedback-item-title {
    font-size: 1.08rem;
    line-height: 1.35;
}

.feedback-item-actions {
    display: flex;
    gap: 0.55rem;
}

.feedback-action-btn {
    min-width: 72px;
    background: color-mix(in srgb, var(--key) 75%, transparent);
    color: var(--foreground);
    border: 1px solid var(--border);
}

.feedback-action-btn.delete {
    color: #ff8c8c;
    border-color: color-mix(in srgb, #ff8c8c 40%, var(--border));
}

.feedback-item-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    align-items: center;
    color: var(--muted-foreground);
    font-size: 0.88rem;
}

.feedback-item-content {
    color: var(--foreground);
    white-space: pre-wrap;
}

.feedback-item-footer {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: center;
    padding-top: 0.4rem;
    border-top: 1px solid color-mix(in srgb, var(--border) 72%, transparent);
}

.feedback-comments-title {
    color: var(--muted-foreground);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.comment-actions {
    display: flex;
    gap: 0.55rem;
}

.btn-comment,
.btn-comment-cancel {
    border: 1px solid var(--border);
    background: transparent;
    color: var(--foreground);
    border-radius: 999px;
    padding: 0.45rem 0.8rem;
    cursor: pointer;
}

.feedback-empty-state {
    padding: 2rem;
    text-align: center;
}

.feedback-empty-state h3 {
    font-size: 1.1rem;
    margin-bottom: 0.45rem;
}

.feedback-empty-state p {
    color: var(--muted-foreground);
    max-width: 42ch;
    margin: 0 auto;
}

@media (max-width: 1080px) {
    .feedback-content {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .feedback-forum-container {
        padding: 1rem;
    }

    .feedback-panel,
    .feedback-item {
        padding: 1rem;
    }

    .feedback-item-header,
    .feedback-item-footer,
    .feedback-panel-header {
        flex-direction: column;
    }

    .feedback-item-actions,
    .comment-actions,
    .form-actions {
        width: 100%;
    }

    .feedback-action-btn,
    .btn-submit,
    .btn-clear,
    .btn-comment,
    .btn-comment-cancel {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="feedback-forum-container">
    <div class="feedback-content">
        <section class="feedback-panel feedback-form-section">
            <div class="feedback-panel-header">
                <div>
                    <h2>Send your feedback</h2>
                </div>
            </div>

            <form id="feedbackForm" class="feedback-form">
                <div class="feedback-form-grid">
                    <div class="form-group">
                        <label for="yourName">Your Name</label>
                        <input type="text" id="yourName" name="name" placeholder="Enter your name" required>
                    </div>

                    <div class="form-group">
                        <label for="postTitle">Post Title</label>
                        <input type="text" id="postTitle" name="title" placeholder="Summarize the feedback clearly" required>
                    </div>

                    <div class="form-group">
                        <label for="feedbackMessage">Feedback Message</label>
                        <textarea id="feedbackMessage" name="message" placeholder="Write the issue, idea, or improvement suggestion" required></textarea>
                    </div>
                </div>


                <div class="form-actions">
                    <button type="submit" class="btn-submit">Submit Post</button>
                    <button type="reset" class="btn-clear">Clear</button>
                </div>
            </form>
        </section>

        <section class="feedback-panel feedback-list-panel">
            <div class="feedback-panel-header">
                <div>
                    <h2>Feedback Posts</h2>
                </div>
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
document.addEventListener('DOMContentLoaded', function() {
    const feedbackForm = document.getElementById('feedbackForm');
    const feedbackList = document.getElementById('feedbackList');
    const apiBaseUrl = '/UniHelper/api';

    function buildFeedbackApiUrl(action, params = {}) {
        const query = new URLSearchParams({
            controller: 'feedbackController',
            action,
            ...params
        });

        return `${apiBaseUrl}?${query.toString()}`;
    }
    
    // Load existing feedback posts when page loads
    loadFeedback();

    function renderEmptyState(title, message) {
        feedbackList.innerHTML = `
            <div class="feedback-empty-state">
                <h3>${title}</h3>
                <p>${message}</p>
            </div>
        `;
    }

    /**
     * Client-side validation for feedback form
     * Checks for empty fields and basic format validation
     */
    function validateFeedbackForm(formData) {
        const errors = [];
        
        // Check if name is empty or too short
        if (!formData.name || formData.name.trim().length === 0) {
            errors.push('Name is required');
        } else if (formData.name.trim().length < 2) {
            errors.push('Name must be at least 2 characters');
        }
        
        // Check if title is empty or too short
        if (!formData.title || formData.title.trim().length === 0) {
            errors.push('Post title is required');
        } else if (formData.title.trim().length < 5) {
            errors.push('Post title must be at least 5 characters');
        }
        
        // Check if message is empty or too short
        if (!formData.message || formData.message.trim().length === 0) {
            errors.push('Feedback message is required');
        } else if (formData.message.trim().length < 10) {
            errors.push('Feedback message must be at least 10 characters');
        }
        
        return errors;
    }
    
    /**
     * Sanitize user input to prevent basic XSS
     * Trims whitespace and encodes special HTML characters
     */
    function sanitizeInput(input) {
        // Trim whitespace
        let sanitized = input.trim();
        // Create a temporary div element to escape HTML
        const div = document.createElement('div');
        div.textContent = sanitized;
        return div.innerHTML;
    }
    
    /**
     * Submit feedback via fetch() without page reload
     * Sends data to /feedback/submit endpoint
     */
    function submitFeedback(feedbackData) {
        // Show loading state
        const submitBtn = feedbackForm.querySelector('.btn-submit');
        const originalBtnText = submitBtn.textContent;
        submitBtn.textContent = 'Submitting...';
        submitBtn.disabled = true;
        
        // Prepare payload - pass data exactly as entered (no modification)
        const payload = {
            name: feedbackData.name,
            title: feedbackData.title,
            message: feedbackData.message
        };
        
        // Send POST request via fetch()
        // Use full path with /UniHelper/ to match the application's deployment structure
        fetch(buildFeedbackApiUrl('submitFeedback'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
        .then(response => {
            // Check if response is JSON
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Handle API response
            submitBtn.textContent = originalBtnText;
            submitBtn.disabled = false;
            
            if (data.success) {
                // Success: Add feedback to the list and reset form
                addFeedbackToList({
                    id: data.data.id,
                    name: data.data.name,
                    title: data.data.title,
                    message: data.data.message,
                    created_at: data.data.created_at
                });
                feedbackForm.reset();
                showNotification('Feedback submitted successfully!', 'success');
            } else {
                // Error response from server
                showNotification(data.message || 'Failed to submit feedback', 'error');
            }
        })
        .catch(error => {
            // Network or parsing error
            submitBtn.textContent = originalBtnText;
            submitBtn.disabled = false;
            console.error('Error submitting feedback:', error);
            showNotification('An error occurred while submitting feedback. Please try again.', 'error');
        });
    }
    
    /**
     * Display notification message to user
     */
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 4px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideIn 0.3s ease;
            ${type === 'success' ? 'background-color: #10b981;' : 'background-color: #ef4444;'}
        `;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        // Remove notification after 5 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
    
    // Add CSS animations for notifications
    if (!document.getElementById('feedbackNotificationStyles')) {
        const style = document.createElement('style');
        style.id = 'feedbackNotificationStyles';
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Form submission handler
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Collect form data
            const formData = {
                name: document.getElementById('yourName').value,
                title: document.getElementById('postTitle').value,
                message: document.getElementById('feedbackMessage').value
            };
            
            // Validate form data
            const validationErrors = validateFeedbackForm(formData);
            
            if (validationErrors.length > 0) {
                // Display validation errors
                showNotification(validationErrors.join(', '), 'error');
                return;
            }
            
            // Check if we're editing or creating
            const editId = feedbackForm.getAttribute('data-edit-id');
            
            if (editId) {
                // Update existing feedback
                updateFeedbackItem(editId, formData);
            } else {
                // Submit new feedback
                submitFeedback(formData);
            }
        });
    }

    /**
     * Update an existing feedback item
     */
    function updateFeedbackItem(feedbackId, feedbackData) {
        const submitBtn = feedbackForm.querySelector('.btn-submit');
        const originalBtnText = submitBtn.textContent;
        submitBtn.textContent = 'Updating...';
        submitBtn.disabled = true;
        
        const payload = {
            id: feedbackId,
            name: feedbackData.name,
            title: feedbackData.title,
            message: feedbackData.message
        };
        
        fetch(buildFeedbackApiUrl('updateFeedback'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            submitBtn.textContent = originalBtnText;
            submitBtn.disabled = false;
            
            if (data.success) {
                feedbackForm.reset();
                feedbackForm.removeAttribute('data-edit-id');
                submitBtn.textContent = 'Submit Post';
                showNotification('Feedback updated successfully!', 'success');
                
                // Reload feedback list
                loadFeedback();
            } else {
                showNotification(data.message || 'Failed to update feedback', 'error');
            }
        })
        .catch(error => {
            submitBtn.textContent = originalBtnText;
            submitBtn.disabled = false;
            console.error('Error updating feedback:', error);
            showNotification('An error occurred while updating feedback. ' + (error.message || ''), 'error');
        });
    }

    /**
     * Add submitted feedback to the list display
     * Takes sanitized feedback data and inserts it at the top
     */
    function addFeedbackToList(feedback) {
        const rawDate = feedback.created_at ? new Date(feedback.created_at) : new Date();
        const dateStr = Number.isNaN(rawDate.getTime())
            ? new Date().toISOString().split('T')[0]
            : rawDate.toISOString().split('T')[0];
        
        const newItem = document.createElement('div');
        newItem.className = 'feedback-item';
        newItem.setAttribute('data-feedback-id', feedback.id || '');
        
        newItem.innerHTML = `
            <div class="feedback-item-header">
                <h3 class="feedback-item-title"></h3>
                <div class="feedback-item-actions">
                    <button class="feedback-action-btn btn-edit-feedback" type="button" title="Edit">Edit</button>
                    <button class="feedback-action-btn delete btn-delete-feedback" type="button" title="Delete">Delete</button>
                </div>
            </div>
            <div class="feedback-item-meta">
                <span>By <span class="feedback-item-author"></span></span>
                <span>•</span>
                <span>${dateStr}</span>
            </div>
            <p class="feedback-item-content"></p>
            
            <div class="feedback-item-footer">
                <div class="feedback-comments-title">Comments</div>
                <div class="comment-actions">
                    <button class="btn-comment" type="button">Post Comment</button>
                    <button class="btn-comment-cancel" type="button">Cancel</button>
                </div>
            </div>
        `;
        
        // Set text content to prevent XSS
        newItem.querySelector('.feedback-item-title').textContent = feedback.title;
        newItem.querySelector('.feedback-item-author').textContent = feedback.name;
        newItem.querySelector('.feedback-item-content').textContent = feedback.message;
        
        // Add edit button handler
        const editBtn = newItem.querySelector('.btn-edit-feedback');
        editBtn.addEventListener('click', function() {
            editFeedback(feedback);
        });
        
        // Add delete button handler
        const deleteBtn = newItem.querySelector('.btn-delete-feedback');
        deleteBtn.addEventListener('click', function() {
            deleteFeedback(feedback.id || '', newItem);
        });
        
        feedbackList.insertBefore(newItem, feedbackList.firstChild);
    }

    /**
     * Load all feedback posts from the server
     */
    function loadFeedback() {
        fetch(buildFeedbackApiUrl('getFeedback'), {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load feedback: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && Array.isArray(data.data)) {
                feedbackList.innerHTML = ''; // Clear existing content
                
                // Sort by created_at descending (newest first)
                data.data.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

                if (data.data.length === 0) {
                    renderEmptyState('No feedback yet', 'Be the first person to share an idea, issue, or improvement.');
                    return;
                }
                
                // Add each feedback item
                data.data.forEach(feedback => {
                    addFeedbackToList(feedback);
                });
                return;
            }

            throw new Error(data.message || 'Failed to load feedback');
        })
        .catch(error => {
            console.error('Error loading feedback:', error);
            renderEmptyState('Failed to load feedback', 'Please refresh the page and try again.');
            showNotification('Failed to load feedback. Please refresh the page.', 'error');
        });
    }

    /**
     * Edit feedback - populate form with feedback data
     */
    function editFeedback(feedback) {
        document.getElementById('yourName').value = feedback.name;
        document.getElementById('postTitle').value = feedback.title;
        document.getElementById('feedbackMessage').value = feedback.message;
        
        // Store the ID in the form for update
        feedbackForm.setAttribute('data-edit-id', feedback.id);
        
        // Change submit button text
        const submitBtn = feedbackForm.querySelector('.btn-submit');
        submitBtn.textContent = 'Update Feedback';
        setActiveFilterButton(addNewPostBtn);
        
        // Scroll to form
        feedbackForm.scrollIntoView({ behavior: 'smooth' });
        document.getElementById('yourName').focus();
        
        showNotification('Editing feedback - update and submit to save changes', 'info');
    }

    /**
     * Delete feedback post
     */
    function deleteFeedback(feedbackId, feedbackElement) {
        if (!confirm('Are you sure you want to delete this feedback?')) {
            return;
        }
        
        fetch(buildFeedbackApiUrl('deleteFeedback'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ id: feedbackId })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                feedbackElement.remove();
                showNotification('Feedback deleted successfully!', 'success');
            } else {
                showNotification(data.message || 'Failed to delete feedback', 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting feedback:', error);
            showNotification('An error occurred while deleting feedback. ' + (error.message || ''), 'error');
        });
    }

});
</script>
