document.addEventListener('DOMContentLoaded', () => {
    const API_BASE = '/unihelper/api';
    const PROFILE_BASE = '/unihelper/view/profile/';

    const tabsContainer = document.querySelector('.connections-view-header-tabs');
    const tabs = document.querySelectorAll('.connections-view-header-tabs-tab');
    const tabContents = {
        'suggestions-tab': document.getElementById('suggestions-tab-content'),
        'friends-tab': document.getElementById('friends-tab-content'),
        'requests-tab': document.getElementById('requests-tab-content')
    };
    const searchResultTabContent = document.getElementById('search-results-tab-content');
    const searchInput = document.getElementById('connections-search-input');
    const searchTriggerBtn = document.querySelector('.search-trigger-btn');
    const searchClearBtn = document.querySelector('.search-clear-btn');
    const cardTemplate = document.querySelector('.connection-card.template');

    const spaces = {
        suggestions: document.querySelector('#suggestions-tab-content .connections-view-cards-space'),
        friends: document.querySelector('#friends-tab-content .connections-view-cards-space'),
        requests: document.querySelector('#requests-tab-content .connections-view-cards-space'),
        search: document.querySelector('#search-results-tab-content .connections-view-cards-space')
    };

    const state = {
        activeTabId: 'suggestions-tab',
        activeSuggestionType: 'mutual',
        activeRequestType: 'incoming',
        currentSearchToken: 0
    };
    const USER_SEARCH_PAGE_INDEX = 0;

    function showToast(message, type = 'info') {
        if (typeof window.showToastNotification === 'function') {
            window.showToastNotification(message, type);
        }
    }

    function getSafeName(user) {
        if (user.name && String(user.name).trim()) {
            return String(user.name).trim();
        }

        const first = user.first_name ? String(user.first_name).trim() : '';
        const last = user.last_name ? String(user.last_name).trim() : '';
        const full = `${first} ${last}`.trim();
        return full || 'Unknown User';
    }

    function getSafeRole(user) {
        return user.role ? String(user.role) : 'User';
    }

    function getFallbackAvatar(name) {
        return '/unihelper/views/assets/default-pfp.png';
    }

    function resolveProfilePicture(path, userName) {
        if (!path) {
            return getFallbackAvatar(userName);
        }

        const raw = String(path).trim();
        if (!raw) {
            return getFallbackAvatar(userName);
        }

        if (/^https?:\/\//i.test(raw)) {
            return raw;
        }
        
        let cleanPath = raw;
        if (!cleanPath.startsWith('/')) {
            cleanPath = '/' + cleanPath;
        }
        
        if (!cleanPath.startsWith('/unihelper/public')) {
             return `/unihelper/public${cleanPath}`;
        }

        return cleanPath;
    }

    function normalizeUser(raw) {
        const userId = Number(raw.user_id ?? raw.id ?? raw.friend_id ?? 0);
        const name = getSafeName(raw);

        return {
            user_id: userId,
            name,
            role: getSafeRole(raw),
            profile_picture: resolveProfilePicture(raw.profile_picture, name),
            raw
        };
    }

    function setSpaceMessage(spaceEl, message, isError = false) {
        if (!spaceEl) return;

        spaceEl.innerHTML = '';
        const msg = document.createElement('div');
        msg.style.gridColumn = '1 / -1';
        msg.style.padding = '14px';
        msg.style.borderRadius = '10px';
        msg.style.border = '1px solid var(--border)';
        msg.style.background = 'var(--card)';
        msg.style.color = isError ? '#dc2626' : 'var(--text)';
        msg.textContent = message;
        spaceEl.appendChild(msg);
    }

    function renderCards(spaceEl, users, getActions) {
        if (!spaceEl) return;

        spaceEl.innerHTML = '';
        if (!Array.isArray(users) || users.length === 0) {
            setSpaceMessage(spaceEl, 'No users found.');
            return;
        }

        users.forEach((entry) => {
            const user = entry.user ? entry.user : entry;
            const card = cardTemplate ? cardTemplate.cloneNode(true) : document.createElement('div');
            card.classList.remove('template');
            card.style.display = 'flex';

            const nameEl = card.querySelector('.connection-card-header-info-name');
            const roleEl = card.querySelector('.connection-card-header-info-role');
            const imgEl = card.querySelector('.connection-card-header img');
            const btnBox = card.querySelector('.connection-card-button');

            if (nameEl) nameEl.textContent = user.name;
            if (roleEl) roleEl.textContent = user.role;

            if (imgEl) {
                imgEl.src = user.profile_picture;
                imgEl.alt = `${user.name} profile picture`;
                imgEl.onerror = () => {
                    imgEl.onerror = null;
                    imgEl.src = getFallbackAvatar(user.name);
                };
            }

            if (btnBox) {
                btnBox.innerHTML = '';
                const actions = (typeof getActions === 'function' ? getActions(entry) : []) || [];

                actions.forEach((action) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = action.label;

                    if (action.variant === 'danger') {
                        btn.classList.add('btn-danger');
                    } else if (action.variant === 'primary') {
                        btn.classList.add('btn-primary');
                    } else {
                        btn.classList.add('btn-default');
                    }

                    btn.addEventListener('click', action.onClick);
                    btnBox.appendChild(btn);
                });
            }

            spaceEl.appendChild(card);
        });
    }

    async function apiGet(controller, action, params = {}) {
        const query = new URLSearchParams({ controller, action, ...params });
        const res = await fetch(`${API_BASE}?${query.toString()}`, {
            method: 'GET',
            credentials: 'same-origin'
        });

        let payload;
        try {
            payload = await res.json();
        } catch (error) {
            throw new Error('Invalid server response');
        }

        if (!res.ok || !payload.success) {
            throw new Error(payload.message || 'Request failed');
        }

        return payload;
    }

    async function apiPost(action, friendId) {
        const query = new URLSearchParams({
            controller: 'connectionController',
            action
        });

        const formData = new FormData();
        formData.append('friend_id', String(friendId));

        const res = await fetch(`${API_BASE}?${query.toString()}`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        let payload;
        try {
            payload = await res.json();
        } catch (error) {
            throw new Error('Invalid server response');
        }

        if (!res.ok || !payload.success) {
            throw new Error(payload.message || 'Action failed');
        }

        return payload;
    }

    async function doConnectionAction(action, user, confirmText = '') {
        if (!user?.user_id) return;

        if (confirmText && !(await window.confirm(confirmText))) {
            return;
        }

        try {
            const response = await apiPost(action, user.user_id);
            showToast(response.message || 'Updated successfully', 'success');

            await refreshActiveTab();

            if (searchResultTabContent && searchResultTabContent.style.display === 'block' && searchInput?.value.trim()) {
                await runSearch(searchInput.value.trim());
            }
        } catch (error) {
            showToast(error.message || 'Failed to update connection', 'error');
        }
    }

    function getViewAction(user) {
        return {
            label: 'View',
            variant: 'primary',
            onClick: () => {
                window.location.href = `${PROFILE_BASE}${user.user_id}`;
            }
        };
    }

    async function loadFriends() {
        setSpaceMessage(spaces.friends, 'Loading friends...');

        try {
            const payload = await apiGet('connectionController', 'getFriends');
            const users = (payload.data || []).map(normalizeUser);
            renderCards(spaces.friends, users, (user) => [
                getViewAction(user),
                {
                    label: 'Remove',
                    variant: 'danger',
                    onClick: () => doConnectionAction('removeConnection', user, 'Remove this connection?')
                }
            ]);
        } catch (error) {
            setSpaceMessage(spaces.friends, error.message || 'Could not load friends', true);
        }
    }

    async function loadSuggestions(type = 'mutual') {
        if (type !== 'mutual') {
            setSpaceMessage(spaces.suggestions, 'This suggestion type is not supported by backend yet.');
            return;
        }

        setSpaceMessage(spaces.suggestions, 'Loading suggestions...');

        try {
            const payload = await apiGet('connectionController', 'getSuggestions', { type: 'mutual' });
            const users = (payload.data || []).map(normalizeUser);
            renderCards(spaces.suggestions, users, (user) => [
                {
                    label: 'Add friend',
                    variant: 'primary',
                    onClick: () => doConnectionAction('requestConnection', user)
                },
                getViewAction(user)
            ]);
        } catch (error) {
            setSpaceMessage(spaces.suggestions, error.message || 'Could not load suggestions', true);
        }
    }

    async function loadRequests(type = 'incoming') {
        setSpaceMessage(spaces.requests, 'Loading requests...');

        const action = type === 'outgoing' ? 'getPendingConnections' : 'getReceivedRequests';
        try {
            const payload = await apiGet('connectionController', action);
            const users = (payload.data || []).map(normalizeUser);

            if (type === 'outgoing') {
                renderCards(spaces.requests, users, (user) => [
                    {
                        label: 'Cancel',
                        variant: 'danger',
                        onClick: () => doConnectionAction('cancelConnection', user, 'Cancel this request?')
                    },
                    getViewAction(user)
                ]);
                return;
            }

            renderCards(spaces.requests, users, (user) => [
                {
                    label: 'Accept',
                    variant: 'primary',
                    onClick: () => doConnectionAction('acceptConnection', user)
                },
                {
                    label: 'Reject',
                    variant: 'danger',
                    onClick: () => doConnectionAction('rejectConnection', user, 'Reject this request?')
                },
                getViewAction(user)
            ]);
        } catch (error) {
            setSpaceMessage(spaces.requests, error.message || 'Could not load requests', true);
        }
    }

    async function getStatusForUser(friendId) {
        try {
            const payload = await apiGet('connectionController', 'checkStatus', { friend_id: String(friendId) });
            return {
                status: payload.status || 'none',
                initiatedBy: Number(payload.initiated_by || 0)
            };
        } catch (error) {
            return {
                status: 'none',
                initiatedBy: 0,
                error: error.message
            };
        }
    }

    async function runSearch(query) {
        const trimmed = query.trim();
        const token = ++state.currentSearchToken;

        if (!trimmed) {
            setSpaceMessage(spaces.search, 'Type a name and search.');
            return;
        }

        setSpaceMessage(spaces.search, 'Searching users...');

        try {
            const payload = await apiGet('searchController', 'search', {
                query: trimmed,
                type: 'user',
                index: USER_SEARCH_PAGE_INDEX
            });

            if (token !== state.currentSearchToken) {
                return;
            }

            const users = (payload.data || []).map(normalizeUser).filter((user) => user.user_id > 0);
            if (!users.length) {
                setSpaceMessage(spaces.search, 'No users found for this search.');
                return;
            }

            const statusEntries = await Promise.all(users.map(async (user) => {
                const status = await getStatusForUser(user.user_id);
                return { user, status };
            }));

            if (token !== state.currentSearchToken) {
                return;
            }

            renderCards(spaces.search, statusEntries, ({ user, status }) => {
                const actions = [getViewAction(user)];

                if (status.status === 'none') {
                    actions.unshift({
                        label: 'Add friend',
                        variant: 'primary',
                        onClick: () => doConnectionAction('requestConnection', user)
                    });
                } else if (status.status === 'accepted') {
                    actions.unshift({
                        label: 'Remove',
                        variant: 'danger',
                        onClick: () => doConnectionAction('removeConnection', user, 'Remove this connection?')
                    });
                } else if (status.status === 'pending') {
                    if (status.initiatedBy === user.user_id) {
                        actions.unshift({
                            label: 'Reject',
                            variant: 'danger',
                            onClick: () => doConnectionAction('rejectConnection', user, 'Reject this request?')
                        });
                        actions.unshift({
                            label: 'Accept',
                            variant: 'primary',
                            onClick: () => doConnectionAction('acceptConnection', user)
                        });
                    } else {
                        actions.unshift({
                            label: 'Cancel',
                            variant: 'danger',
                            onClick: () => doConnectionAction('cancelConnection', user, 'Cancel this request?')
                        });
                    }
                }

                return actions;
            });
        } catch (error) {
            if (token !== state.currentSearchToken) {
                return;
            }

            setSpaceMessage(spaces.search, error.message || 'Search failed', true);
        }
    }

    async function refreshActiveTab() {
        if (state.activeTabId === 'friends-tab') {
            await loadFriends();
            return;
        }

        if (state.activeTabId === 'requests-tab') {
            await loadRequests(state.activeRequestType);
            return;
        }

        await loadSuggestions(state.activeSuggestionType);
    }

    function updateTabVisibility(activeTabId) {
        state.activeTabId = activeTabId;

        Object.values(tabContents).forEach((content) => {
            if (content) content.style.display = 'none';
        });

        tabs.forEach((tab) => tab.classList.remove('active'));

        if (activeTabId) {
            const activeTabBtn = document.getElementById(activeTabId);
            if (activeTabBtn) activeTabBtn.classList.add('active');

            if (tabContents[activeTabId]) {
                tabContents[activeTabId].style.display = 'block';
            }

            if (searchResultTabContent) {
                searchResultTabContent.style.display = 'none';
            }

            refreshActiveTab();
        }
    }

    updateTabVisibility('suggestions-tab');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (searchClearBtn) searchClearBtn.style.display = 'none';
            if (tabsContainer) tabsContainer.style.display = 'flex';

            updateTabVisibility(tab.id);
        });
    });

    const tagContainers = document.querySelectorAll('.connections-view-content-tags');
    tagContainers.forEach((container) => {
        const tags = container.querySelectorAll('.connections-view-content-tags-tag');
        tags.forEach((tag) => {
            tag.addEventListener('click', () => {
                tags.forEach((t) => t.classList.remove('active'));
                tag.classList.add('active');

                const isSuggestionTag = !!container.closest('#suggestions-tab-content');
                const isRequestTag = !!container.closest('#requests-tab-content');

                if (isSuggestionTag) {
                    const label = tag.textContent?.trim().toLowerCase() || '';
                    if (label.includes('mutual')) {
                        state.activeSuggestionType = 'mutual';
                    } else if (label.includes('major')) {
                        state.activeSuggestionType = 'major';
                    } else if (label.includes('university')) {
                        state.activeSuggestionType = 'university';
                    }
                    loadSuggestions(state.activeSuggestionType);
                }

                if (isRequestTag) {
                    const label = tag.textContent?.trim().toLowerCase() || '';
                    state.activeRequestType = label.includes('outgoing') ? 'outgoing' : 'incoming';
                    loadRequests(state.activeRequestType);
                }
            });
        });
    });

    function handleSearch() {
        const query = searchInput ? searchInput.value.trim() : '';
        if (query.length > 0) {
            Object.values(tabContents).forEach((content) => {
                if (content) content.style.display = 'none';
            });

            if (tabsContainer) tabsContainer.style.display = 'none';

            if (searchResultTabContent) {
                searchResultTabContent.style.display = 'block';
                tabs.forEach((tab) => tab.classList.remove('active'));
            }

            if (searchClearBtn) searchClearBtn.style.display = 'inline-flex';

            runSearch(query);
        } else {
            handleClearSearch();
        }
    }

    function handleClearSearch() {
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }

        if (searchClearBtn) searchClearBtn.style.display = 'none';
        if (tabsContainer) tabsContainer.style.display = 'flex';

        updateTabVisibility('suggestions-tab');
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            if (searchInput.value.trim().length > 0) {
                if (searchClearBtn) searchClearBtn.style.display = 'inline-flex';
            } else {
                handleClearSearch();
            }
        });

        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                handleSearch();
            }
        });
    }

    if (searchTriggerBtn) {
        searchTriggerBtn.addEventListener('click', handleSearch);
    }

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', handleClearSearch);
    }
});
