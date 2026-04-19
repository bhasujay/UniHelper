var questions_ids = [];
var questions_ids_temp = [];
var hasMoreQuestions_temp = true;
var FLAGGED_TOOLTIP_TEXT = 'This content may violate our community guidelines and is under review by moderators.';

//////////////////////////////////////////////////////////////////////
// Badge Hover Panel
// One shared singleton panel; cleaned up and rebuilt on each trigger.

var _badgePanel = null;          // the live DOM panel
var _badgeHideTimer = null;      // 3-second grace-period timer
var _badgeCache = {};            // userId -> badges array (in-memory cache)

/**
 * Attach badge-panel hover behaviour to a username element.
 * @param {HTMLElement} el       The .qa-username span
 * @param {string|number} userId The user whose badges to fetch
 * @param {'above'|'below'} dir  Panel direction relative to the username
 */
function bindBadgeHoverPanel(el, userId, dir) {
    el.addEventListener('mouseenter', function() {
        _clearBadgeHideTimer();
        _showBadgePanel(el, userId, dir);
    });
    el.addEventListener('mouseleave', function() {
        _startBadgeHideTimer();
    });
}

function _clearBadgeHideTimer() {
    if (_badgeHideTimer) {
        clearTimeout(_badgeHideTimer);
        _badgeHideTimer = null;
    }
}

function _startBadgeHideTimer() {
    _clearBadgeHideTimer();
    _badgeHideTimer = setTimeout(_hideBadgePanel, 100);
}

function _hideBadgePanel() {
    if (_badgePanel) {
        _badgePanel.remove();
        _badgePanel = null;
    }
    _clearBadgeHideTimer();
}

function _showBadgePanel(anchorEl, userId, dir) {
    // Remove any existing panel first
    _hideBadgePanel();

    const panel = document.createElement('div');
    panel.className = 'qa-badge-panel' + (dir === 'above' ? ' panel-above' : ' panel-below');

    // Keep the panel alive while the pointer is on it
    panel.addEventListener('mouseenter', _clearBadgeHideTimer);
    panel.addEventListener('mouseleave', _startBadgeHideTimer);

    _badgePanel = panel;

    // Position relative to the anchor element
    _positionPanel(panel, anchorEl, dir);
    document.body.appendChild(panel);

    // Show skeleton (3 circles) while loading
    for (let i = 0; i < 3; i++) {
        const sk = document.createElement('div');
        sk.className = 'qa-badge-skeleton';
        panel.appendChild(sk);
    }

    // Animate in
    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            if (panel === _badgePanel) panel.classList.add('visible');
        });
    });

    // Fetch (or use cache)
    _fetchBadges(userId).then(function(badges) {
        // Panel may have been removed while we were fetching
        if (panel !== _badgePanel) return;

        panel.innerHTML = '';   // clear skeletons

        if (!badges || badges.length === 0) {
            _hideBadgePanel();
            return;
        }

        // Cap at 12
        badges.slice(0, 12).forEach(function(badge) {
            const item = document.createElement('div');
            item.className = 'qa-badge-item';
            // Tooltip: first line = name, second line = description.
            item.setAttribute('data-tooltip', badge.name + '\n' + badge.description);

            const img = document.createElement('img');
            // concat the /unihelper prefix for relative paths, but leave absolute URLs alone (for external badges)
            img.src = badge.image_url.startsWith('/') ? '/unihelper' + badge.image_url : badge.image_url;
            img.alt = badge.name;
            img.width = 100;
            img.height = 100;
            item.appendChild(img);
            panel.appendChild(item);
        });

        // Re-position after real content fills it (width may have changed)
        _positionPanel(panel, anchorEl, dir);
    });
}

function _positionPanel(panel, anchorEl, dir) {
    const rect = anchorEl.getBoundingClientRect();
    const scrollX = window.scrollX || window.pageXOffset;
    const scrollY = window.scrollY || window.pageYOffset;

    // Horizontal: left-align with the username
    let left = rect.left + scrollX;
    const panelW = panel.offsetWidth || 160; // estimate before paint
    // Clamp to viewport
    const maxLeft = window.innerWidth + scrollX - panelW - 8;
    if (left > maxLeft) left = maxLeft;

    panel.style.left = left + 'px';

    if (dir === 'above') {
        panel.style.top = (rect.top + scrollY - (panel.offsetHeight || 50) - 8) + 'px';
    } else {
        panel.style.top = (rect.bottom + scrollY + 8) + 'px';
    }
}

function _fetchBadges(userId) {
    if (_badgeCache[userId]) {
        return Promise.resolve(_badgeCache[userId]);
    }
    return fetch('/unihelper/api?controller=badgeController&action=getBadgesForUser&user_id=' + encodeURIComponent(userId))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var result = (data.success && Array.isArray(data.badges)) ? data.badges : [];
            _badgeCache[userId] = result;
            return result;
        })
        .catch(function() { return []; });
}

function isFlaggedStatus(status) {
    return String(status || '').toLowerCase() === 'flagged';
}

function applyFlaggedState(element, status) {
    if (!element) {
        return;
    }

    if (isFlaggedStatus(status)) {
        element.classList.add('flagged-content');
        element.setAttribute('title', FLAGGED_TOOLTIP_TEXT);
    } else {
        element.classList.remove('flagged-content');
        element.removeAttribute('title');
    }
}

var _qaImageLightbox = null;

