(function () {
    'use strict';

    const API_BASE = '/unihelper/api';
    const shell = document.querySelector('.feed-shell');
    const composer = document.getElementById('feedComposer');
    const openComposerBtn = document.getElementById('feedOpenComposerBtn');
    const closeComposerBtn = document.getElementById('feedCloseComposerBtn');
    const cancelComposerBtn = document.getElementById('feedCancelComposerBtn');
    const form = document.getElementById('feedComposerForm');
    const publishBtn = document.getElementById('feedPublishBtn');
    const audienceMode = document.getElementById('feedAudienceMode');
    const roleChoices = document.getElementById('feedRoleChoices');
    const list = document.getElementById('feedList');
    const loadMoreBtn = document.getElementById('feedLoadMore');
    const searchForm = document.getElementById('feedSearchForm');
    const searchInput = document.getElementById('feedSearchInput');
    const searchClearBtn = document.getElementById('feedSearchClearBtn');
    const imageInput = document.getElementById('feedImage');
    const imagePreviewWrap = document.getElementById('feedImagePreviewWrap');
    const imagePreview = document.getElementById('feedImagePreview');
    const imageRemoveBtn = document.getElementById('feedImageRemoveBtn');
    const feedType = document.getElementById('feedType');
    const titleInput = document.getElementById('feedTitle');
    const composerTitle = document.getElementById('feedComposerTitle');
    const composerSubtitle = composer ? composer.querySelector('.feed-subtitle') : null;

    if (!shell || !composer || !openComposerBtn || !form || !list || !loadMoreBtn || !audienceMode) {
        return;
    }

    // Keep the composer overlay in the global page layer so it always sits on top.
    if (composer.parentElement && composer.parentElement !== document.body) {
        composer.parentElement.removeChild(composer);
        document.body.appendChild(composer);
    }

    const state = {
        page: 1,
        limit: 8,
        searchPage: 0,
        searchLimit: 20,
        loading: false,
        hasMore: true,
        searchHasMore: true,
        activeSearchQuery: '',
        previousBodyOverflow: '',
        editMode: false,
        editingPostId: 0,
        initialEditImagePath: '',
        removeExistingImage: false,
    };

    const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const MAX_IMAGE_SIZE_BYTES = 5 * 1024 * 1024;
    const defaultComposerTitle = composerTitle ? composerTitle.textContent : 'Community Feed';
    const defaultComposerSubtitle = composerSubtitle ? composerSubtitle.textContent : '';
    const defaultPublishLabel = publishBtn ? publishBtn.textContent : 'Publish to Feed';

    if (feedType && shell.dataset.defaultPostType) {
        feedType.value = shell.dataset.defaultPostType;
    }

    function setComposerModeCreate() {
        state.editMode = false;
        state.editingPostId = 0;
        state.initialEditImagePath = '';
        state.removeExistingImage = false;

        if (composerTitle) {
            composerTitle.textContent = defaultComposerTitle;
        }
        if (composerSubtitle) {
            composerSubtitle.textContent = defaultComposerSubtitle;
        }
        if (publishBtn) {
            publishBtn.textContent = defaultPublishLabel;
        }
    }

    function setComposerModeEdit(item) {
        const row = item || {};
        const roles = row.meta && Array.isArray(row.meta.roles) ? row.meta.roles : [];

        state.editMode = true;
        state.editingPostId = Number(row.source_id || 0);
        state.initialEditImagePath = String(row.image_path || '').trim();
        state.removeExistingImage = false;

        if (composerTitle) {
            composerTitle.textContent = 'Edit Post';
        }
        if (composerSubtitle) {
            composerSubtitle.textContent = 'Update your announcement details and audience.';
        }
        if (publishBtn) {
            publishBtn.textContent = 'Save Changes';
        }

        if (titleInput) {
            titleInput.value = String(row.title || '');
        }

        const bodyInput = document.getElementById('feedBody');
        if (bodyInput) {
            bodyInput.value = String(row.body || '');
        }

        if (feedType) {
            feedType.value = String(row.post_type || 'announcement');
        }

        if (audienceMode) {
            audienceMode.value = roles.length ? 'selected_roles' : 'all_roles';
        }

        if (roleChoices) {
            const selectedRoles = Object.create(null);
            roles.forEach(function (role) {
                selectedRoles[String(role)] = true;
            });

            roleChoices.querySelectorAll('input[name="audience_roles[]"]').forEach(function (input) {
                input.checked = !!selectedRoles[input.value];
            });
        }

        toggleRolePicker();

        if (state.initialEditImagePath !== '') {
            showImagePreviewFromPath(state.initialEditImagePath);
        } else {
            clearSelectedImage();
        }
    }

    function resetComposerForm() {
        form.reset();
        if (feedType && shell.dataset.defaultPostType) {
            feedType.value = shell.dataset.defaultPostType;
        }
        toggleRolePicker();
        clearSelectedImage();
        setComposerModeCreate();
    }

    function openComposer() {
        composer.hidden = false;
        composer.classList.remove('is-hidden');
        openComposerBtn.classList.add('expanded');
        openComposerBtn.setAttribute('aria-expanded', 'true');
        state.previousBodyOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        if (titleInput) {
            setTimeout(function () {
                titleInput.focus();
            }, 60);
        }
    }

    function closeComposer(options) {
        const opts = options || {};

        if (opts.resetForm) {
            resetComposerForm();
        }

        closeAllFeedMenus();

        composer.classList.add('is-hidden');
        composer.hidden = true;
        openComposerBtn.classList.remove('expanded');
        openComposerBtn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = state.previousBodyOverflow || '';
        state.previousBodyOverflow = '';
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function roleLabel(role) {
        const labels = {
            'role-applicant': 'Applicant',
            'role-undergrad': 'Undergraduate',
            'role-profile': 'Profile',
            'role-admin': 'Admin'
        };
        return labels[role] || 'User';
    }

    function typeLabel(type) {
        const labels = {
            'announcement': 'Announcement',
            'event': 'Event',
            'general': 'General',
            'session': 'Session'
        };
        return labels[type] || 'Post';
    }

    function formatDateTime(value) {
        if (!value) return 'just now';

        const dt = new Date(value.replace(' ', 'T'));
        if (Number.isNaN(dt.getTime())) {
            return escapeHtml(value);
        }

        return dt.toLocaleString();
    }

    function sessionMetaHtml(item) {
        const meta = item.meta || {};
        if (item.source !== 'session') {
            return '';
        }

        const parts = [];
        if (meta.subject) parts.push('Subject: ' + escapeHtml(meta.subject));
        if (meta.date && meta.time) parts.push('Starts: ' + escapeHtml(meta.date + ' ' + meta.time));
        if (meta.duration) parts.push('Duration: ' + escapeHtml(meta.duration) + 'h');

        const line = parts.length ? '<div>' + parts.join(' | ') + '</div>' : '';
        const sessionLink = meta.source_link
            ? '<a class="feed-session-link" href="' + escapeHtml(meta.source_link) + '">Open in Peer Learning</a>'
            : '';

        return line + sessionLink;
    }

    function postImageHtml(item) {
        const imagePath = String((item && item.image_path) || '').trim();
        if (!imagePath) {
            return '';
        }

        return '<div class="feed-item-media"><img class="feed-item-image" src="' + escapeHtml(imagePath) + '" alt="Post image" loading="lazy"></div>';
    }

    function authorMetaHtml(item) {
        const authorName = escapeHtml(item.author_name || 'Unknown');
        const role = escapeHtml(item.author_role_label || roleLabel(shell.dataset.userRole));
        const authorId = Number(item.author_id || 0);

        if (authorId > 0) {
            return 'By <a class="feed-author-link" href="/unihelper/view/profile/' + authorId + '">' + authorName + '</a> (' + role + ')';
        }

        return 'By ' + authorName + ' (' + role + ')';
    }

    function postActionsHtml(item) {
        if (!item || item.source !== 'post' || !item.can_manage) {
            return '';
        }

        return [
            '<div class="feed-menu-wrap">',
                '<button type="button" class="feed-menu-btn" aria-label="Post actions" aria-haspopup="true" aria-expanded="false">',
                    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">',
                        '<circle cx="12" cy="5" r="1"></circle>',
                        '<circle cx="12" cy="12" r="1"></circle>',
                        '<circle cx="12" cy="19" r="1"></circle>',
                    '</svg>',
                '</button>',
                '<div class="feed-menu-dropdown" hidden>',
                    '<button type="button" class="feed-menu-item feed-menu-edit">Edit</button>',
                    '<button type="button" class="feed-menu-item feed-menu-delete">Delete</button>',
                '</div>',
            '</div>'
        ].join('');
    }

    function likeActionHtml(item) {
        if (!item || !item.source_id || (item.source !== 'post' && item.source !== 'session')) {
            return '';
        }

        const likedByViewer = !!item.liked_by_viewer;
        const likeCount = Math.max(0, Number(item.like_count || 0));

        return [
            '<button type="button" class="feed-like-btn' + (likedByViewer ? ' is-liked' : '') + '"',
            ' aria-pressed="' + (likedByViewer ? 'true' : 'false') + '"',
            ' aria-label="' + (likedByViewer ? 'Unlike' : 'Like') + '">',
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">',
                    '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>',
                '</svg>',
                '<span class="feed-like-label">Like</span>',
                '<span class="feed-like-count">' + likeCount + '</span>',
            '</button>'
        ].join('');
    }

    function setLikeButtonState(likeBtn, likedByViewer, likeCount) {
        if (!likeBtn) {
            return;
        }

        const safeCount = Math.max(0, Number(likeCount || 0));
        likeBtn.classList.toggle('is-liked', !!likedByViewer);
        likeBtn.setAttribute('aria-pressed', likedByViewer ? 'true' : 'false');
        likeBtn.setAttribute('aria-label', likedByViewer ? 'Unlike' : 'Like');

        const countNode = likeBtn.querySelector('.feed-like-count');
        if (countNode) {
            countNode.textContent = String(safeCount);
        }
    }

    async function submitLike(item, likeBtn) {
        if (!item || !likeBtn || likeBtn.disabled) {
            return;
        }

        const prevLiked = likeBtn.classList.contains('is-liked');
        const prevCount = Math.max(0, Number(item.like_count || 0));

        const optimisticLiked = !prevLiked;
        const optimisticCount = Math.max(0, prevCount + (optimisticLiked ? 1 : -1));

        setLikeButtonState(likeBtn, optimisticLiked, optimisticCount);
        likeBtn.disabled = true;

        try {
            const fd = new FormData();
            fd.append('source', String(item.source || ''));
            fd.append('source_id', String(item.source_id || 0));

            const response = await fetch(API_BASE + '?controller=FeedController&action=toggleLike', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd,
            });

            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error((payload && payload.message) || 'Failed to update like.');
            }

            const data = payload.data || {};
            const confirmedLiked = !!data.liked_by_viewer;
            const confirmedCount = Math.max(0, Number(data.like_count || 0));

            item.liked_by_viewer = confirmedLiked;
            item.like_count = confirmedCount;
            setLikeButtonState(likeBtn, confirmedLiked, confirmedCount);
        } catch (error) {
            setLikeButtonState(likeBtn, prevLiked, prevCount);
            item.liked_by_viewer = prevLiked;
            item.like_count = prevCount;
            showToast(error.message || 'Failed to update like.', 'error');
        } finally {
            likeBtn.disabled = false;
        }
    }

    function closeAllFeedMenus() {
        document.querySelectorAll('.feed-menu-dropdown').forEach(function (menu) {
            menu.hidden = true;
        });

        document.querySelectorAll('.feed-menu-btn').forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'false');
        });
    }

    async function confirmAction(message) {
        if (typeof window.confirm !== 'function') {
            return true;
        }

        return !!(await Promise.resolve(window.confirm(message)));
    }

    async function handleDeletePost(item) {
        if (!item || item.source !== 'post' || !item.source_id) {
            return;
        }

        const confirmed = await confirmAction('Are you sure you want to delete this post? This action cannot be undone.');
        if (!confirmed) {
            return;
        }

        try {
            const fd = new FormData();
            fd.append('post_id', String(item.source_id));

            const response = await fetch(API_BASE + '?controller=FeedController&action=deletePost', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd,
            });

            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error((payload && payload.message) || 'Failed to delete post.');
            }

            showToast(payload.message || 'Post deleted successfully.', 'success');
            if (isSearchMode()) {
                searchFeed(true);
            } else {
                fetchFeed(true);
            }
        } catch (error) {
            showToast(error.message || 'Failed to delete post.', 'error');
        }
    }

    function bindCardActions(card, item) {
        if (!item || item.source !== 'post' || !item.can_manage) {
            return;
        }

        const menuBtn = card.querySelector('.feed-menu-btn');
        const menuDropdown = card.querySelector('.feed-menu-dropdown');
        const editBtn = card.querySelector('.feed-menu-edit');
        const deleteBtn = card.querySelector('.feed-menu-delete');

        if (!menuBtn || !menuDropdown) {
            return;
        }

        menuBtn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const shouldOpen = !!menuDropdown.hidden;
            closeAllFeedMenus();
            menuDropdown.hidden = !shouldOpen;
            menuBtn.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        });

        if (editBtn) {
            editBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                closeAllFeedMenus();
                setComposerModeEdit(item);
                openComposer();
            });
        }

        if (deleteBtn) {
            deleteBtn.addEventListener('click', async function (event) {
                event.preventDefault();
                event.stopPropagation();

                closeAllFeedMenus();
                await handleDeletePost(item);
            });
        }
    }

    function bindCardLikeAction(card, item) {
        const likeBtn = card.querySelector('.feed-like-btn');
        if (!likeBtn) {
            return;
        }

        likeBtn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            submitLike(item, likeBtn);
        });
    }

    function renderItem(item) {
        const card = document.createElement('article');
        card.className = 'feed-card';

        card.innerHTML = [
            '<div class="feed-card-head">',
                '<div class="feed-title-row">',
                    '<h3 class="feed-item-title">' + escapeHtml(item.title) + '</h3>',
                    '<p class="feed-item-meta">' + authorMetaHtml(item) + '</p>',
                '</div>',
                '<div class="feed-card-head-right">',
                    '<span class="feed-type-badge">' + escapeHtml(typeLabel(item.post_type)) + '</span>',
                    postActionsHtml(item),
                '</div>',
            '</div>',
            postImageHtml(item),
            '<p class="feed-item-body">' + escapeHtml(item.body || '') + '</p>',
            '<div class="feed-item-foot">',
                '<div class="feed-item-foot-left">' + likeActionHtml(item) + '</div>',
                '<div class="feed-item-foot-right">',
                    '<span>Audience: ' + escapeHtml(item.audience_label || 'All roles') + '</span>',
                    '<span>' + escapeHtml(formatDateTime(item.created_at)) + '</span>',
                '</div>',
            '</div>',
            sessionMetaHtml(item)
        ].join('');

        bindCardActions(card, item);
        bindCardLikeAction(card, item);

        return card;
    }

    function showToast(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type || 'success');
        }
    }

    function getSearchQuery() {
        return searchInput ? String(searchInput.value || '').trim() : '';
    }

    function isSearchMode() {
        return state.activeSearchQuery !== '';
    }

    function updateSearchClearVisibility() {
        if (searchClearBtn) {
            searchClearBtn.hidden = !(getSearchQuery() !== '' || isSearchMode());
        }
    }

    function showEmpty(message) {
        list.innerHTML = '<div class="feed-empty">' + escapeHtml(message) + '</div>';
    }

    function clearSelectedImage() {
        if (imageInput) {
            imageInput.value = '';
        }

        if (imagePreview) {
            imagePreview.src = '';
        }

        if (imagePreviewWrap) {
            imagePreviewWrap.classList.add('is-hidden');
            imagePreviewWrap.hidden = true;
        }
    }

    function showImagePreviewFromPath(path) {
        const imagePath = String(path || '').trim();
        if (!imagePath || !imagePreview || !imagePreviewWrap) {
            clearSelectedImage();
            return;
        }

        if (imageInput) {
            imageInput.value = '';
        }

        imagePreview.src = imagePath;
        imagePreviewWrap.hidden = false;
        imagePreviewWrap.classList.remove('is-hidden');
    }

    function previewSelectedImage(file) {
        if (!file || !imagePreview || !imagePreviewWrap) {
            return;
        }

        const reader = new FileReader();
        reader.onload = function (event) {
            const result = event && event.target ? event.target.result : null;
            if (!result) {
                return;
            }

            imagePreview.src = String(result);
            imagePreviewWrap.hidden = false;
            imagePreviewWrap.classList.remove('is-hidden');
        };
        reader.readAsDataURL(file);
    }

    function validateSelectedImage(file) {
        if (!file) {
            return null;
        }

        if (ALLOWED_IMAGE_TYPES.indexOf(file.type) === -1) {
            return 'Only JPG, PNG, GIF, and WEBP images are allowed.';
        }

        if (file.size > MAX_IMAGE_SIZE_BYTES) {
            return 'Image must be 5MB or smaller.';
        }

        return null;
    }

    function toggleRolePicker() {
        const shouldShow = audienceMode && audienceMode.value === 'selected_roles';
        if (roleChoices) {
            roleChoices.classList.toggle('show', !!shouldShow);
        }
    }

    async function fetchFeed(reset) {
        if (state.loading) return;

        if (reset) {
            state.page = 1;
            state.hasMore = true;
            list.innerHTML = '';
        }

        if (!state.hasMore) return;

        state.loading = true;
        loadMoreBtn.disabled = true;

        try {
            const url = API_BASE + '?controller=FeedController&action=getFeed&page=' + state.page + '&limit=' + state.limit;
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
            });

            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error((payload && payload.message) || 'Failed to load feed.');
            }

            const rows = Array.isArray(payload.data) ? payload.data : [];
            if (reset && rows.length === 0) {
                showEmpty('No posts yet. Publish the first update for your audience.');
            } else {
                rows.forEach(function (row) {
                    list.appendChild(renderItem(row));
                });
            }

            state.hasMore = !!payload.has_more;
            if (state.hasMore) {
                state.page += 1;
            }

            loadMoreBtn.style.display = state.hasMore ? 'inline-flex' : 'none';
        } catch (error) {
            if (!list.children.length) {
                showEmpty('Unable to load feed right now.');
            }
            showToast(error.message || 'Feed loading failed.', 'error');
        } finally {
            state.loading = false;
            loadMoreBtn.disabled = false;
        }
    }

    function normalizeSearchItem(item) {
        const row = item || {};
        const authorName = row.author_name
            || [row.first_name || '', row.last_name || ''].join(' ').trim()
            || 'Unknown';

        return {
            source: row.source || 'post',
            source_id: Number(row.source_id || row.id || 0),
            post_type: row.post_type || 'announcement',
            title: row.title || '',
            body: row.body || '',
            image_path: row.image_path || '',
            created_at: row.created_at || '',
            audience_label: row.audience_label || 'All Roles',
            author_name: authorName,
            author_id: Number(row.author_id || row.user_id || 0),
            author_role_label: row.author_role_label || roleLabel(row.author_role || shell.dataset.userRole),
            can_manage: !!row.can_manage,
            like_count: Math.max(0, Number(row.like_count || 0)),
            liked_by_viewer: !!Number(row.liked_by_viewer || 0),
            meta: row.meta || {},
        };
    }

    async function searchFeed(reset) {
        if (state.loading) return;

        const query = state.activeSearchQuery;
        if (!query) {
            fetchFeed(true);
            return;
        }

        if (reset) {
            state.searchPage = 0;
            state.searchHasMore = true;
            list.innerHTML = '';
        }

        if (!state.searchHasMore) return;

        state.loading = true;
        loadMoreBtn.disabled = true;

        try {
            const url = API_BASE
                + '?controller=searchController&action=search&type=feed&index=' + state.searchPage
                + '&query=' + encodeURIComponent(query);

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
            });

            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error((payload && payload.message) || 'Failed to search feed.');
            }

            const rows = Array.isArray(payload.data) ? payload.data : [];
            if (reset && rows.length === 0) {
                showEmpty('No announcements matched your search.');
            } else {
                rows.forEach(function (row) {
                    list.appendChild(renderItem(normalizeSearchItem(row)));
                });
            }

            state.searchHasMore = rows.length >= state.searchLimit;
            if (state.searchHasMore) {
                state.searchPage += 1;
            }

            loadMoreBtn.style.display = state.searchHasMore ? 'inline-flex' : 'none';
        } catch (error) {
            if (!list.children.length) {
                showEmpty('Unable to search announcements right now.');
            }
            showToast(error.message || 'Feed search failed.', 'error');
        } finally {
            state.loading = false;
            loadMoreBtn.disabled = false;
        }
    }

    async function publishPost(event) {
        event.preventDefault();

        const fd = new FormData(form);
        const selectedImage = imageInput && imageInput.files ? imageInput.files[0] : null;
        const isEditing = state.editMode && state.editingPostId > 0;
        if ((fd.get('audience_mode') || '') === 'selected_roles' && fd.getAll('audience_roles[]').length === 0) {
            showToast('Select at least one audience role.', 'error');
            return;
        }

        const imageValidationError = validateSelectedImage(selectedImage || null);
        if (imageValidationError) {
            showToast(imageValidationError, 'error');
            return;
        }

        publishBtn.disabled = true;

        try {
            let action = 'createPost';
            if (isEditing) {
                action = 'updatePost';
                fd.append('post_id', String(state.editingPostId));
                if (state.removeExistingImage && !selectedImage) {
                    fd.append('remove_image', '1');
                }
            }

            const response = await fetch(API_BASE + '?controller=FeedController&action=' + action, {
                method: 'POST',
                credentials: 'same-origin',
                body: fd,
            });

            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error((payload && payload.message) || 'Failed to save post.');
            }

            closeComposer({ resetForm: true });
            showToast(isEditing ? 'Post updated successfully.' : 'Post published to feed.', 'success');
            if (isSearchMode()) {
                searchFeed(true);
            } else {
                fetchFeed(true);
            }
        } catch (error) {
            showToast(error.message || 'Failed to save post.', 'error');
        } finally {
            publishBtn.disabled = false;
        }
    }

    openComposerBtn.addEventListener('click', function () {
        if (composer.hidden) {
            if (state.editMode) {
                resetComposerForm();
            }
            openComposer();
        } else {
            closeComposer({ resetForm: false });
        }
    });

    if (closeComposerBtn) {
        closeComposerBtn.addEventListener('click', function () {
            closeComposer({ resetForm: false });
        });
    }

    if (cancelComposerBtn) {
        cancelComposerBtn.addEventListener('click', function () {
            closeComposer({ resetForm: true });
        });
    }

    composer.addEventListener('click', function (event) {
        if (event.target === composer) {
            closeComposer({ resetForm: false });
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllFeedMenus();
        }

        if (event.key === 'Escape' && !composer.hidden) {
            closeComposer({ resetForm: false });
        }
    });

    document.addEventListener('click', function () {
        closeAllFeedMenus();
    });

    if (searchForm) {
        searchForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const query = getSearchQuery();

            if (query !== '') {
                state.activeSearchQuery = query;
                searchFeed(true);
            } else {
                state.activeSearchQuery = '';
                fetchFeed(true);
            }

            updateSearchClearVisibility();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            updateSearchClearVisibility();
            if (getSearchQuery() === '' && isSearchMode()) {
                state.activeSearchQuery = '';
                fetchFeed(true);
            }
        });
    }

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
            }
            state.activeSearchQuery = '';
            updateSearchClearVisibility();
            fetchFeed(true);
        });
    }

    if (imageInput) {
        imageInput.addEventListener('change', function () {
            const file = imageInput.files && imageInput.files.length ? imageInput.files[0] : null;
            if (!file) {
                if (state.editMode && state.initialEditImagePath && !state.removeExistingImage) {
                    showImagePreviewFromPath(state.initialEditImagePath);
                } else {
                    clearSelectedImage();
                }
                return;
            }

            const imageValidationError = validateSelectedImage(file);
            if (imageValidationError) {
                showToast(imageValidationError, 'error');
                if (state.editMode && state.initialEditImagePath && !state.removeExistingImage) {
                    showImagePreviewFromPath(state.initialEditImagePath);
                } else {
                    clearSelectedImage();
                }
                return;
            }

            state.removeExistingImage = false;
            previewSelectedImage(file);
        });
    }

    if (imageRemoveBtn) {
        imageRemoveBtn.addEventListener('click', function () {
            clearSelectedImage();
            if (state.editMode && state.initialEditImagePath) {
                state.removeExistingImage = true;
            }
        });
    }

    audienceMode.addEventListener('change', toggleRolePicker);
    form.addEventListener('submit', publishPost);
    loadMoreBtn.addEventListener('click', function () {
        if (isSearchMode()) {
            searchFeed(false);
        } else {
            fetchFeed(false);
        }
    });

    setComposerModeCreate();
    updateSearchClearVisibility();
    toggleRolePicker();
    fetchFeed(true);
})();
