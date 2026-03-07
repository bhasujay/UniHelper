var questions_ids = [];
var questions_ids_temp = [];
var hasMoreQuestions_temp = true;

//////////////////////////////////////////////////////////////////////

function generateMenuDropdown(element, questionUserId, currentUserId, isModerator, questionId, isAnswer = -1) {
    let menuContainer = element.querySelector('.qa-menu-dropdown[data-generated="true"]');

    if (!menuContainer) {
        const template = document.querySelector('.qa-menu-dropdown:not([data-generated="true"])');
        if (!template) {
            return;
        }

        menuContainer = template.cloneNode(true);
        menuContainer.dataset.generated = 'true';
        menuContainer.style.display = 'none';
        element.appendChild(menuContainer);
    }

    const editBtn = menuContainer.querySelector('.edit-btn');
    const deleteBtn = menuContainer.querySelector('.delete-btn');
    const reportBtn = menuContainer.querySelector('.report-btn');
    const closeBtn = menuContainer.querySelector('.close-btn');
    const menuBtn = element.querySelector('.qa-menu-btn');

    // Reset visibility so reused dropdowns do not keep stale state.
    if (editBtn) editBtn.style.display = 'none';
    if (deleteBtn) deleteBtn.style.display = 'none';
    if (reportBtn) reportBtn.style.display = 'none';

    if (questionUserId == currentUserId) {
        if (editBtn) {
            editBtn.style.display = isAnswer >= 0 ? 'none' : 'block';
        }
        if (deleteBtn) {
            deleteBtn.style.display = 'block';
        }
    }

    if (isModerator && reportBtn) {
        reportBtn.style.display = 'block';
    }

    if (!menuContainer.dataset.initialized) {
        menuContainer.dataset.initialized = 'true';

        menuContainer.addEventListener('click', function(e) {
            e.stopPropagation();
            const clickedItem = e.target.closest('.qa-menu-item');
            if (clickedItem && !clickedItem.classList.contains('delete-btn')) {
                menuContainer.style.display = 'none';
            }
        });

        if (closeBtn) {
            closeBtn.onclick = function(e) {
                e.stopPropagation();
                menuContainer.style.display = 'none';
            };
        }
    }

    if (menuBtn) {
        menuBtn.onclick = function(e) {
            e.stopPropagation();
            const shouldOpen = menuContainer.style.display !== 'block';

            document.querySelectorAll('.qa-menu-dropdown[data-generated="true"]').forEach(function(menu) {
                menu.style.display = 'none';
            });

            menuContainer.style.display = shouldOpen ? 'block' : 'none';
        };
    }

    if (deleteBtn) {
        deleteBtn.onclick = async function(e) {
            e.stopPropagation();
            try {
                if (isAnswer >= 0) {
                    await deleteAnswer(isAnswer);
                } else {
                    await deleteQuestion(questionId);
                }
            } finally {
                // Hide regardless of success/failure/cancel to avoid stale open menus.
                menuContainer.style.display = 'none';
            }
        };
    }

    if (!document.body.dataset.qaMenuOutsideBound) {
        document.body.dataset.qaMenuOutsideBound = 'true';
        document.addEventListener('click', function() {
            document.querySelectorAll('.qa-menu-dropdown[data-generated="true"]').forEach(function(menu) {
                menu.style.display = 'none';
            });
        });
    }
}

function resetAnswerForm() {
    document.getElementById('qaAnswerForm').reset();
    const label = document.querySelector('.qa-answermodal .qa-form-label');
    label.innerHTML = 'Your Answer';
    // Clear question ID
    document.getElementById('qaAnswerForm').dataset.questionId = '';
}

function resetForm() {
    const titleDiv = document.getElementById('qa-question-title');
    titleDiv.innerHTML = '';
    document.getElementById('qa-question-body').value = '';
    selectedFiles = [];
    // Remove all image previews
    document.querySelectorAll('.qa-image-preview').forEach(preview => preview.remove());
}

