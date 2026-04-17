/**
 * Notification Modal Controller
 * Handles: modal toggle, tab switching, empty-state visibility,
 *          and exposes helpers for future fetch-based population.
 */
(function () {
    'use strict';

    const API_BASE = '/unihelper/api';
    const POLL_INTERVAL_MS = 60 * 1000;

    // ── DOM references ──────────────────────────────────────────────
    const bellBtn       = document.getElementById('notificationBellBtn');
    const dot           = document.getElementById('notificationDot');
    const overlay       = document.getElementById('notificationModalOverlay');
    const closeBtn      = document.getElementById('notificationModalClose');
    const tabsContainer = document.querySelector('.notification-tabs');
    const tabs          = document.querySelectorAll('.notification-tab');
    const panels        = document.querySelectorAll('.notification-panel');
    const template      = document.getElementById('notificationItemTemplate');
    const markAllReadBtn = document.getElementById('markAllReadBtn');

    // List & empty-state references per tab
    const lists = {
        new:    document.getElementById('newNotifList'),
        opened: document.getElementById('openedNotifList'),
    };
    const empties = {
        new:    document.getElementById('newNotifEmpty'),
        opened: document.getElementById('openedNotifEmpty'),
    };
    const newCountBadge = document.getElementById('newNotifCount');

    const state = {
        hasFetchedUnread: false,
        hasFetchedRead: false,
        activeTab: 'new',
        serverUnreadCount: 0,
        unreadRequestPromise: null,
        readRequestPromise: null,
        renderedUnreadCount: null,
    };

    // icons for the notification types
    const icons = {
        'qa': '🗯️',
        'session': '💻',
        'connection': '👥',
        'other': '🔔'
    };

    function buildApiUrl(action, extraParams) {
        const query = new URLSearchParams(
            Object.assign(
                {
                    controller: 'notificationController',
                    action: action,
                },
                extraParams || {}
            )
        );
        return API_BASE + '?' + query.toString();
    }

    async function apiGet(action, extraParams) {
        const response = await fetch(buildApiUrl(action, extraParams), {
            method: 'GET',
            credentials: 'same-origin',
        });

        let payload;
        try {
            payload = await response.json();
        } catch (e) {
            throw new Error('Invalid server response.');
        }

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Request failed.');
        }

        return payload;
    }

    async function apiPost(action, bodyParams) {
        const formData = new FormData();
        Object.keys(bodyParams || {}).forEach(function (key) {
            formData.append(key, String(bodyParams[key]));
        });

        const response = await fetch(buildApiUrl(action), {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        });

        let payload;
        try {
            payload = await response.json();
        } catch (e) {
            throw new Error('Invalid server response.');
        }

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Request failed.');
        }

        return payload;
    }

    function notify(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type || 'success');
        }
    }

    function scheduleAfterPaint(callback) {
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(callback);
            return;
        }

        window.setTimeout(callback, 0);
    }

    function formatTime(value) {
        if (!value) return 'Just now';
        const date = new Date(value);
        if (isNaN(date.getTime())) return 'Just now';

        const now = Date.now();
        const diffMs = Math.max(0, now - date.getTime());
        const diffMinutes = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMinutes / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffMinutes < 1) return 'Just now';
        if (diffMinutes < 60) return diffMinutes + 'm ago';
        if (diffHours < 24) return diffHours + 'h ago';
        if (diffDays < 7) return diffDays + 'd ago';

        return date.toLocaleString();
    }

    function mapNotification(row) {
        const type = row.type && icons[row.type] ? row.type : 'other';
        return {
            id: String(row.id || ''),
            message: row.message || '',
            time: formatTime(row.created_at),
            link: row.link || '',
            iconHtml: icons[type],
        };
    }

    function clearAndRender(tabName, rows) {
        const list = lists[tabName];
        if (!list) {
            return;
        }

        list.classList.add('is-bulk-rendering');
        const fragment = document.createDocumentFragment();

        (rows || []).forEach(function (row) {
            const item = createNotificationItem(mapNotification(row));
            if (!item) {
                return;
            }

            if (tabName === 'opened') {
                const markBtn = item.querySelector('.notification-item-action.mark-action');
                if (markBtn) {
                    markBtn.remove();
                }
            }

            fragment.appendChild(item);
        });

        list.replaceChildren(fragment);
        refreshEmpty(tabName);

        if (tabName === 'new') {
            updateNewCount(list.children.length);
        }

        scheduleAfterPaint(function () {
            if (lists[tabName]) {
                lists[tabName].classList.remove('is-bulk-rendering');
            }
        });
    }

    function normalizeUnreadCount(value) {
        const count = Number(value);
        if (!Number.isFinite(count) || count < 0) {
            return 0;
        }

        return Math.floor(count);
    }

    function getLoadedUnreadCount() {
        if (!lists.new) {
            return 0;
        }

        return lists.new.children.length;
    }

    function shouldFetchUnreadFromServer() {
        if (state.unreadRequestPromise) {
            return false;
        }

        if (!state.hasFetchedUnread) {
            return true;
        }

        return state.serverUnreadCount > getLoadedUnreadCount();
    }

    async function checkAnyNewNotifications() {
        try {
            const payload = await apiGet('checkAny');
            const unreadCount = normalizeUnreadCount(payload.unread_count);

            state.serverUnreadCount = unreadCount;
            updateNewCount(unreadCount);
        } catch (error) {
            // Keep UI usable even if the periodic check fails.
        }
    }

    function fetchUnread() {
        if (state.unreadRequestPromise) {
            return state.unreadRequestPromise;
        }

        state.unreadRequestPromise = apiGet('getUnread')
            .then(function (payload) {
                clearAndRender('new', payload.data || []);
                state.hasFetchedUnread = true;
                state.serverUnreadCount = getLoadedUnreadCount();
                updateNewCount(state.serverUnreadCount);
            })
            .finally(function () {
                state.unreadRequestPromise = null;
            });

        return state.unreadRequestPromise;
    }

    function fetchRead() {
        if (state.readRequestPromise) {
            return state.readRequestPromise;
        }

        state.readRequestPromise = apiGet('getRead')
            .then(function (payload) {
                clearAndRender('opened', payload.data || []);
                state.hasFetchedRead = true;
            })
            .finally(function () {
                state.readRequestPromise = null;
            });

        return state.readRequestPromise;
    }

    // ── Modal open / close ──────────────────────────────────────────
    function openModal() {
        if (!overlay) {
            return;
        }

        overlay.classList.add('show');
        document.body.classList.add('notification-modal-open');

        // Let the modal paint first, then run tab/fetch logic.
        scheduleAfterPaint(function () {
            switchTab('new');
        });
    }

    function closeModal() {
        if (!overlay) {
            return;
        }

        overlay.classList.remove('show');
        document.body.classList.remove('notification-modal-open');
    }

    function toggleModal() {
        if (!overlay) {
            return;
        }

        overlay.classList.contains('show') ? closeModal() : openModal();
    }

    // Bell button toggles the modal
    if (bellBtn) {
        bellBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleModal();
        });
    }

    // Close button
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    // Clicking the overlay (outside the modal) closes it
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });
    }

    // Escape key closes the modal
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay && overlay.classList.contains('show')) {
            closeModal();
        }
    });

    // ── Tab switching ───────────────────────────────────────────────
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const target = this.getAttribute('data-tab');
            switchTab(target);
        });
    });

    function switchTab(name) {
        if (!name) {
            return;
        }

        const isSameTab = state.activeTab === name;
        state.activeTab = name;

        if (!isSameTab) {
            // Update tab buttons
            tabs.forEach(function (t) {
                t.classList.toggle('active', t.getAttribute('data-tab') === name);
            });

            // Move slide indicator
            if (tabsContainer) {
                tabsContainer.setAttribute('data-active', name);
            }

            // Show correct panel
            panels.forEach(function (p) {
                p.classList.toggle('active', p.getAttribute('data-panel') === name);
            });
        }

        if (name === 'new' && shouldFetchUnreadFromServer()) {
            fetchUnread().catch(function (error) {
                notify(error.message || 'Failed to load unread notifications.', 'error');
            });
        }

        if (name === 'opened' && !state.hasFetchedRead) {
            fetchRead().catch(function (error) {
                notify(error.message || 'Failed to load opened notifications.', 'error');
            });
        }
    }

    // ── Empty-state helpers ─────────────────────────────────────────
    /**
     * Call this after adding or removing items in a list to toggle
     * the "no notifications" placeholder automatically.
     */
    function refreshEmpty(tabName) {
        const list  = lists[tabName];
        const empty = empties[tabName];
        if (!list || !empty) return;

        const hasItems = list.children.length > 0;
        empty.style.display = hasItems ? 'none' : 'flex';
    }

    // Run on init so both panels start with the correct visibility
    refreshEmpty('new');
    refreshEmpty('opened');

    // ── Notification dot (red indicator) ────────────────────────────
    function setDotCount(count) {
        if (!dot) return;

        const normalizedCount = normalizeUnreadCount(count);

        if (normalizedCount > 0) {
            const nextText = normalizedCount > 99 ? '99+' : String(normalizedCount);
            if (dot.textContent !== nextText) {
                dot.textContent = nextText;
            }
            dot.classList.add('active');
            return;
        }

        if (dot.textContent) {
            dot.textContent = '';
        }
        dot.classList.remove('active');
    }

    // ── Badge count on the "New" tab ────────────────────────────────
    function updateNewCount(count) {
        const normalizedCount = normalizeUnreadCount(count);

        if (state.renderedUnreadCount === normalizedCount) {
            return;
        }

        state.renderedUnreadCount = normalizedCount;

        if (newCountBadge) {
            if (normalizedCount > 0) {
                newCountBadge.textContent = normalizedCount > 99 ? '99+' : normalizedCount;
                newCountBadge.style.display = '';
            } else {
                newCountBadge.style.display = 'none';
            }
        }

        setDotCount(normalizedCount);
    }

    // ── Template-based item creation ────────────────────────────────
    /**
     * Creates a notification DOM element from the hidden <template>.
     *
     * @param {Object} data
     * @param {string} data.id        – unique notification id
     * @param {string} data.title     – heading text
     * @param {string} data.message   – body text
     * @param {string} data.time      – human-readable timestamp
     * @param {string} [data.iconHtml]– optional SVG string for the icon
     * @returns {HTMLElement}
     */
    function createNotificationItem(data) {
        if (!template) return null;

        const clone = template.content.cloneNode(true);
        const item  = clone.querySelector('.notification-item');

        if (data.id) {
            item.setAttribute('data-notif-id', data.id);
        }
        
        const contentLink = item.querySelector('.notification-item-content');
        if (contentLink) {
            if (data.link) {
                contentLink.setAttribute('href', data.link);
            } else {
                contentLink.removeAttribute('href');
                contentLink.addEventListener('click', function (event) {
                    event.preventDefault();
                });
            }
        }

        const titleEl = item.querySelector('.notification-item-title');
        if (titleEl && data.title) {
            titleEl.textContent = data.title;
        } else if (titleEl) {
            titleEl.remove(); // If no title is given, remove the element entirely to save space
        }

        const messageEl = item.querySelector('.notification-item-message');
        if (messageEl && data.message) {
            messageEl.textContent = data.message;
        }

        const timeEl = item.querySelector('.notification-item-time');
        if (timeEl && data.time) {
            timeEl.textContent = data.time;
        }

        const iconContainer = item.querySelector('.notification-item-icon');
        if (iconContainer) {
            iconContainer.textContent = data.iconHtml || icons.other;
        }

        return item;
    }

    /**
     * Appends a notification item to the specified tab list.
     *
     * @param {'new'|'opened'} tabName
     * @param {Object} data – same shape as createNotificationItem expects
     */
    function addNotification(tabName, data) {
        const item = createNotificationItem(data);
        if (!item) return;

        const list = lists[tabName];
        if (!list) return;

        if (tabName === 'opened') {
            const markBtn = item.querySelector('.notification-item-action.mark-action');
            if (markBtn) {
                markBtn.remove();
            }
        }

        list.appendChild(item);
        refreshEmpty(tabName);

        if (tabName === 'new') {
            updateNewCount(lists.new.children.length);
        }
    }

    /**
     * Removes a notification item by its id from whichever list it lives in.
     *
     * @param {string} id
     */
    function removeNotification(id) {
        Object.keys(lists).forEach(function (tabName) {
            const item = lists[tabName].querySelector('[data-notif-id="' + id + '"]');
            if (item) {
                item.remove();
                refreshEmpty(tabName);
                if (tabName === 'new') {
                    updateNewCount(lists.new.children.length);
                }
            }
        });
    }

    /**
     * Moves a notification from "new" to "opened".
     *
     * @param {string} id
     */
    function markAsOpened(id) {
        const item = lists.new.querySelector('[data-notif-id="' + id + '"]');
        if (!item) return;

        item.remove();
        refreshEmpty('new');
        updateNewCount(lists.new.children.length);

        // Hide the action button once opened
        const actionBtn = item.querySelector('.notification-item-action.mark-action');
        if (actionBtn) actionBtn.style.display = 'none';

        lists.opened.prepend(item);
        refreshEmpty('opened');
    }

    /**
     * Clears all items from a tab.
     *
     * @param {'new'|'opened'} tabName
     */
    function clearTab(tabName) {
        const list = lists[tabName];
        if (!list) return;
        list.replaceChildren();
        refreshEmpty(tabName);
        if (tabName === 'new') updateNewCount(0);
    }

    async function handleMarkAsRead(notificationId) {
        await apiPost('markAsRead', { notification_id: notificationId });
        markAsOpened(notificationId);
    }

    async function handleDelete(notificationId) {
        await apiPost('delete', { notification_id: notificationId });
        removeNotification(notificationId);
    }

    async function handleMarkAllAsRead() {
        await apiPost('markAllAsRead', {});

        if (!lists.new || !lists.opened) {
            updateNewCount(0);
            notify('All notifications marked as read.', 'success');
            return;
        }

        const items = lists.new.querySelectorAll('.notification-item[data-notif-id]');
        if (items.length > 0) {
            const fragment = document.createDocumentFragment();

            Array.prototype.forEach.call(items, function (item) {
                const actionBtn = item.querySelector('.notification-item-action.mark-action');
                if (actionBtn) {
                    actionBtn.remove();
                }
                fragment.appendChild(item);
            });

            lists.opened.prepend(fragment);
        }

        refreshEmpty('new');
        refreshEmpty('opened');

        updateNewCount(0);
        notify('All notifications marked as read.', 'success');
    }

    if (lists.new) {
        lists.new.addEventListener('click', function (event) {
            const markBtn = event.target.closest('.mark-action');
            if (!markBtn) return;

            event.preventDefault();
            event.stopPropagation();

            const item = markBtn.closest('.notification-item');
            if (!item) return;

            const id = item.getAttribute('data-notif-id');
            if (!id) return;

            handleMarkAsRead(id)
                .catch(function (error) {
                    notify(error.message || 'Failed to mark notification.', 'error');
                });
        });

        lists.new.addEventListener('click', function (event) {
            const deleteBtn = event.target.closest('.delete-action');
            if (!deleteBtn) return;

            event.preventDefault();
            event.stopPropagation();

            const item = deleteBtn.closest('.notification-item');
            if (!item) return;

            const id = item.getAttribute('data-notif-id');
            if (!id) return;

            handleDelete(id)
                .catch(function (error) {
                    notify(error.message || 'Failed to delete notification.', 'error');
                });
        });
    }

    if (lists.opened) {
        lists.opened.addEventListener('click', function (event) {
            const deleteBtn = event.target.closest('.delete-action');
            if (!deleteBtn) return;

            event.preventDefault();
            event.stopPropagation();

            const item = deleteBtn.closest('.notification-item');
            if (!item) return;

            const id = item.getAttribute('data-notif-id');
            if (!id) return;

            handleDelete(id)
                .catch(function (error) {
                    notify(error.message || 'Failed to delete notification.', 'error');
                });
        });
    }

    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function () {
            if (!lists.new || lists.new.children.length === 0) {
                return;
            }

            handleMarkAllAsRead().catch(function (error) {
                notify(error.message || 'Failed to mark all as read.', 'error');
            });
        });
    }

    // Remove static placeholders from the server template; runtime rendering is API-driven.
    clearTab('new');
    clearTab('opened');
    switchTab('new');

    // Initial check on page load and periodic polling every 1 minute.
    checkAnyNewNotifications();
    setInterval(checkAnyNewNotifications, POLL_INTERVAL_MS);

})();