function getQuestionImageLightbox() {
    if (_qaImageLightbox) {
        return _qaImageLightbox;
    }

    const lightbox = document.createElement('div');
    lightbox.className = 'qa-image-lightbox';
    lightbox.style.display = 'none';

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'qa-image-lightbox-close';
    closeBtn.setAttribute('aria-label', 'Close image preview');
    closeBtn.textContent = '❌';

    const image = document.createElement('img');
    image.className = 'qa-image-lightbox-img';
    image.alt = 'Question image preview';

    closeBtn.addEventListener('click', closeQuestionImageLightbox);

    lightbox.appendChild(closeBtn);
    lightbox.appendChild(image);
    document.body.appendChild(lightbox);

    _qaImageLightbox = lightbox;
    return _qaImageLightbox;
}

function openQuestionImageLightbox(src, altText) {
    if (!src) {
        return;
    }

    const lightbox = getQuestionImageLightbox();
    const image = lightbox.querySelector('.qa-image-lightbox-img');

    image.src = src;
    image.alt = altText || 'Question image preview';
    lightbox.style.display = 'flex';
}

function closeQuestionImageLightbox() {
    if (!_qaImageLightbox) {
        return;
    }

    const image = _qaImageLightbox.querySelector('.qa-image-lightbox-img');
    if (image) {
        image.src = '';
    }

    _qaImageLightbox.style.display = 'none';
}

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

    reportBtn.style.display = 'block';

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

    if (reportBtn) {
        reportBtn.onclick = function(e) {
            e.stopPropagation();
            menuContainer.style.display = 'none';
            document.getElementById('qa-report-type').value = isAnswer >= 0 ? 'answer' : 'question';
            document.getElementById('qa-report-id').value = isAnswer >= 0 ? isAnswer : questionId;
            const reportModal = document.querySelector('.qa-reportmodal');
            if (reportModal) {
                reportModal.style.display = 'flex';
            }
        };
    }

    if (editBtn) {
        editBtn.onclick = async function(e) {
            e.stopPropagation();
            menuContainer.style.display = 'none';
            await editQuestion(questionId);
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
    const questionTitleEl = document.querySelector('.qa-answermodal .qa-answer-question-title');
    const usernameEl = document.querySelector('.qa-answermodal .answer-to-username');
    if (questionTitleEl) {
        questionTitleEl.textContent = '';
    }
    if (usernameEl) {
        usernameEl.textContent = '';
    }
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
    closeQuestionImageLightbox();

    const qaViewModal = document.querySelector('.qa-question-view');
    applyFlaggedState(qaViewModal.querySelector('.qa-view-content'), 'normal');
    
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

function scrollQuestionViewTop() {
    const injectedMain = document.getElementById('dashboardMain');
    if (injectedMain && typeof injectedMain.scrollTo === 'function') {
        injectedMain.scrollTo({ top: 0, left: 0, behavior: 'auto' });
    }

    const mainFallback = document.querySelector('main.main-content');
    if (mainFallback && mainFallback !== injectedMain && typeof mainFallback.scrollTo === 'function') {
        mainFallback.scrollTo({ top: 0, left: 0, behavior: 'auto' });
    }

    if (typeof window.scrollTo === 'function') {
        window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
    }
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
    const label = answermodal.querySelector('.qa-form-label');

    let username = '';
    const questionCard = document.getElementById(questionId);
    if (questionCard) {
        const cardUsername = questionCard.querySelector('.qa-username');
        if (cardUsername) {
            username = cardUsername.textContent.trim();
        }
    }

    // Deep-link flow can open question view without rendering a feed card first.
    if (!username) {
        const qaView = document.querySelector('.qa-question-view');
        const viewQuestionIdEl = qaView ? qaView.querySelector('#qaViewModalQuestionId') : null;
        if (qaView && qaView.style.display === 'flex' && viewQuestionIdEl && String(viewQuestionIdEl.textContent) === String(questionId)) {
            const viewUsername = qaView.querySelector('.qa-username');
            if (viewUsername) {
                username = viewUsername.textContent.trim();
            }
        }
    }

    label.textContent = 'Your Answer';
    if (username) {
        label.textContent = 'Your Answer to ';
        const usernameSpan = document.createElement('span');
        usernameSpan.className = 'answer-to-username';
        usernameSpan.textContent = username;
        label.appendChild(usernameSpan);
    }
    
    // Store question ID in the form
    document.getElementById('qaAnswerForm').dataset.questionId = questionId;
    
    answermodal.style.display = 'flex';
    questionModel = true;
}

function goBackFromQuestionView(questionId) {
    closeQuestionImageLightbox();

    // show the main qa forum elements
    document.querySelector('.qa-question-view').style.display = 'none';
    if (currentFilter === 'tag' || currentFilter === 'user') {
        document.querySelector('.qa-tag-filter').style.display = 'block';
    } else if (currentFilter === 'search') {
        document.querySelector('.qa-search-results').style.display = 'block';
    } else {
        document.querySelector('.qa-main').style.display = 'block';
    }
    document.querySelector('.qa-header').style.display = '';
    document.querySelector('.qa-sticky-btn').style.display = 'flex';

    // Duplicate ids can exist across different containers; update every matching card.
    const questionCards = Array.from(document.querySelectorAll('.qa-question-card'))
        .filter(card => card.id === String(questionId));

    const upvoted = document.querySelector('.qa-question-view .upvote').classList.contains('active');
    const downvoted = document.querySelector('.qa-question-view .downvote').classList.contains('active');
    const voteCountText = document.querySelector('.qa-question-view .vote-count').textContent;
    const anwerCount = document.querySelector('.qa-question-view .qa-answer-count');
    const answerCountNumber = anwerCount.textContent.match(/\d+/)?.[0] || '0';

    // ---- Deep-link fallback: card not in the feed ----
    if (questionCards.length === 0 && window._deepLinkQuestionId === String(questionId)) {
        // We came via a direct link — the card was never in the feed.
        // Fetch it, build the card, prepend it to .qa-main, and scroll to it.
        loadQuestionDetails(questionId).then(function(question) {
            if (!question) {
                // Question may have been deleted in the meantime — just reset.
                resetQuestionView();
                return;
            }

            const addedTime  = new Date(question.added_time);
            const lastModified = new Date(question.last_modified);
            let timestamp, modified;
            if (addedTime.getTime() === lastModified.getTime()) {
                timestamp = getRelativeTime(addedTime);
                modified = false;
            } else {
                timestamp = getRelativeTime(lastModified);
                modified = true;
            }

            const mappedData = {
                userID:          question.user_id,
                username:        question.username,
                user_role:       question.user_role,
                moderator_status: question.moderator_status || '0',
                user_avatar:     question.user_avatar,
                questionId:      question.q_id,
                voteCount:       parseInt(voteCountText, 10),
                voteStatus:      upvoted ? 1 : (downvoted ? -1 : 0),
                answerCount:     parseInt(answerCountNumber, 10),
                questionTitle:   question.question,
                questionText:    question.text,
                timestamp:       timestamp,
                imagecount:      question.img_path ? question.img_path.split(',').length : 0,
                firstImage:      question.img_path ? question.img_path.split(',')[0] : '',
                modified:        modified,
                status:          question.status
            };

            // Temporarily force the default filter so the card goes into .qa-main
            const prevFilter = currentFilter;
            currentFilter = 'default';
            makeQuestionCard(mappedData, 0); // prepend
            currentFilter = prevFilter;

            // Track the id so it won't be duplicated on future fetches
            if (!questions_ids.includes(question.q_id)) {
                questions_ids.push(question.q_id);
            }

            // Scroll to the newly created card
            const newCard = document.getElementById(String(questionId));
            if (newCard) {
                newCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // Clear the deep link flag — it has been handled
            delete window._deepLinkQuestionId;
        });

        resetQuestionView();
        return;
    }

    // ---- Normal flow: card exists in the feed ----
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
    closeQuestionImageLightbox();

    // hide the main qa forum elements
    if (currentFilter === 'tag' || currentFilter === 'user') {
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
    const viewAvatarImg = qaViewModal.querySelector('.qa-avatar-img');
    if (question.user_avatar) {
        viewAvatarImg.src = 'public' + question.user_avatar;
    } else {
        viewAvatarImg.src = document.getElementById('default-pfp').src;
    }
    const viewUsernameEl = qaViewModal.querySelector('.qa-username');
    viewUsernameEl.textContent = question.username;
    qaViewModal.querySelector('.qa-role').textContent = question.user_role;

    const viewProfileUrl = `/unihelper/view/profile/${question.user_id}`;
    viewAvatarImg.parentElement.onclick = function(e) { e.stopPropagation(); window.open(viewProfileUrl, '_blank'); };
    viewAvatarImg.parentElement.style.cursor = 'pointer';
    viewUsernameEl.onclick = function(e) { e.stopPropagation(); window.open(viewProfileUrl, '_blank'); };
    viewUsernameEl.style.cursor = 'pointer';
    // Badge hover panel — question view opens below the name
    bindBadgeHoverPanel(viewUsernameEl, question.user_id, 'below');
    

    const normalizeSqlDateTime = function(dateTimeValue) {
        if (!dateTimeValue) return '';
        const raw = String(dateTimeValue).trim();
        if (!raw) return '';

        // Backend sends DATETIME-like values; keep an exact date+time text in SQL style.
        return raw
            .replace('T', ' ')
            .replace(/\.\d+$/, '')
            .replace(/Z$/, '');
    };

    const addedDateTime = normalizeSqlDateTime(question.added_time);
    const editedDateTime = normalizeSqlDateTime(question.last_modified);

    if (editedDateTime && editedDateTime !== addedDateTime) {
        qaViewModal.querySelector('.qa-time').textContent = editedDateTime;
        qaViewModal.querySelector('.qa-modified').textContent = '(edited)';
    } else {
        qaViewModal.querySelector('.qa-time').textContent = addedDateTime;
        qaViewModal.querySelector('.qa-modified').textContent = '';
    }

    // Create and append the menu container
    qaViewModal.querySelector('.qa-menu-btn').style.display = 'block';
    generateMenuDropdown(qaViewModal, question.user_id, userID, isModerator, questionId);
    applyFlaggedState(qaViewModal.querySelector('.qa-view-content'), question.status);

    // Style the title with hashtags
    const styledTitle = question.question.replace(/#(\w+)/g, '<span class="hashtag">#$1</span>');
    qaViewModal.querySelector('.qa-view-title').innerHTML = styledTitle;
    qaViewModal.querySelector('.qa-view-body').textContent = question.text;

    // Make hashtag tags clickable in question view
    bindHashtagClicks(qaViewModal);

    // make the image array
    question.images = question.img_path ? question.img_path.split(',') : [];

    // make the image gallery if there are images
    if (question.images.length > 0) {
        const imageGallery = qaViewModal.querySelector('.qa-view-images');
        const imgContainer = imageGallery.querySelector('.qa-img-container');
        const galleryImage = imgContainer.querySelector('img');
        const prevBtn = imageGallery.querySelector('.qa-img-prev');
        const nextBtn = imageGallery.querySelector('.qa-img-next');
        
        imageGallery.style.display = 'flex';
        let currentImageIndex = 0;
        let length = question.images.length;
        
        // Set first image
        galleryImage.src = question.images[0];
        galleryImage.style.cursor = 'zoom-in';
        galleryImage.onclick = function(e) {
            e.stopPropagation();
            openQuestionImageLightbox(galleryImage.src, question.question);
        };
        
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
            galleryImage.src = question.images[currentImageIndex];
        };
        
        // Next button click handler
        nextBtn.onclick = function() {
            currentImageIndex = (currentImageIndex + 1) % length;
            galleryImage.src = question.images[currentImageIndex];
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
            const ansAvatarImg = card.querySelector('.qa-avatar-img');
            if (answer.user_avatar) {
                ansAvatarImg.src = 'public' + answer.user_avatar;
            } else {
                ansAvatarImg.src = document.getElementById('default-pfp').src;
            }
            if (answer.username.length >12) {
                answer.username = answer.username.split(' ')[0];
            }
            const ansUsernameEl = card.querySelector('.qa-username');
            ansUsernameEl.textContent = answer.username;
            card.querySelector('.qa-role').textContent = answer.user_role;

            const ansProfileUrl = `/unihelper/view/profile/${answer.user_id}`;
            ansAvatarImg.parentElement.onclick = function(e) { e.stopPropagation(); window.open(ansProfileUrl, '_blank'); };
            ansAvatarImg.parentElement.style.cursor = 'pointer';
            ansUsernameEl.onclick = function(e) { e.stopPropagation(); window.open(ansProfileUrl, '_blank'); };
            ansUsernameEl.style.cursor = 'pointer';
            // Badge hover panel — answer cards in question view open above
            bindBadgeHoverPanel(ansUsernameEl, answer.user_id, 'above');

            const ansAddedTime = new Date(answer.added_time);
            card.querySelector('.qa-time').textContent = getRelativeTime(ansAddedTime);
            card.querySelector('.qa-answer-body').textContent = answer.text;

            // Create and append the menu container
            card.querySelector('.qa-menu-btn').style.display = 'block';
            generateMenuDropdown(card, answer.user_id, userID, isModerator, questionId, answer.a_id);
            applyFlaggedState(card, answer.status);

            qaViewModal.querySelector('.qa-view-answers').appendChild(card);
            card.style.display = 'flex';
        }
    }
    // QA is injected into dashboard main-content; reset scroll there on view switch.
    qaViewModal.style.display = 'flex';
    qaViewModal.scrollTop = 0;
    scrollQuestionViewTop();
}

// rendering question cards
// Truncate plain text to a maximum number of words, adding ellipsis if truncated.
function truncateWords(text, limit) {
    if (!text) return '';
    const words = String(text).trim().split(/\s+/);
    if (words.length <= limit) return words.join(' ');
    return words.slice(0, limit).join(' ') + '...';
}

function makeQuestionCard(data, position) {
    // Clone the template
    const card = questionCardTemplate.cloneNode(true); // Use the global variable
    
    // Change the id to the questionId
    card.id = data.questionId;
    card.classList.remove('template');
    card.style.display = 'flex';
    applyFlaggedState(card, data.status);

    // Populate user info
    card.querySelector('#qa-user-id').value = data.userID;
    const avatarImg = card.querySelector('.qa-avatar-img');
    if (data.user_avatar) {
        if (data.user_avatar.includes('http')) {
            avatarImg.src = data.user_avatar; // Absolute URL, use as is
        } else {
            avatarImg.src = 'public' + data.user_avatar; // Relative path, prepend 'public'
        }
    } else {
        avatarImg.src = document.getElementById('default-pfp').src;
    }
    const usernameEl = card.querySelector('.qa-username');
    usernameEl.textContent = data.username;
    card.querySelector('.qa-role').textContent = data.user_role;

    const profileUrl = `/unihelper/view/profile/${data.userID}`;
    avatarImg.parentElement.onclick = function(e) { e.stopPropagation(); window.open(profileUrl, '_blank'); };
    avatarImg.parentElement.style.cursor = 'pointer';
    usernameEl.onclick = function(e) { e.stopPropagation(); window.open(profileUrl, '_blank'); };
    usernameEl.style.cursor = 'pointer';
    // Badge hover panel — feed view opens above the name
    bindBadgeHoverPanel(usernameEl, data.userID, 'above');
    
    // Populate question content
    const styledTitle = data.questionTitle.replace(/#(\w+)/g, '<span class="hashtag">#$1</span>');
    card.querySelector('.qa-question-title').innerHTML = styledTitle;
    card.querySelector('.qa-question-text').textContent = truncateWords(data.questionText, 100);

    // Make hashtag tags clickable in the card
    bindHashtagClicks(card);
    
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
    card.querySelector('.qa-menu-btn').style.display = 'block';
    generateMenuDropdown(card, data.userID, userID, isModerator, data.questionId);
    
    // Add click handler for answer button
    const answerBtn = card.querySelector('.answer-btn');
    answerBtn.addEventListener('click', function() {
        answer(data.questionId);
    });

    const openQuestionViewFromCard = function(e) {
        if (e && e.target && e.target.closest('.hashtag')) {
            return;
        }

        if (typeof window.getSelection === 'function') {
            const selectedText = String(window.getSelection() || '').trim();
            if (selectedText.length > 0) {
                return;
            }
        }

        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        viewQuestion(data.questionId);
    };

    const titleEl = card.querySelector('.qa-question-title');
    const bodyEl = card.querySelector('.qa-question-text');

    [titleEl, bodyEl].forEach(function(el) {
        if (!el) {
            return;
        }

        el.classList.add('qa-open-question-target');
        el.setAttribute('tabindex', '0');
        el.setAttribute('role', 'button');
        el.setAttribute('aria-label', 'View full question');

        el.addEventListener('click', openQuestionViewFromCard);
        el.addEventListener('keydown', function(evt) {
            if (evt.key === 'Enter' || evt.key === ' ') {
                openQuestionViewFromCard(evt);
            }
        });
    });

    // Add click handler for view question button
    const viewQuestionBtn = card.querySelector('.view-question-btn');
    viewQuestionBtn.addEventListener('click', openQuestionViewFromCard);
    
    // Prepend the card to the cuurent filter
    let holder;
    if (currentFilter === 'default') {
        holder = document.querySelector('.qa-main');
    } else if (currentFilter === 'search') {
        holder = document.querySelector('.qa-search-results');
    } else if (currentFilter === 'tag' || currentFilter === 'user') {
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

    const url = `/unihelper/api?controller=qaController&action=getQuestions&offset=${current_question_pointer}&limit=${batch_limit}&tag=${currentTag}`;

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
                            modified: modified,
                            status: question.status
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

    // Fetch more questions if the main question list is visible and we have more questions to load
    if (scrollPosition >= pageHeight - threshold){
        if (currentFilter === 'default' && document.querySelector('.qa-main').style.display !== 'none') {
            fetchQuestions();
        }
    }
}

async function loadQuestionDetails(questionId) {
    return fetch(`/unihelper/api?controller=qaController&action=getQuestion&questionId=${questionId}`)
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
    return fetch(`/unihelper/api?controller=qaController&action=getAnswers&questionId=${questionId}`)
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
            `/unihelper/api?controller=qaController&action=Vote&question_id=${questionId}&vote_value=${voteValue}`,
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
    // Clean the tag filter bucket before loading new tag results
    document.querySelector('.qa-tag-filter').innerHTML = '';
    fetchQuestions();
}

function tagOffClick(removeTempTag) {
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

    // Remove the temporary tag button if it was one
    if (removeTempTag) {
        const tagsBar = document.querySelector('.qa-tags-bar');
        const tempBtn = tagsBar.querySelector('.tag-btn[data-temp="true"]');
        if (tempBtn) tempBtn.remove();
    }
}

//////////////////////////////////////////////////////////////////////
// Clickable hashtag handler

function hashtagClick(tagName) {
    const qaView = document.querySelector('.qa-question-view');
    const isInQuestionView = qaView && qaView.style.display === 'flex';

    if (isInQuestionView) {
        // Case 2: from question view — go back first, then apply tag
        const questionId = qaView.querySelector('#qaViewModalQuestionId').textContent;
        goBackFromQuestionView(questionId);
        // Use a small delay to let the view transition complete
        setTimeout(function() {
            activateTagInBar(tagName);
        }, 50);
    } else {
        // Case 1: from the feed
        activateTagInBar(tagName);
    }
}

function activateTagInBar(tagName) {
    const tagsBar = document.querySelector('.qa-tags-bar');
    const buttons = tagsBar.querySelectorAll('.tag-btn');

    // Check if a temp tag is already active for this exact tag name — treat as toggle-off
    const oldTemp = tagsBar.querySelector('.tag-btn[data-temp="true"]');
    if (oldTemp) {
        const oldTempName = oldTemp.textContent.trim().toLowerCase();
        if (oldTempName === tagName.toLowerCase()) {
            // Same tag clicked again — deactivate and go back to main
            tagOffClick(true);
            return;
        }
        // Different tag — remove the old temp before proceeding
        oldTemp.remove();
    }

    // Look for an existing button whose tag name matches
    let matchedBtn = null;
    buttons.forEach(function(btn) {
        if (btn.dataset.temp === 'true') return; // skip (already removed above)
        // tag button text may include count like "tagname (5)", so strip it
        const btnTagName = btn.textContent.replace(/\s*\(\d+\)$/, '').trim();
        if (btnTagName.toLowerCase() === tagName.toLowerCase()) {
            matchedBtn = btn;
        }
    });

    if (matchedBtn) {
        // Tag exists in bar — if already active, do nothing; otherwise click it
        if (!matchedBtn.classList.contains('active')) {
            matchedBtn.click();
        }
    } else {
        // Tag not in bar — add a temporary button and activate it
        // Deactivate any currently active button first
        const currentActive = tagsBar.querySelector('.tag-btn.active');
        if (currentActive) {
            currentActive.click(); // deactivate it
        }

        const tempBtn = document.createElement('button');
        tempBtn.className = 'tag-btn';
        tempBtn.textContent = tagName;
        tempBtn.setAttribute('type', 'button');
        tempBtn.setAttribute('aria-pressed', 'false');
        tempBtn.dataset.temp = 'true';

        tempBtn.addEventListener('click', function() {
            const allBtns = tagsBar.querySelectorAll('.tag-btn');
            const wasActive = this.classList.contains('active');

            allBtns.forEach(function(b) {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });

            if (!wasActive) {
                this.classList.add('active');
                this.setAttribute('aria-pressed', 'true');
                tagOnClick(tagName);
            } else {
                // Was active, now deactivating — remove temp button and go back to main
                tagOffClick(true);
            }
        });

        tagsBar.appendChild(tempBtn);

        // Immediately activate it
        tempBtn.click();
    }
}

function bindHashtagClicks(container) {
    const hashtags = container.querySelectorAll('.hashtag');
    hashtags.forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.stopPropagation();
            // Extract tag name (remove the leading #)
            const tagName = this.textContent.replace(/^#/, '').trim();
            if (tagName) {
                hashtagClick(tagName);
            }
        });
    });
}

// deleting a question
async function deleteQuestion(questionId) {
    if (!await confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
        return false;
    }

    try {
        const response = await fetch(`/unihelper/api?controller=qaController&action=deleteQuestion&questionId=${questionId}`, {
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
        const response = await fetch(`/unihelper/api?controller=qaController&action=deleteAnswer&answerId=${answerId}`, {
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

//////////////////////////////////////////////////////////////////////
// Edit question

var isEditMode = false;
var editingQuestionId = null;

async function editQuestion(questionId) {
    // Fetch the full question data using existing function
    const question = await loadQuestionDetails(questionId);
    if (!question) {
        showToast('Failed to load question data for editing.', 'error');
        return;
    }

    // Set edit mode flags
    isEditMode = true;
    editingQuestionId = questionId;

    // Change the modal title temporarily
    const modalTitle = document.querySelector('.qa-askmodal-title');
    modalTitle.textContent = 'Edit Question';

    // Change submit button text
    const submitBtn = document.querySelector('#qaQuestionForm button[type="submit"]');
    submitBtn.textContent = 'Update Question';

    // Populate the form with existing data
    const titleDiv = document.getElementById('qa-question-title');
    titleDiv.textContent = question.question;
    parseHashtags(); // Re-style any hashtags in the title

    const bodyTextarea = document.getElementById('qa-question-body');
    bodyTextarea.value = question.text;

    // Clear current selectedFiles and image previews
    selectedFiles = [];
    document.querySelectorAll('.qa-image-preview').forEach(preview => preview.remove());

    // Load existing images as File objects
    const imageTray = document.querySelector('.qa-image-tray');
    const imageAddBox = document.querySelector('.qa-image-add-box');

    if (question.img_path) {
        const imagePaths = question.img_path.split(',');
        for (let i = 0; i < imagePaths.length; i++) {
            const path = imagePaths[i].trim();
            if (!path) continue;
            try {
                const file = await urlToFile(path, `image_${i}`);
                selectedFiles.push(file);
                addImagePreview(file, imageTray, imageAddBox);
            } catch (err) {
                console.error('Failed to load image for editing:', path, err);
            }
        }
    }

    // Show the ask modal
    const askmodal = document.querySelector('.qa-askmodal');
    askmodal.style.display = 'flex';
}

// Helper: convert an image URL/path to a File object
async function urlToFile(path, fallbackName) {
    // Build full URL from the relative path
    const url = window.location.origin + '/unihelper/' + path;
    const response = await fetch(url);
    const blob = await response.blob();

    // Extract filename and extension from the path
    const parts = path.split('/');
    const fileName = parts[parts.length - 1] || fallbackName;
    const ext = fileName.split('.').pop().toLowerCase();

    const mimeMap = {
        'jpg': 'image/jpeg',
        'jpeg': 'image/jpeg',
        'png': 'image/png',
        'gif': 'image/gif',
        'webp': 'image/webp'
    };
    const mime = mimeMap[ext] || blob.type || 'image/jpeg';

    return new File([blob], fileName, { type: mime });
}

function resetEditMode() {
    isEditMode = false;
    editingQuestionId = null;

    // Restore original modal title and submit button text
    const modalTitle = document.querySelector('.qa-askmodal-title');
    modalTitle.textContent = 'Ask a Question';

    const submitBtn = document.querySelector('#qaQuestionForm button[type="submit"]');
    submitBtn.textContent = 'Post Question';
}

//////////////////////////////////////////////////////////////////////
// Searching functions

let activeSearchRequestId = 0;

function getSearchElements() {
    const resultsBucket = document.querySelector('.qa-search-results');
    if (!resultsBucket) return null;

    return {
        resultsBucket,
        mainBucket: document.querySelector('.qa-main'),
        tagBucket: document.querySelector('.qa-tag-filter'),
        tagsBar: document.querySelector('.qa-tags-bar'),
        stickyBtn: document.querySelector('.qa-sticky-btn'),
        searchInput: document.getElementById('qa-search-input'),
        clearBtn: document.querySelector('.search-clear-btn'),
        loadingTemplate: resultsBucket.querySelector('.template-search-loading'),
        emptyTemplate: resultsBucket.querySelector('.template-search-empty'),
        questionTemplate: resultsBucket.querySelector('.template-question-search'),
        answerTemplate: resultsBucket.querySelector('.template-answer-search')
    };
}

function getOrCreateSearchSummaryElement(resultsBucket) {
    let summary = resultsBucket.querySelector('.qa-search-summary');
    if (!summary) {
        summary = document.createElement('p');
        summary.className = 'qa-search-summary';
        summary.style.margin = '0 0 0.25rem 0';
        summary.style.fontSize = '0.9rem';
        summary.style.color = 'var(--muted-foreground)';
        resultsBucket.insertBefore(summary, resultsBucket.firstChild);
    }
    return summary;
}

function clearRenderedSearchCards(resultsBucket) {
    resultsBucket.querySelectorAll('.qa-search-result-item').forEach(function(card) {
        card.remove();
    });
}

function setSearchLoadingState(loadingElement, isVisible) {
    if (!loadingElement) return;
    loadingElement.style.display = isVisible ? 'flex' : 'none';
}

function setSearchEmptyState(emptyElement, isVisible, query) {
    if (!emptyElement) return;

    const textElement = emptyElement.querySelector('.qa-search-no-results-text');
    if (textElement) {
        textElement.textContent = query
            ? `No results found for "${query}". Try different keywords or check your spelling.`
            : "We couldn't find any questions or answers matching your search. Try different keywords or check your spelling.";
    }

    emptyElement.style.display = isVisible ? 'flex' : 'none';
}

function parseDeepLinkRef(deeplinkRef, fallbackQuestionId, fallbackAnswerId) {
    const parts = String(deeplinkRef || '')
        .split(',')
        .map(function(value) { return value.trim(); })
        .filter(Boolean);

    const questionId = parts[0] || fallbackQuestionId || '';
    const answerId = parts[1] || fallbackAnswerId || '';

    if (!questionId) {
        return null;
    }

    let link = `/unihelper/qa-forum?question=${encodeURIComponent(questionId)}`;
    if (answerId) {
        link += `&answer=${encodeURIComponent(answerId)}`;
    }

    return link;
}

function createSearchResultCard(result, questionTemplate, answerTemplate) {
    const type = String(result?.type || '').toLowerCase();
    const isAnswer = type === 'answer';
    const template = isAnswer ? answerTemplate : questionTemplate;

    if (!template) {
        return null;
    }

    const link = parseDeepLinkRef(result.deeplink_ref, result.questionId, result.answerId);
    if (!link) {
        return null;
    }

    const card = template.cloneNode(true);
    card.classList.remove('template-question-search', 'template-answer-search');
    card.classList.add('qa-search-result-item');
    card.style.display = 'flex';

    const typeEl = card.querySelector('.qa-search-card-type');
    if (typeEl) {
        typeEl.textContent = isAnswer ? '💬 Answer' : '❓ Question';
    }

    const timeEl = card.querySelector('.qa-search-card-time');
    if (timeEl) {
        const rawTimestamp = result.time || result.timestamp || result.added_time || result.last_modified || '';
        let relativeTime = '';

        if (rawTimestamp) {
            const parsedTime = new Date(rawTimestamp);
            if (!Number.isNaN(parsedTime.getTime())) {
                relativeTime = getRelativeTime(parsedTime);
            }
        }

        timeEl.textContent = relativeTime;
        timeEl.style.display = relativeTime ? 'inline' : 'none';
    }

    if (isAnswer) {
        const parentEl = card.querySelector('.qa-search-answer-parent');
        if (parentEl) {
            parentEl.textContent = `Re: ${result.questionTitle || ''}`;
        }
    } else {
        const titleEl = card.querySelector('.qa-search-question-title');
        if (titleEl) {
            titleEl.textContent = result.questionTitle || '';
        }
    }

    const bodyEl = card.querySelector('.qa-search-body');
    if (bodyEl) {
        bodyEl.textContent = isAnswer ? (result.answerText || '') : (result.questionText || '');
    }

    card.setAttribute('role', 'link');
    card.setAttribute('tabindex', '0');
    card.addEventListener('click', function() {
        window.location.href = link;
    });
    card.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            window.location.href = link;
        }
    });

    return card;
}

function setSearchSummary(summaryEl, query, count) {
    if (!summaryEl) return;
    if (count === 0) {
        summaryEl.textContent = `No results found for "${query}"`;
        return;
    }
    const label = count === 1 ? 'result' : 'results';
    summaryEl.textContent = `Search results for "${query}" (${count} ${label})`;
}

async function fetchSearchResults(query) {
    const els = getSearchElements();
    if (!els || !query) {
        return;
    }

    const requestId = ++activeSearchRequestId;
    const summaryEl = getOrCreateSearchSummaryElement(els.resultsBucket);

    clearRenderedSearchCards(els.resultsBucket);
    setSearchEmptyState(els.emptyTemplate, false);
    setSearchLoadingState(els.loadingTemplate, true);
    summaryEl.textContent = `Searching for "${query}"...`;

    try {
        const response = await fetch(`/unihelper/api?controller=searchController&action=search&query=${encodeURIComponent(query)}&type=qa&index=${encodeURIComponent(searchIndex)}`);
        let payload;

        try {
            payload = await response.json();
        } catch (_) {
            throw new Error('Invalid response format from search API.');
        }

        if (requestId !== activeSearchRequestId) {
            return;
        }

        if (!response.ok) {
            throw new Error(payload?.message || payload?.error || 'Search request failed.');
        }

        if (payload?.success === false || payload?.error) {
            throw new Error(payload?.message || payload?.error || 'Unable to complete search.');
        }

        const results = Array.isArray(payload?.data)
            ? payload.data
            : (Array.isArray(payload) ? payload : []);

        searchIndex += 1;
        setSearchSummary(summaryEl, query, results.length);

        if (results.length === 0) {
            setSearchEmptyState(els.emptyTemplate, true, query);
            return;
        }

        const fragment = document.createDocumentFragment();
        results.forEach(function(result) {
            const card = createSearchResultCard(result, els.questionTemplate, els.answerTemplate);
            if (card) {
                fragment.appendChild(card);
            }
        });

        els.resultsBucket.appendChild(fragment);
        setSearchEmptyState(els.emptyTemplate, els.resultsBucket.querySelectorAll('.qa-search-result-item').length === 0, query);
    } catch (error) {
        if (requestId !== activeSearchRequestId) {
            return;
        }

        const message = error?.message || 'An error occurred while searching. Please try again.';
        showToast(message, 'error');
        summaryEl.textContent = `Search failed for "${query}". Please try again.`;
        setSearchLoadingState(els.loadingTemplate, false);
    } finally {
        if (requestId === activeSearchRequestId) {
            setSearchLoadingState(els.loadingTemplate, false);
        }
    }
}

function clearSearch() {
    activeSearchRequestId += 1;

    const els = getSearchElements();
    if (!els) {
        return;
    }

    currentFilter = 'default';
    currentTag = 'default';

    if (els.mainBucket) els.mainBucket.style.display = 'block';
    if (els.tagBucket) els.tagBucket.style.display = 'none';
    if (els.resultsBucket) els.resultsBucket.style.display = 'none';
    if (els.tagsBar) els.tagsBar.style.display = 'flex';
    if (els.stickyBtn) els.stickyBtn.style.display = 'flex';

    clearRenderedSearchCards(els.resultsBucket);
    setSearchLoadingState(els.loadingTemplate, false);
    setSearchEmptyState(els.emptyTemplate, false);

    const summaryEl = getOrCreateSearchSummaryElement(els.resultsBucket);
    summaryEl.textContent = '';

    if (els.searchInput) {
        els.searchInput.value = '';
    }
    if (els.clearBtn) {
        els.clearBtn.style.display = 'none';
    }

    const tagsBar = document.querySelector('.qa-tags-bar');
    if (tagsBar) {
        tagsBar.querySelectorAll('.tag-btn').forEach(function(btn) {
            btn.classList.remove('active');
            btn.setAttribute('aria-pressed', 'false');
        });
        const tempBtn = tagsBar.querySelector('.tag-btn[data-temp="true"]');
        if (tempBtn) {
            tempBtn.remove();
        }
    }
}

function search() {
    const els = getSearchElements();
    if (!els || !els.searchInput) {
        return;
    }

    const query = els.searchInput.value.trim();
    if (!query) {
        clearSearch();
        return;
    }

    currentFilter = 'search';

    if (els.mainBucket) els.mainBucket.style.display = 'none';
    if (els.tagBucket) els.tagBucket.style.display = 'none';
    if (els.resultsBucket) els.resultsBucket.style.display = 'flex';
    if (els.tagsBar) els.tagsBar.style.display = 'none';
    if (els.stickyBtn) els.stickyBtn.style.display = 'none';

    if (els.clearBtn) {
        els.clearBtn.style.display = 'inline-flex';
    }

    fetchSearchResults(query);
}

//////////////////////////////////////////////////////////////////////
// User filtering via deep link

function activateUserTagInBar(username, questionsData) {
    const tagsBar = document.querySelector('.qa-tags-bar');
    
    // Deactivate any currently active button first
    const currentActive = tagsBar.querySelector('.tag-btn.active');
    if (currentActive) {
        currentActive.click(); // deactivate it
    }

    // Remove old temp tag if exists
    const oldTemp = tagsBar.querySelector('.tag-btn[data-temp="true"]');
    if (oldTemp) {
        oldTemp.remove();
    }

    const tempBtn = document.createElement('button');
    tempBtn.className = 'tag-btn active'; // Make it active instantly
    tempBtn.textContent = username;
    tempBtn.style.fontWeight = 'bold'; // The username tag font is bold
    tempBtn.setAttribute('type', 'button');
    tempBtn.setAttribute('aria-pressed', 'true');
    tempBtn.dataset.temp = 'true';
    
    tempBtn.addEventListener('click', function() {
        // Was active, now deactivating — remove temp button and go back to main
        tagOffClick(true);
    });

    tagsBar.appendChild(tempBtn);

    // Call the custom user tag onclick handler
    userTagOnClick(username, questionsData);
}

function userTagOnClick(username, questionsData) {
    // Update current filter
    currentFilter = 'user'; 
    current_question_pointer_temp = current_question_pointer;
    current_question_pointer = 0;
    questions_ids_temp = [...questions_ids];
    questions_ids = [];
    hasMoreQuestions_temp = hasMoreQuestions;
    hasMoreQuestions = false; 

    // hide the main question list and show the tag filter container
    document.querySelector('.qa-main').style.display = 'none';
    document.querySelector('.qa-tag-filter').style.display = 'block';
    document.querySelector('.qa-sticky-btn').style.display = 'none';
    
    // Clean the tag filter bucket
    const holder = document.querySelector('.qa-tag-filter');
    holder.innerHTML = '';

    if (questionsData && questionsData.length > 0) {
        questionsData.forEach(function(question) {
            if (questions_ids.includes(question.q_id)) return;
            const addedTime = new Date(question.added_time);
            const lastModified = new Date(question.last_modified);
            let timestamp;
            let modified;
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
                modified: modified,
                status: question.status
            };
            makeQuestionCard(mappedData, -1);
            questions_ids.push(question.q_id);
        });
    } else {
        const emptyMsg = document.createElement('div');
        emptyMsg.textContent = 'This user has no questions.';
        emptyMsg.style.textAlign = 'center';
        emptyMsg.style.padding = '2rem';
        emptyMsg.style.color = 'var(--muted-foreground)';
        holder.appendChild(emptyMsg);
    }
}