function resetQuestionView() {
    const qaViewModal = document.querySelector('.qa-question-view');
    
    // Reset user info
    qaViewModal.querySelector('.qa-avatar-img').src = document.getElementById('default-pfp').src;
    qaViewModal.querySelector('.qa-username').textContent = '';
    qaViewModal.querySelector('.qa-role').textContent = '';
    qaViewModal.querySelector('.qa-time').textContent = '';
    qaViewModal.querySelector('.qa-modified').textContent = '';
    
    // Reset question content
    qaViewModal.querySelector('.qa-view-title').innerHTML = '';
    qaViewModal.querySelector('.qa-view-body').textContent = '';
    
    // Reset image gallery
    qaViewModal.querySelector('.qa-view-images').style.display = 'none';
    const imgContainer = qaViewModal.querySelector('.qa-img-container');
    if (imgContainer) {
        imgContainer.querySelector('img').src = '';
    }
    
    // Reset vote functionalities
    qaViewModal.querySelector('.vote-count').textContent = '0';
    qaViewModal.querySelector('.upvote').classList.remove('active');
    qaViewModal.querySelector('.downvote').classList.remove('active');
    
    // Reset answers section - but keep the template
    qaViewModal.querySelector('.qa-answer-count').textContent = '0 Answers';
    const answersContainer = qaViewModal.querySelector('.qa-view-answers');
    const answerCards = answersContainer.querySelectorAll('.qa-answer-card');
    answerCards.forEach((card, index) => {
        if (index > 0) {
            card.remove();
        } else {
            card.style.display = 'none';
        }
    });
}

function addImagePreview(file, imageTray, imageAddBox) { // Add parameters
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.createElement('div');
        preview.className = 'qa-image-preview';
        preview.dataset.fileName = file.name;
        
        const img = document.createElement('img');
        img.src = e.target.result;
        
        const removeBtn = document.createElement('button');
        removeBtn.className = 'qa-image-remove';
        removeBtn.innerHTML = '×';
        removeBtn.type = 'button';
        removeBtn.addEventListener('click', function() {
            removeImage(file.name, preview);
        });
        
        preview.appendChild(img);
        preview.appendChild(removeBtn);
        
        // Insert before the add box
        imageTray.insertBefore(preview, imageAddBox);
        
        // Scroll to show the new image
        imageTray.scrollLeft = imageTray.scrollWidth;
    };
    reader.readAsDataURL(file);
}

function removeImage(fileName, previewElement) {
    selectedFiles = selectedFiles.filter(f => f.name !== fileName);
    previewElement.remove();
}

function answer(questionId) {
    const answermodal = document.querySelector('.qa-answermodal');
    const questionCard = document.getElementById(questionId);
    const username = questionCard.querySelector('.qa-username').textContent;
    const label = answermodal.querySelector('.qa-form-label');
    label.innerHTML = `Your Answer to <span class="answer-to-username">${username}</span>`;
    
    // Store question ID in the form
    document.getElementById('qaAnswerForm').dataset.questionId = questionId;
    
    answermodal.style.display = 'flex';
    questionModel = true;
}

