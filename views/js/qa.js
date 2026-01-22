// Declare selectedFiles globally at the top of the file
var selectedFiles = [];
var questionCardTemplate; // Declare globally
var current_question_pointer = 0;
var batch_limit = 10;
var isFetching = false;
var hasMoreQuestions = true;

//////////////////////////////////////////////////////////////////////


document.addEventListener('DOMContentLoaded', function() {
    // Create button element (label included but hidden via CSS)
    const btn = document.createElement('button');
    btn.className = 'qa-sticky-btn';
    btn.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span class="qa-sticky-label">Ask a Question</span>';
    document.body.appendChild(btn);

    // Locate existing askmodal and move it to document.body
    let askmodal = document.querySelector('.qa-askmodal');
    askmodal.parentElement.removeChild(askmodal);
    document.body.appendChild(askmodal);

    // Locate existing answermodal and move it to document.body
    let answermodal = document.querySelector('.qa-answermodal');
    answermodal.parentElement.removeChild(answermodal);
    document.body.appendChild(answermodal);

    // the question card template
    questionCardTemplate = document.getElementById('qa-question-card-template');
    
    let isExpanded = false;
    // hover: toggle a class — label reveal is handled by CSS transitions
    btn.addEventListener('mouseenter', function() {
        if (!isExpanded) btn.classList.add('hover');
    });
    btn.addEventListener('mouseleave', function() {
        if (!isExpanded) btn.classList.remove('hover');
    });
    
    // click: toggle expanded state and askmodal visibility
    btn.addEventListener('click', function() {
        isExpanded = !isExpanded;
        if (isExpanded) {
            btn.classList.add('expanded');
            askmodal.style.display = 'flex';
        } else {
            btn.classList.remove('expanded');
            askmodal.style.display = 'none';
        }
    });
    
    // close askmodal button — also collapse the toggle
    askmodal.querySelector('.qa-askmodal-close').addEventListener('click', function() {
        const questionTitle = document.getElementById('qa-question-title').textContent.trim();
        const questionBody = document.getElementById('qa-question-body').value.trim();
        
        if (questionTitle || questionBody || selectedFiles.length > 0) {
            if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
                return;
            }
        }
        
        askmodal.style.display = 'none';
        isExpanded = false;
        btn.classList.remove('expanded');
        btn.classList.remove('hover');
        resetForm();
    });
    
    
    // Close answermodal
    answermodal.querySelector('.qa-answermodal-close').addEventListener('click', function() {
        answermodal.style.display = 'none';
        resetAnswerForm();
    });
    
    // Cancel button
    const cancelBtn = document.querySelector('.qa-cancel-btn');
    cancelBtn.addEventListener('click', function() {
        const questionTitle = document.getElementById('qa-question-title').textContent.trim();
        const questionBody = document.getElementById('qa-question-body').value.trim();
        
        if (questionTitle || questionBody || selectedFiles.length > 0) {
            if (!confirm('You have unsaved changes. Are you sure you want to cancel?')) {
                return;
            }
        }
        
        askmodal.style.display = 'none';
        isExpanded = false;
        btn.classList.remove('expanded');
        btn.classList.remove('hover');
        resetForm();
    });
    
    // Cancel button for answer modal
    const answerCancelBtn = answermodal.querySelector('.qa-cancel-btn');
    answerCancelBtn.addEventListener('click', function() {
        answermodal.style.display = 'none';
        resetAnswerForm();
    });
    
    // Open answermodal
    const answerButtons = document.querySelectorAll('.answer-btn');
    answerButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const questionCard = btn.closest('.qa-question-card');
            const username = questionCard.querySelector('.qa-username').textContent;
            const label = answermodal.querySelector('.qa-form-label');
            label.innerHTML = `Your Answer to <span class="answer-to-username">${username}</span>`;
            answermodal.style.display = 'flex';
        });
    });
    
    // Image upload handling in askmodal
    const imageInput = document.getElementById('qa-image-input');
    const imageTray = document.querySelector('.qa-image-tray');
    const imageAddBox = document.querySelector('.qa-image-add-box');
    
    imageInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        files.forEach(file => {
            if (file.type.startsWith('image/')) {
                // Check if limit is reached
                if (selectedFiles.length >= 10) {
                    showToast('You can only upload up to 10 images', 'error');
                    return;
                }
                selectedFiles.push(file);
                addImagePreview(file, imageTray, imageAddBox); // Pass imageTray and imageAddBox
            }
        });
        // Reset input so same file can be selected again if removed and re-added
        e.target.value = '';
    });

    // Hashtag parsing in question title
    document.getElementById('qa-question-title').addEventListener('input', function() {
        parseHashtags();
    });
    
    // Form submission
    const questionForm = document.getElementById('qaQuestionForm');
    questionForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validation
        const questionTitle = document.getElementById('qa-question-title').textContent.trim();
        const questionBody = document.getElementById('qa-question-body').value.trim();
        
        if (!questionTitle) {
            showToast('Please enter a question title', 'error');
            return;
        }
        
        if (questionTitle.length < 10) {
            showToast('Question title must be at least 10 characters long', 'error');
            return;
        }
        
        if (questionTitle.length > 512) {
            showToast('Question title must not exceed 512 characters', 'error');
            return;
        }
        
        if (!questionBody) {
            showToast('Please provide a detailed description', 'error');
            return;
        }
        
        if (questionBody.length < 10) {
            showToast('Description must be at least 10 characters long', 'error');
            return;
        }
        
        // Extract tags from title at submission time
        const tagMatches = questionTitle.match(/#(\w+)/g);
        const extractedTags = tagMatches ? tagMatches.map(tag => tag.slice(1)) : [];
        
        // Prepare form data
        const formData = new FormData();
        formData.append('question', questionTitle);
        formData.append('text', questionBody);
        formData.append('tags', JSON.stringify(extractedTags)); // Extract tags here
        
        // Add all selected images
        selectedFiles.forEach((file, index) => {
            formData.append('images[]', file);
        });
        
        // Show loading state
        const submitBtn = questionForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
        
        // AJAX submission
        fetch('http://localhost/unihelper/api?controller=qaController&action=create', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Question posted successfully!', 'success');
                
                // Make the data object for question card
                const fullAvatarSrc = document.getElementsByClassName('profile-img')[0].src;
                const baseUrl = 'http://localhost/unihelper/public/';
                let user_avatar;

                if (fullAvatarSrc.includes('/views/assets/')) {
                    user_avatar = null; // Use default avatar
                } else {
                    user_avatar = fullAvatarSrc.replace(baseUrl, '');
                }
                
                // Build the expected image path based on what the server would save
                const firstImagePath = selectedFiles.length > 0 
                    ? `uploads/qnaImages/${data.data.question_id}/0.${selectedFiles[0].name.split('.').pop()}`
                    : '';
                
                const added_data = {
                    userID: document.getElementById('profileUserId').textContent,
                    username: document.getElementById('profileName').textContent,
                    user_role: document.getElementById('profileRole').textContent,
                    moderator_status: document.getElementById('profileModStatus').textContent,
                    user_avatar: user_avatar,
                    
                    questionId: data.data.question_id,
                    voteCount: 0,
                    voteStatus: 0,
                    answerCount: 0,
                    questionTitle: questionTitle,
                    questionText: questionBody,
                    timestamp: 'Just now',
                    imagecount: selectedFiles.length,
                    firstImage: firstImagePath  // Use server path format instead of blob URL
                };

                makeQuestionCard(added_data, 0); // Prepend new question card
                
                // Close askmodal and reset
                askmodal.style.display = 'none';
                isExpanded = false;
                btn.classList.remove('expanded');
                btn.classList.remove('hover');
                resetForm();
                
            } else {
                showToast('Error: ' + (data.message || 'Failed to post question'), 'error');
            }
        })
        .catch(error => {
            console.error('Error submitting question:', error);
            console.log(data.message);
            showToast('An error occurred while submitting your question. Please try again.', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
        });
    });

    // Clean up
    window.addEventListener('beforeunload', function() {
        if (btn.parentElement) btn.remove();
        if (askmodal.parentElement) askmodal.remove();
        if (answermodal.parentElement) answermodal.remove();
    });

    // Fetch and populate tags
    fetch('/unihelper/api?controller=qaController&action=getTopTags')
    .then(response => response.json())
    .then(data => {
        const tags = data.data;
        const tagsBar = document.querySelector('.qa-tags-bar');
        tagsBar.innerHTML = ''; // Clear skeleton
        tags.forEach(tag => {
            const tagBtn = document.createElement('button');
            tagBtn.className = 'tag-btn';
            tagBtn.textContent = tag.tag_name;
            tagsBar.appendChild(tagBtn);
        });
    })
    .catch(error => console.error('Error fetching tags:', error));

    // Initial fetch of questions
    fetchQuestions();

    // Set up scroll listener for lazy loading
    window.addEventListener('scroll', handleScroll);
});

