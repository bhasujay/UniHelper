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
    const feedType = document.getElementById('feedType');
    const titleInput = document.getElementById('feedTitle');

    if (!shell || !composer || !openComposerBtn || !form || !list || !loadMoreBtn || !audienceMode) {
        return;
    }

    const state = {
        page: 1,
        limit: 8,
        loading: false,
        hasMore: true,
    };

    if (feedType && shell.dataset.defaultPostType) {
        feedType.value = shell.dataset.defaultPostType;
    }

    function openComposer() {
        composer.hidden = false;
        composer.classList.remove('is-hidden');
        openComposerBtn.classList.add('expanded');
        openComposerBtn.setAttribute('aria-expanded', 'true');

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
        }

        composer.classList.add('is-hidden');
        composer.hidden = true;
        openComposerBtn.classList.remove('expanded');
        openComposerBtn.setAttribute('aria-expanded', 'false');
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
                list.innerHTML = '<div class="feed-empty">No posts yet. Publish the first update for your audience.</div>';
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
                list.innerHTML = '<div class="feed-empty">Unable to load feed right now.</div>';
            }
            showToast(error.message || 'Feed loading failed.', 'error');
        } finally {
            state.loading = false;
            loadMoreBtn.disabled = false;
        }
    }

    async function publishPost(event) {
        event.preventDefault();

        const fd = new FormData(form);
        if ((fd.get('audience_mode') || '') === 'selected_roles' && fd.getAll('audience_roles[]').length === 0) {
            showToast('Select at least one audience role.', 'error');
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
            fetchFeed(true);
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

    audienceMode.addEventListener('change', toggleRolePicker);
    form.addEventListener('submit', publishPost);
    loadMoreBtn.addEventListener('click', function () {
        fetchFeed(false);
    });

    toggleRolePicker();
    fetchFeed(true);
})();
