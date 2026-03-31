/**
 * Notification Modal Controller
 * Handles: modal toggle, tab switching, empty-state visibility,
 *          and exposes helpers for future fetch-based population.
 */
(function () {
    'use strict';

    // ── DOM references ──────────────────────────────────────────────
    const bellBtn       = document.getElementById('notificationBellBtn');
    const dot           = document.getElementById('notificationDot');
    const overlay       = document.getElementById('notificationModalOverlay');
    const closeBtn      = document.getElementById('notificationModalClose');
    const tabsContainer = document.querySelector('.notification-tabs');
    const tabs          = document.querySelectorAll('.notification-tab');
    const panels        = document.querySelectorAll('.notification-panel');
    const template      = document.getElementById('notificationItemTemplate');

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

    // ── Modal open / close ──────────────────────────────────────────
    function openModal() {
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden'; // prevent bg scroll
    }

    function closeModal() {
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    function toggleModal() {
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
    function setDotActive(active) {
        if (!dot) return;
        dot.classList.toggle('active', !!active);
    }

    // ── Badge count on the "New" tab ────────────────────────────────
    function updateNewCount(count) {
        if (!newCountBadge) return;
        if (count > 0) {
            newCountBadge.textContent = count > 99 ? '99+' : count;
            newCountBadge.style.display = '';
        } else {
            newCountBadge.style.display = 'none';
        }
        // Also sync the bell dot
        setDotActive(count > 0);
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

        if (data.id)      item.setAttribute('data-notif-id', data.id);
        if (data.title)   item.querySelector('.notification-item-title').textContent   = data.title;
        if (data.message) item.querySelector('.notification-item-message').textContent = data.message;
        if (data.time)    item.querySelector('.notification-item-time').textContent    = data.time;

        if (data.iconHtml) {
            item.querySelector('.notification-item-icon').innerHTML = data.iconHtml;
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
        const actionBtn = item.querySelector('.notification-item-action');
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
        list.innerHTML = '';
        refreshEmpty(tabName);
        if (tabName === 'new') updateNewCount(0);
    }

    // ── Expose public API on window for external scripts ────────────
    window.NotificationModal = {
        open:               openModal,
        close:              closeModal,
        toggle:             toggleModal,
        switchTab:           switchTab,
        addNotification:     addNotification,
        removeNotification:  removeNotification,
        markAsOpened:        markAsOpened,
        clearTab:            clearTab,
        setDotActive:        setDotActive,
        updateNewCount:      updateNewCount,
        refreshEmpty:        refreshEmpty,
    };
})();