function goBackFromQuestionView(questionId) {
    // show the main qa forum elements
    document.querySelector('.qa-question-view').style.display = 'none';
    if (currentFilter === 'tag') {
        document.querySelector('.qa-tag-filter').style.display = 'block';
    } else if (currentFilter === 'search') {
        document.querySelector('.qa-search-results').style.display = 'block';
    } else {
        document.querySelector('.qa-main').style.display = 'block';
    }
    document.querySelector('.qa-header').style.display = 'block';
    document.querySelector('.qa-sticky-btn').style.display = 'flex';

    // Duplicate ids can exist across different containers; update every matching card.
    const questionCards = Array.from(document.querySelectorAll('.qa-question-card'))
        .filter(card => card.id === String(questionId));

    const upvoted = document.querySelector('.qa-question-view .upvote').classList.contains('active');
    const downvoted = document.querySelector('.qa-question-view .downvote').classList.contains('active');
    const voteCountText = document.querySelector('.qa-question-view .vote-count').textContent;
    const anwerCount = document.querySelector('.qa-question-view .qa-answer-count');
    const answerCountNumber = anwerCount.textContent.match(/\d+/)?.[0] || '0';
    let lastVisibleCard = null;
    
    questionCards.forEach(questionCard => {
        questionCard.querySelector('.upvote').classList.remove('active');
        questionCard.querySelector('.downvote').classList.remove('active');

        if (upvoted) {
            questionCard.querySelector('.upvote').classList.add('active');
        }
        if (downvoted) {
            questionCard.querySelector('.downvote').classList.add('active');
        }

        questionCard.querySelector('.vote-count').textContent = voteCountText;

        questionCard.querySelector('.qa-answer-count').innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
    </svg>${answerCountNumber}`;

        // Scroll every match; hidden containers won't move viewport.
        questionCard.scrollIntoView({ behavior: 'auto', block: 'center' });

        if (questionCard.offsetParent !== null) {
            lastVisibleCard = questionCard;
        }
    });

    // Ensure final position lands on a visible card.
    if (lastVisibleCard) {
        lastVisibleCard.scrollIntoView({ behavior: 'auto', block: 'center' });
    }

    resetQuestionView();
}

// rendering the question view page
async function viewQuestion(questionId) {
    // hide the main qa forum elements
    if (currentFilter === 'tag') {
        document.querySelector('.qa-tag-filter').style.display = 'none';
    } else if (currentFilter === 'search') {
        document.querySelector('.qa-search-results').style.display = 'none';
    } else {
        document.querySelector('.qa-main').style.display = 'none';
    }
    document.querySelector('.qa-header').style.display = 'none';
    document.querySelector('.qa-sticky-btn').style.display = 'none';

    const qaViewModal = document.querySelector('.qa-question-view');
    qaViewModal.querySelector('#qaViewModalQuestionId').textContent = questionId;

    // load the question details from an AJAX call
    const question = await loadQuestionDetails(questionId);

    // copy avatar src from the question card into the view modal
    if (question.user_avatar) {
        qaViewModal.querySelector('.qa-avatar-img').src = 'public' + question.user_avatar;
    } else {
        qaViewModal.querySelector('.qa-avatar-img').src = document.getElementById('default-pfp').src;
    }
    qaViewModal.querySelector('.qa-username').textContent = question.username;
    qaViewModal.querySelector('.qa-role').textContent = question.user_role;
    

    const addedTime = new Date(question.added_time);
    const lastModified = new Date(question.last_modified);
    if (addedTime.getTime() === lastModified.getTime()) {
        qaViewModal.querySelector('.qa-time').textContent = getRelativeTime(addedTime);
    } else {
        qaViewModal.querySelector('.qa-modified').textContent = '(edited)';
    }

    // Create and append the menu container
    if (question.user_id == userID || isModerator) {
        qaViewModal.querySelector('.qa-menu-btn').style.display = 'block';
        
        // Create and append the menu container
        generateMenuDropdown(qaViewModal, question.user_id, userID, isModerator, questionId);
    }

    // Style the title with hashtags
    const styledTitle = question.question.replace(/#(\w+)/g, '<span class="hashtag">#$1</span>');
    qaViewModal.querySelector('.qa-view-title').innerHTML = styledTitle;
    qaViewModal.querySelector('.qa-view-body').textContent = question.text;

    // make the image array
    question.images = question.img_path ? question.img_path.split(',') : [];

    // make the image gallery if there are images
    if (question.images.length > 0) {
        const imageGallery = qaViewModal.querySelector('.qa-view-images');
        const imgContainer = imageGallery.querySelector('.qa-img-container');
        const prevBtn = imageGallery.querySelector('.qa-img-prev');
        const nextBtn = imageGallery.querySelector('.qa-img-next');
        
        imageGallery.style.display = 'flex';
        let currentImageIndex = 0;
        let length = question.images.length;
        
        // Set first image
        imgContainer.querySelector('img').src = question.images[0];
        
        // Hide/show nav buttons based on number of images
        if (question.images.length === 1) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'flex';
            nextBtn.style.display = 'flex';
        }
        
        // Previous button click handler
        prevBtn.onclick = function() {
            currentImageIndex = (currentImageIndex - 1 + length) % length;
            imgContainer.querySelector('img').src = question.images[currentImageIndex];
        };
        
        // Next button click handler
        nextBtn.onclick = function() {
            currentImageIndex = (currentImageIndex + 1) % length;
            imgContainer.querySelector('img').src = question.images[currentImageIndex];
        };
    } else {
        qaViewModal.querySelector('.qa-view-images').style.display = 'none';
    }

    // load the current vote status
    qaViewModal.querySelector('.vote-count').textContent = question.vote_count;
    if (question.user_vote === 1) {
        qaViewModal.querySelector('.upvote').classList.add('active');
    } else if (question.user_vote === -1) {
        qaViewModal.querySelector('.downvote').classList.add('active');
    }

    // populate the answers section
    if (question.answer_count == 1) {
        qaViewModal.querySelector('.qa-answer-count').textContent = `1 Answer`;
    } else {
        qaViewModal.querySelector('.qa-answer-count').textContent = `${question.answer_count} Answers`;
    }

    let answerTemplate = qaViewModal.querySelector('.qa-answer-card');
    answerTemplate.style.display = 'none';

    // populate for each answer
    let answers = await getAnswersForQuestion(questionId); 
    if (answers != null) {
        for (let answer of answers) {
            const card = answerTemplate.cloneNode(true);
            // set the answer id as the id of the card for easy reference when editing/deleting
            card.id = `answer-${answer.a_id}`;
            if (answer.user_avatar) {
                card.querySelector('.qa-avatar-img').src = 'public' + answer.user_avatar;
            } else {
                card.querySelector('.qa-avatar-img').src = document.getElementById('default-pfp').src;
            }
            if (answer.username.length >12) {
                answer.username = answer.username.split(' ')[0];
            }
            card.querySelector('.qa-username').textContent = answer.username;
            card.querySelector('.qa-role').textContent = answer.user_role;

            const ansAddedTime = new Date(answer.added_time);
            card.querySelector('.qa-time').textContent = getRelativeTime(ansAddedTime);
            card.querySelector('.qa-answer-body').textContent = answer.text;

            // Create and append the menu container
            if (answer.user_id == userID || isModerator) {
                card.querySelector('.qa-menu-btn').style.display = 'block';
                
                // Create and append the menu container
                generateMenuDropdown(card, answer.user_id, userID, isModerator, questionId, answer.a_id);
            }

            qaViewModal.querySelector('.qa-view-answers').appendChild(card);
            card.style.display = 'flex';
        }
    }
    // Scroll the question view and its answers to the top, and bring page to top
    qaViewModal.style.display = 'flex';
}

// rendering question cards
function makeQuestionCard(data, position) {
    // Clone the template
    const card = questionCardTemplate.cloneNode(true); // Use the global variable
    
    // Change the id to the questionId
    card.id = data.questionId;
    card.classList.remove('template');
    card.style.display = 'flex';

    // Populate user info
    card.querySelector('#qa-user-id').value = data.userID;
    if (data.user_avatar) {
        card.querySelector('.qa-avatar-img').src = 'public' + data.user_avatar;
    } else {
        card.querySelector('.qa-avatar-img').src = document.getElementById('default-pfp').src;
    }
    card.querySelector('.qa-username').textContent = data.username;
    card.querySelector('.qa-role').textContent = data.user_role;
    
    // Populate question content
    const styledTitle = data.questionTitle.replace(/#(\w+)/g, '<span class="hashtag">#$1</span>');
    card.querySelector('.qa-question-title').innerHTML = styledTitle;
    card.querySelector('.qa-question-text').textContent = data.questionText;
    
    card.querySelector('.qa-time').textContent = data.timestamp;
    if (data.modified) {
        card.querySelector('.qa-modified').textContent = '(edited)';
    }

    card.querySelector('.vote-count').textContent = data.voteCount;
    if (data.voteStatus === 1) {
        card.querySelector('.upvote').classList.add('active');
    } else if (data.voteStatus === -1) {
        card.querySelector('.downvote').classList.add('active');
    }

    // Add click handler for upvote button
    const upvoteBtn = card.querySelector('.upvote');
    const downvoteBtn = card.querySelector('.downvote');
    upvoteBtn.addEventListener('click', function() {
        submitVote(card, data.questionId, this);
    });
    downvoteBtn.addEventListener('click', function() {
        submitVote(card, data.questionId, this);
    });


    card.querySelector('.qa-answer-count').innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
    </svg>${data.answerCount}`;
    
    // Handle images
    const imagePreview = card.querySelector('.qa-question-image-preview');
    const imageCount = card.querySelector('.qa-image-count');
    const img = imagePreview.querySelector('img');
    if (data.imagecount > 0) {
        imagePreview.style.display = 'block';
        img.src = data.firstImage;
        img.style.maxWidth = '75px'; // Resize to max 750px width
        img.style.height = 'auto'; // Maintain aspect ratio
        if (data.imagecount > 1) {
            img.classList.add('darkened'); // Darken the image
            imageCount.style.display = 'block';
            imageCount.textContent = `+${data.imagecount - 1}`;
        } else {
            img.classList.remove('darkened');
            imageCount.style.display = 'none';
        }
    } else {
        imagePreview.style.display = 'none';
    }

    // add clickable functionalities for the question card
    // Create and append the menu container
    if (data.userID == userID || isModerator) {
        card.querySelector('.qa-menu-btn').style.display = 'block';
        generateMenuDropdown(card, data.userID, userID, isModerator, data.questionId);
    }
    
    // Add click handler for answer button
    const answerBtn = card.querySelector('.answer-btn');
    answerBtn.addEventListener('click', function() {
        answer(data.questionId);
    });

    // Add click handler for view question button
    const viewQuestionBtn = card.querySelector('.view-question-btn');
    viewQuestionBtn.addEventListener('click', function() {
        viewQuestion(data.questionId);
    });
    
    // Prepend the card to the cuurent filter
    let holder;
    if (currentFilter === 'default') {
        holder = document.querySelector('.qa-main');
    } else if (currentFilter === 'search') {
        holder = document.querySelector('.qa-search-results');
    } else if (currentFilter === 'tag') {
        holder = document.querySelector('.qa-tag-filter');
    }

    if (position === 0) {
        holder.prepend(card);
    } else if (position === -1) {
        holder.appendChild(card);
    }
}

function getRelativeTime(date) {
    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);
    const diffWeek = Math.floor(diffDay / 7);
    const diffMonth = Math.floor(diffDay / 30);
    const diffYear = Math.floor(diffDay / 365);

    if (diffSec < 60) return 'Just now';
    if (diffMin < 60) return `${diffMin} min ago`;
    if (diffHour < 24) return `${diffHour} hour${diffHour > 1 ? 's' : ''} ago`;
    if (diffDay < 7) return `${diffDay} day${diffDay > 1 ? 's' : ''} ago`;
    if (diffWeek < 4) return `${diffWeek} week${diffWeek > 1 ? 's' : ''} ago`;
    if (diffMonth < 12) return `${diffMonth} month${diffMonth > 1 ? 's' : ''} ago`;
    return `${diffYear} year${diffYear > 1 ? 's' : ''} ago`;
}

function parseHashtags() {
    const titleDiv = document.getElementById('qa-question-title');
    
    // Save cursor position
    const selection = window.getSelection();
    let cursorPosition = 0;
    if (selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        const preCaretRange = range.cloneRange();
        preCaretRange.selectNodeContents(titleDiv);
        preCaretRange.setEnd(range.endContainer, range.endOffset);
        cursorPosition = preCaretRange.toString().length;
    }
    
    const text = titleDiv.textContent;
    
    // Simple regex replacement: match # followed by word characters
    const styledText = text.replace(/#(\w+)/g, '<span class="hashtag">#$1</span>');
    
    // Only update if HTML changed (prevents cursor jumping on every keystroke)
    if (titleDiv.innerHTML !== styledText) {
        titleDiv.innerHTML = styledText;
        
        // Restore cursor position
        const restorePosition = (node, targetOffset) => {
            let currentOffset = 0;
            const walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT, null, false);
            
            while (walker.nextNode()) {
                const textNode = walker.currentNode;
                const textLength = textNode.textContent.length;
                
                if (currentOffset + textLength >= targetOffset) {
                    const range = document.createRange();
                    range.setStart(textNode, Math.min(targetOffset - currentOffset, textLength));
                    range.collapse(true);
                    selection.removeAllRanges();
                    selection.addRange(range);
                    return;
                }
                currentOffset += textLength;
            }
            
            // If we couldn't find the exact position, move to end
            const range = document.createRange();
            range.selectNodeContents(titleDiv);
            range.collapse(false);
            selection.removeAllRanges();
            selection.addRange(range);
        };
        
        if (titleDiv.textContent.length > 0) {
            restorePosition(titleDiv, cursorPosition);
        }
    }
}

//////////////////////////////////////////////////////////////////////
// Skeleton card management

let hiddenSkeletons = [];

function showSkeletonCards() {
    const qaMain = document.querySelector('.qa-main');
    hiddenSkeletons.forEach(card => {
        qaMain.appendChild(card); // Append to ensure it's at the bottom
        card.style.display = 'flex';
    });
    hiddenSkeletons = [];
}

function hideSkeletonCards() {
    const qaMain = document.querySelector('.qa-main');
    const skeletonCards = qaMain.querySelectorAll('.qa-question-card:not(.template):not([id])');
    hiddenSkeletons = Array.from(skeletonCards);
    skeletonCards.forEach(card => {
        qaMain.removeChild(card);
    });
}

//////////////////////////////////////////////////////////////////////
// Question fetching and lazy loading


function fetchQuestions() {
    if (isFetching || !hasMoreQuestions) {
        return;
    }

    isFetching = true;
    showSkeletonCards();

    const url = `http://localhost/unihelper/api?controller=qaController&action=getQuestions&offset=${current_question_pointer}&limit=${batch_limit}&tag=${currentTag}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {

            console.log('Fetched questions:', data);

            hideSkeletonCards();

            if (data.success) {
                if (data.data === null || data.data.length === 0) {
                    // No more questions to load
                    hasMoreQuestions = false;
                    console.log(questions_ids)
                } else {
                    // Process questions iteratively and render cards
                    data.data.forEach(question => {
                        if (questions_ids.includes(question.q_id)) {
                            return; // Skip if already loaded
                        }
                        const addedTime = new Date(question.added_time);
                        const lastModified = new Date(question.last_modified);
                        let timestamp;
                        if (addedTime.getTime() === lastModified.getTime()) {
                            timestamp = getRelativeTime(addedTime);
                            modified = false;
                        } else {
                            timestamp = getRelativeTime(lastModified);
                            modified = true;
                        }
                        const mappedData = {
                            userID: question.user_id,
                            username: question.username,
                            user_role: question.user_role,
                            moderator_status: question.moderator_status,
                            user_avatar: question.user_avatar,
                            questionId: question.q_id,
                            voteCount: question.vote_count,
                            voteStatus: question.user_vote,
                            answerCount: question.answer_count,
                            questionTitle: question.question,
                            questionText: question.text,
                            timestamp: timestamp,
                            imagecount: question.img_path ? question.img_path.split(',').length : 0,
                            firstImage: question.img_path ? question.img_path.split(',')[0] : '',
                            modified: modified
                        };
                        // show the question card
                        makeQuestionCard(mappedData, -1);
                        questions_ids.push(question.q_id);
                    });
                    
                    // Update the pointer for next batch
                    current_question_pointer += data.data.length;
                }
            } else {
                console.error('Error fetching questions:', data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching questions:', error);
            showSkeletonCards();
        })
        .finally(() => {
            isFetching = false;
        });
}

function handleScroll() {
    // Check if user scrolled near bottom of page
    const scrollPosition = window.innerHeight + window.scrollY;
    const pageHeight = document.documentElement.scrollHeight;
    const threshold = 200; // Trigger when 200px from bottom

    if (scrollPosition >= pageHeight - threshold) {
        fetchQuestions();
    }
}

async function loadQuestionDetails(questionId) {
    return fetch(`http://localhost/unihelper/api?controller=qaController&action=getQuestion&questionId=${questionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                return data.data;
            } else {
                return null;
            }
        })
        .catch(error => {
            console.error('Error fetching question details:', error);
            return null;
        });
}

