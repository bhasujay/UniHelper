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
    };

    const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const MAX_IMAGE_SIZE_BYTES = 5 * 1024 * 1024;

    if (feedType && shell.dataset.defaultPostType) {
        feedType.value = shell.dataset.defaultPostType;
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
            form.reset();
            if (feedType && shell.dataset.defaultPostType) {
                feedType.value = shell.dataset.defaultPostType;
            }
            toggleRolePicker();
            clearSelectedImage();
        }

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

    function renderItem(item) {
        const card = document.createElement('article');
        card.className = 'feed-card';

        card.innerHTML = [
            '<div class="feed-card-head">',
                '<div class="feed-title-row">',
                    '<h3 class="feed-item-title">' + escapeHtml(item.title) + '</h3>',
                    '<p class="feed-item-meta">By ' + escapeHtml(item.author_name || 'Unknown') + ' (' + escapeHtml(item.author_role_label || roleLabel(shell.dataset.userRole)) + ')</p>',
                '</div>',
                '<span class="feed-type-badge">' + escapeHtml(typeLabel(item.post_type)) + '</span>',
            '</div>',
            postImageHtml(item),
            '<p class="feed-item-body">' + escapeHtml(item.body || '') + '</p>',
            '<div class="feed-item-foot">',
                '<span>Audience: ' + escapeHtml(item.audience_label || 'All roles') + '</span>',
                '<span>' + escapeHtml(formatDateTime(item.created_at)) + '</span>',
            '</div>',
            sessionMetaHtml(item)
        ].join('');

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
            post_type: row.post_type || 'announcement',
            title: row.title || '',
            body: row.body || '',
            image_path: row.image_path || '',
            created_at: row.created_at || '',
            audience_label: row.audience_label || 'All Roles',
            author_name: authorName,
            author_role_label: row.author_role_label || roleLabel(row.author_role || shell.dataset.userRole),
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
            const response = await fetch(API_BASE + '?controller=FeedController&action=createPost', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd,
            });

            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error((payload && payload.message) || 'Failed to publish post.');
            }

            closeComposer({ resetForm: true });
            showToast('Post published to feed.', 'success');
            if (isSearchMode()) {
                searchFeed(true);
            } else {
                fetchFeed(true);
            }
        } catch (error) {
            showToast(error.message || 'Failed to publish post.', 'error');
        } finally {
            publishBtn.disabled = false;
        }
    }

    openComposerBtn.addEventListener('click', function () {
        if (composer.hidden) {
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
        if (event.key === 'Escape' && !composer.hidden) {
            closeComposer({ resetForm: false });
        }
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
                clearSelectedImage();
                return;
            }

            const imageValidationError = validateSelectedImage(file);
            if (imageValidationError) {
                showToast(imageValidationError, 'error');
                clearSelectedImage();
                return;
            }

            previewSelectedImage(file);
        });
    }

    if (imageRemoveBtn) {
        imageRemoveBtn.addEventListener('click', function () {
            clearSelectedImage();
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

    updateSearchClearVisibility();
    toggleRolePicker();
    fetchFeed(true);
})();
