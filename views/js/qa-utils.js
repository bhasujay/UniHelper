//////////////////////////////////////////////////////////////////////

function resetAnswerForm() {
    document.getElementById('qaAnswerForm').reset();
    const label = document.querySelector('.qa-answermodal .qa-form-label');
    label.innerHTML = 'Your Answer';
}

function resetForm() {
    const titleDiv = document.getElementById('qa-question-title');
    titleDiv.innerHTML = '';
    document.getElementById('qa-question-body').value = '';
    selectedFiles = [];
    // Remove all image previews
    document.querySelectorAll('.qa-image-preview').forEach(preview => preview.remove());
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
        card.querySelector('.qa-avatar-img').src = document.getElementById('default-pfpf').src;
    }
    card.querySelector('.qa-username').textContent = data.username;
    card.querySelector('.qa-role').textContent = data.user_role;
    
    // Populate question content
    const styledTitle = data.questionTitle.replace(/#(\w+)/g, '<span class="hashtag">#$1</span>');
    card.querySelector('.qa-question-title').innerHTML = styledTitle;
    card.querySelector('.qa-question-text').textContent = data.questionText;
    
    card.querySelector('.qa-time').textContent = data.timestamp;
    card.querySelector('.vote-count').textContent = data.voteCount;
    if (data.voteStatus === 1) {
        card.querySelector('.upvote').classList.add('active');
    } else if (data.voteStatus === -1) {
        card.querySelector('.downvote').classList.add('active');
    }

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
    
    // Prepend the card to the .qa-main div
    const qaMain = document.querySelector('.qa-main');
    if (position === 0) {
        qaMain.prepend(card);
    } else if (position === -1) {
        qaMain.appendChild(card);
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

function showSkeletonCards() {
    const qaMain = document.querySelector('.qa-main');
    const skeletonCards = qaMain.querySelectorAll('.qa-question-card:not(.template):not([id])');
    skeletonCards.forEach(card => {
        card.style.display = 'flex';
    });
}

function hideSkeletonCards() {
    const qaMain = document.querySelector('.qa-main');
    const skeletonCards = qaMain.querySelectorAll('.qa-question-card:not(.template):not([id])');
    skeletonCards.forEach(card => {
        card.style.display = 'none';
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

    const url = `http://localhost/unihelper/api?controller=qaController&action=getQuestions&offset=${current_question_pointer}&limit=${batch_limit}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            hideSkeletonCards();

            if (data.success) {
                if (data.data === null || data.data.length === 0) {
                    // No more questions to load
                    hasMoreQuestions = false;
                } else {
                    // Process and display questions
                    data.data.forEach(question => {
                        const addedTime = new Date(question.added_time);
                        const lastModified = new Date(question.last_modified);
                        let timestamp;
                        if (addedTime.getTime() === lastModified.getTime()) {
                            timestamp = getRelativeTime(addedTime);
                        } else {
                            timestamp = getRelativeTime(lastModified) + ' [modified]';
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
                            firstImage: question.img_path ? question.img_path.split(',')[0] : ''
                        };
                        makeQuestionCard(mappedData, -1);
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
            hideSkeletonCards();
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