async function getAnswersForQuestion(questionId) {
    return fetch(`http://localhost/unihelper/api?controller=qaController&action=getAnswers&questionId=${questionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                return data.data;
            } else {
                return [];
            }
        })
        .catch(error => {
            console.error('Error fetching answers:', error);
            return [];
        });
}

async function submitVote(element, id, clickedBtn) {
    const questionId = id;

    const upvoteBtn   = element.querySelector('.upvote');
    const downvoteBtn = element.querySelector('.downvote');
    const voteCountEl = element.querySelector('.vote-count');

    // ---- SNAPSHOT (for rollback) ----
    const prevCount = voteCountEl.textContent;
    const prevUp = upvoteBtn.classList.contains('active');
    const prevDown = downvoteBtn.classList.contains('active');

    let count = parseInt(prevCount, 10);

    const activeBtn =
        prevUp ? upvoteBtn :
        prevDown ? downvoteBtn :
        null;

    let voteValue;

    // ---- STATE TRANSITIONS ----

    // 1. no active vote
    if (!activeBtn) {
        if (clickedBtn === upvoteBtn) {
            voteValue = 1; // final state
            count += 1;
            upvoteBtn.classList.add('active');
        } else {
            voteValue = -1; // final state
            count -= 1;
            downvoteBtn.classList.add('active');
        }
    }

    // 2. undo vote
    else if (activeBtn === clickedBtn) {
        activeBtn.classList.remove('active');

        voteValue = 0; // final state after undo

        if (clickedBtn === upvoteBtn) {
            count -= 1;
        } else {
            count += 1;
        }
    }

    // 3. switch vote (IMPORTANT: ±2)
    else {
        activeBtn.classList.remove('active');

        if (clickedBtn === upvoteBtn) {
            voteValue = 1; // final state
            count += 2;
            upvoteBtn.classList.add('active');
        } else {
            voteValue = -1; // final state
            count -= 2;
            downvoteBtn.classList.add('active');
        }
    }

    voteCountEl.textContent = count;

    // ---- REQUEST ----
    try {
        const res = await fetch(
            `http://localhost/unihelper/api?controller=qaController&action=Vote&question_id=${questionId}&vote_value=${voteValue}`,
            { method: 'GET' }
        );

        const data = await res.json();

        if (!data.success) throw new Error();

    } catch {
        // ---- ROLLBACK ----
        voteCountEl.textContent = prevCount;
        upvoteBtn.classList.toggle('active', prevUp);
        downvoteBtn.classList.toggle('active', prevDown);
    }
}

