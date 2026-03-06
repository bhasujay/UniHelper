// Declare selectedFiles globally at the top of the file
var selectedFiles = [];
var questionCardTemplate; // Declare globally
var current_question_pointer = 0;
var current_question_pointer_temp = 0;
var currentFilter = 'default'; // default filter
var currentTag = 'default'; // default tag
var batch_limit = 10;
var isFetching = false;
var hasMoreQuestions = true;
var questionModel = true;
var userID = document.getElementById('profileUserId').textContent;
var isModerator = document.getElementById('profileModStatus').textContent === '1';
var menuDropdown = document.querySelector('.qa-menu-dropdown');

////////////////////////////////////////////////////////////////////////////


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

/////////////////////////////////////////////////////////////////////////////

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
    askmodal.querySelector('.qa-askmodal-close').addEventListener('click', async function() {
        const questionTitle = document.getElementById('qa-question-title').textContent.trim();
        const questionBody = document.getElementById('qa-question-body').value.trim();
        
        if (questionTitle || questionBody || selectedFiles.length > 0) {
            if (!await confirm('You have unsaved changes. Are you sure you want to close?')) {
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
    cancelBtn.addEventListener('click', async function() {
        const questionTitle = document.getElementById('qa-question-title').textContent.trim();
        const questionBody = document.getElementById('qa-question-body').value.trim();
        
        if (questionTitle || questionBody || selectedFiles.length > 0) {
            if (!await confirm('You have unsaved changes. Are you sure you want to cancel?')) {
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

/////////////////////////////////////////////////////////////////////////////
    
    // Answer form submission
    const answerForm = document.getElementById('qaAnswerForm');
    answerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const answerText = answerForm.querySelector('textarea').value.trim();
        const questionId = answerForm.dataset.questionId;
        
        if (!answerText) {
            showToast('Please enter your answer', 'error');
            return;
        }
        
        if (answerText.length < 1) {
            showToast('Answer must be at least 1 character long', 'error');
            return;
        }
        
        // Prepare form data
        const formData = new FormData();
        formData.append('question_id', questionId);
        formData.append('text', answerText);
        
        // Show loading state
        const submitBtn = answerForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
        
        // AJAX submission
        fetch('http://localhost/unihelper/api?controller=qaController&action=answerQuestion', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Answer posted successfully!', 'success');
                
                // Update answer count in the question card
                const questionCard = document.getElementById(questionId);
                const answerCountEl = questionCard.querySelector('.qa-answer-count');
                const currentCount = parseInt(answerCountEl.textContent.match(/\d+/)[0]);
                answerCountEl.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>${currentCount + 1}`;

                // if the view modal is open for this question, append the new answer there too
                const qaViewModal = document.querySelector('.qa-question-view');
                if (qaViewModal.style.display === 'flex') {

                    const anscount = qaViewModal.querySelector('.qa-answer-count');
                    const currentAnsCount = parseInt(anscount.textContent.match(/\d+/)[0]);
                    if (currentAnsCount + 1 == 1) {
                        anscount.innerHTML = `1 Answer`;
                    } else {
                        anscount.innerHTML = `${currentAnsCount + 1} Answers`;
                    }
                    
                    let answerTemplate = qaViewModal.querySelector('.qa-answer-card');
                    const card = answerTemplate.cloneNode(true);
                    let user_avatar = document.getElementsByClassName('profile-img')[0].src;
                    let defaultAvatar = document.getElementById('default-pfp').src;

                    if (user_avatar.includes('/uploads')) {
                        card.querySelector('.qa-avatar-img').src = user_avatar;
                    } else {
                        card.querySelector('.qa-avatar-img').src = defaultAvatar;
                    }
                    let username = document.getElementById('profileName').textContent;
                    if (username.length >12) {
                        username = username.split(' ')[0];
                    }
                    let role = document.getElementById('profileRole').textContent;
                    card.querySelector('.qa-username').textContent = username;
                    card.querySelector('.qa-role').textContent = role;

                    card.querySelector('.qa-time').textContent = 'Just now';
                    card.querySelector('.qa-answer-body').textContent = answerText;

                    // Create and append the menu container
                    card.querySelector('.qa-menu-btn').style.display = 'block';
                    generateMenuDropdown(card, userID, userID, isModerator, true);                    

                    qaViewModal.querySelector('.qa-view-answers').appendChild(card);
                    card.style.display = 'flex';
                }
                
                // Close modal and reset form
                answermodal.style.display = 'none';
                questionModel = false;
                resetAnswerForm();
            } else {
                showToast('Error: ' + (data.message || 'Failed to post answer'), 'error');
            }
        })
        .catch(error => {
            console.error('Error submitting answer:', error);
            showToast('An error occurred while submitting your answer. Please try again.', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalBtnText;
        });
    });
    
/////////////////////////////////////////////////////////////////////////////
    
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
    
/////////////////////////////////////////////////////////////////////////////
    
    // Hashtag parsing in question title
    document.getElementById('qa-question-title').addEventListener('input', function() {
        parseHashtags();
    });

    // Ask Form submission
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
                ? `public/uploads/qnaImages/${data.data.question_id}/0.${selectedFiles[0].name.split('.').pop()}`
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
                    firstImage: firstImagePath,  // Use server path format instead of blob URL
                    modified: false
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

/////////////////////////////////////////////////////////////////////////////
    
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
            if (tag.post_count > 0) {
                tagBtn.textContent = tag.tag_name + ` (${tag.post_count})`;
            } else {
                tagBtn.textContent = tag.tag_name;
            }
            // Make buttons behave like push/toggle buttons and call toggle handlers
            tagBtn.setAttribute('type', 'button');
            tagBtn.setAttribute('aria-pressed', 'false');

            tagBtn.addEventListener('click', function() {
                // Enforce single-active: deactivate all buttons first
                const buttons = tagsBar.querySelectorAll('.tag-btn');
                const wasActive = this.classList.contains('active');

                buttons.forEach(btn => {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-pressed', 'false');
                });

                if (!wasActive) {
                    // Activate this one and call handler
                    this.classList.add('active');
                    this.setAttribute('aria-pressed', 'true');
                    tagOnClick(tag.tag_name);
                } else {
                    // It was active and is now deactivated
                    tagOffClick();
                }
            });

            // Allow keyboard activation (Enter / Space)
            tagBtn.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });

            tagsBar.appendChild(tagBtn);
        });
    })
    .catch(error => console.error('Error fetching tags:', error));
    
    // Initial fetch of questions
    fetchQuestions();
    
    // Set up scroll listener for lazy loading
    window.addEventListener('scroll', handleScroll);

/////////////////////////////////////////////////////////////////////////////
// configure vote buttons in question view modal

    const modal = document.querySelector('.qa-question-view');
    const questionId = modal.querySelector('#qaViewModalQuestionId');

    // Upvote and Downvote buttons
    modal.querySelector('.upvote').addEventListener('click', function() {
        submitVote(modal, questionId.textContent, this);
    });
    modal.querySelector('.downvote').addEventListener('click', function() {
        submitVote(modal, questionId.textContent, this);
    });

    // Answer button
    modal.querySelector('.answer-btn').addEventListener('click', function() {
        answer(questionId.textContent);
    });

    // Nav-left (back) button
    modal.querySelector('.qa-nav-left-btn').addEventListener('click', function() {
        goBackFromQuestionView(questionId.textContent);
    });
/////////////////////////////////////////////////////////////////////////////

});

