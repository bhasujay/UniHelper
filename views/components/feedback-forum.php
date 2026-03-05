<?php
// Feedback Forum Component
?>

<link rel="stylesheet" href="/UniHelper/views/css/feedback.css">

<div class="feedback-forum-container">
    <div class="feedback-header">
        <h1>Ready to send your feedback?</h1>
    </div>

    <div class="feedback-controls">
        <button class="btn btn-primary" id="addNewPostBtn">
            <span></span> Add New Post
        </button>
        <button class="btn btn-secondary" id="recentlyAddedBtn">
            <span></span> Recently Added
        </button>
        <button class="btn btn-secondary" id="popularFeedbackBtn">
            <span></span> Popular Feedback
        </button>
    </div>

    <div class="feedback-content">
        <!-- Feedback submission form -->
        <div class="feedback-form-section">
            <h2>Add Feedback Post</h2>
            <form id="feedbackForm" class="feedback-form">
                <div class="form-group">
                    <label for="yourName">Your Name</label>
                    <input type="text" id="yourName" name="name" placeholder="Enter your name" required>
                </div>

                <div class="form-group">
                    <label for="postTitle">Post Title</label>
                    <input type="text" id="postTitle" name="title" placeholder="Enter feedback title" required>
                </div>

                <div class="form-group">
                    <label for="feedbackMessage">Feedback Message</label>
                    <textarea id="feedbackMessage" name="message" placeholder="Write your feedback..." required></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Submit Post</button>
                    <button type="reset" class="btn-clear">Clear</button>
                </div>
            </form>
        </div>

        <!-- Feedback list -->
        <div class="feedback-list-section">
            <h2>Feedback Posts</h2>
            <div id="feedbackList" class="feedback-list">
                <!-- Feedback posts will be loaded dynamically here -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const feedbackForm = document.getElementById('feedbackForm');
    const addNewPostBtn = document.getElementById('addNewPostBtn');
    const recentlyAddedBtn = document.getElementById('recentlyAddedBtn');
    const popularFeedbackBtn = document.getElementById('popularFeedbackBtn');
    
    // Load existing feedback posts when page loads
    loadFeedback();
    
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
        fetch('/UniHelper/feedback/submit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
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
                addFeedbackToList(feedbackData);
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
        
        fetch('/UniHelper/feedback/update', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
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
            showNotification('An error occurred while updating feedback. Please try again.', 'error');
        });
    }

    // Control button handlers
    addNewPostBtn.addEventListener('click', function() {
        const form = document.getElementById('feedbackForm');
        form.scrollIntoView({ behavior: 'smooth' });
        document.getElementById('yourName').focus();
    });

    recentlyAddedBtn.addEventListener('click', function() {
        console.log('Recently Added clicked');
    });

    popularFeedbackBtn.addEventListener('click', function() {
        console.log('Popular Feedback clicked');
    });

    /**
     * Add submitted feedback to the list display
     * Takes sanitized feedback data and inserts it at the top
     */
    function addFeedbackToList(feedback) {
        const feedbackList = document.getElementById('feedbackList');
        
        const dateStr = new Date().toISOString().split('T')[0];
        
        const newItem = document.createElement('div');
        newItem.className = 'feedback-item';
        newItem.setAttribute('data-feedback-id', feedback.id || '');
        
        newItem.innerHTML = `
            <div class="feedback-item-header">
                <h3 class="feedback-item-title"></h3>
                <div class="feedback-item-actions">
                    <button class="btn-edit-feedback" title="Edit">✏️</button>
                    <button class="btn-delete-feedback" title="Delete">🗑️</button>
                </div>
            </div>
            <div class="feedback-item-meta">
                <span>By <span class="feedback-item-author"></span></span>
                <span>•</span>
                <span>${dateStr}</span>
            </div>
            <p class="feedback-item-content"></p>
            
            <div class="feedback-comments">
                <div class="feedback-comments-title">Comments</div>
                <div class="comment-actions">
                    <button class="btn-comment">Post Comment</button>
                    <button class="btn-comment-cancel">Cancel</button>
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
        fetch('/UniHelper/feedback/get', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load feedback');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && Array.isArray(data.data)) {
                const feedbackList = document.getElementById('feedbackList');
                feedbackList.innerHTML = ''; // Clear existing content
                
                // Sort by created_at descending (newest first)
                data.data.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                
                // Add each feedback item
                data.data.forEach(feedback => {
                    addFeedbackToList(feedback);
                });
            }
        })
        .catch(error => {
            console.error('Error loading feedback:', error);
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
        
        fetch('/UniHelper/feedback/delete', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
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
            showNotification('An error occurred while deleting feedback. Please try again.', 'error');
        });
    }

});
</script>