//////////////////////////////////////////////////////////////////////

// tag filtering

function tagOnClick(tag) {
    // Update current filter
    currentFilter = 'tag';
    currentTag = tag;
    current_question_pointer_temp = current_question_pointer;
    current_question_pointer = 0;
    questions_ids_temp = [...questions_ids];
    questions_ids = [];
    hasMoreQuestions_temp = hasMoreQuestions;
    hasMoreQuestions = true;
    // hide the main question list and show the tag filter container
    document.querySelector('.qa-main').style.display = 'none';
    document.querySelector('.qa-tag-filter').style.display = 'block';
    document.querySelector('.qa-sticky-btn').style.display = 'none';
    document.querySelector('.qa-tag-filter').innerHTML = '';
    fetchQuestions();
}

function tagOffClick() {
    // Reset to default filter
    currentFilter = 'default';
    currentTag = 'default';
    current_question_pointer = current_question_pointer_temp;
    questions_ids = [...questions_ids_temp];
    hasMoreQuestions = hasMoreQuestions_temp;
    // show the main question list and hide the tag filter container
    document.querySelector('.qa-main').style.display = 'block';
    document.querySelector('.qa-tag-filter').style.display = 'none';
    document.querySelector('.qa-sticky-btn').style.display = 'flex';
    // placeholder for resetting filter function
}

// deleting a question
async function deleteQuestion(questionId) {
    if (!await confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
        return false;
    }

    try {
        const response = await fetch(`http://localhost/unihelper/api?controller=qaController&action=deleteQuestion&questionId=${questionId}`, {
            method: 'GET'
        });
        const data = await response.json();

        if (data.success) {
            // Remove every matching card because the same question can appear in multiple containers.
            const questionCards = Array.from(document.querySelectorAll('.qa-question-card'))
                .filter(card => card.id === String(questionId));
            questionCards.forEach(card => card.remove());
            goBackFromQuestionView(questionId); // Ensure we exit the question view if we're in it
            showToast('Question deleted successfully.', 'success');
            return true;
        }

        showToast('Failed to delete the question. Please try again.', 'error');
        return false;
    } catch (error) {
        console.error('Error deleting question:', error);
        showToast('Failed to delete the question. Please try again.', 'error');
        return false;
    }
}

// deleting an answer
async function deleteAnswer(answerId) {
    if (!await confirm('Are you sure you want to delete this answer? This action cannot be undone.')) {
        return false;
    }

    try {
        const response = await fetch(`http://localhost/unihelper/api?controller=qaController&action=deleteAnswer&answerId=${answerId}`, {
            method: 'GET'
        });
        const data = await response.json();

        if (data.success) {
            // Remove the answer card
            const answerCard = document.getElementById(`answer-${answerId}`);
            if (answerCard) {
                answerCard.remove();
            }
            // also reduce the answer count in the question view
            const answerCountEl = document.querySelector('.qa-question-view .qa-answer-count');
            let answerCount = parseInt(answerCountEl.textContent) || 0;
            answerCount = Math.max(0, answerCount - 1);
            if (answerCount === 1) {
                answerCountEl.textContent = `1 Answer`;
            } else {
                answerCountEl.textContent = `${answerCount} Answers`;
            }
            showToast('Answer deleted successfully.', 'success');
            return true;
        }

        showToast('Failed to delete the answer. Please try again.', 'error');
        return false;
    } catch (error) {
        console.error('Error deleting answer:', error);
        showToast('Failed to delete the answer. Please try again.', 'error');
        return false;
    }
}