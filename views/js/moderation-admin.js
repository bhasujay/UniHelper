document.addEventListener('DOMContentLoaded', () => {

    // Move overlays to body
    const viewModOverlay = document.getElementById('adminViewModOverlay');
    const viewReqOverlay = document.getElementById('adminViewReqOverlay');
    if (viewModOverlay && viewModOverlay.parentNode !== document.body) {
        document.body.appendChild(viewModOverlay);
    }
    if (viewReqOverlay && viewReqOverlay.parentNode !== document.body) {
        document.body.appendChild(viewReqOverlay);
    }

    // Modal Close logic
    document.querySelectorAll('.close-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (viewModOverlay) viewModOverlay.style.display = 'none';
            if (viewReqOverlay) viewReqOverlay.style.display = 'none';
        });
    });

    [viewModOverlay, viewReqOverlay].forEach(overlay => {
        if (!overlay) return;
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.style.display = 'none';
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (viewModOverlay && viewModOverlay.style.display === 'flex') viewModOverlay.style.display = 'none';
            if (viewReqOverlay && viewReqOverlay.style.display === 'flex') viewReqOverlay.style.display = 'none';
        }
    });

    // Tab Logic Reusable
    function setupTabs(containerId, panelsContainerId) {
        const tabsContainer = document.getElementById(containerId);
        if (!tabsContainer) return;

        const tabs = tabsContainer.querySelectorAll('.mod-tab');
        const panels = document.getElementById(panelsContainerId).querySelectorAll('.mod-tab-panel');

        const activeTabEl = tabsContainer.querySelector('.mod-tab.active');
        if (!activeTabEl) return;
        let activeTab = activeTabEl.dataset.tab;
        tabsContainer.setAttribute('data-active', activeTab);

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.tab;
                if (target === activeTab) return;
                activeTab = target;

                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                panels.forEach(p => p.classList.remove('active'));
                const panel = document.getElementById(panelsContainerId).querySelector(`[data-panel="${target}"]`);
                if (panel) panel.classList.add('active');

                tabsContainer.setAttribute('data-active', target);

                if (containerId === 'adminReportsTabs' && typeof filterReports === 'function') {
                    filterReports();
                }
            });
        });
    }

    setupTabs('adminReportsTabs', 'adminReportsPanels');
    setupTabs('adminModsTabs', 'adminModsPanels');

    // SEARCH LOGIC
    const searchInput = document.getElementById('adminReportSearch');
    const searchClearBtn = document.getElementById('adminSearchClearBtn');

    window.filterReports = function() {
        if (!searchInput) return;
        const query = searchInput.value.toLowerCase().trim();
        
        if (query.length > 0) {
            searchClearBtn.style.display = 'flex';
        } else {
            searchClearBtn.style.display = 'none';
        }

        const activePanelId = document.querySelector('#adminReportsTabs .mod-tab.active').dataset.tab;
        const activePanel = document.querySelector(`#adminReportsPanels [data-panel="${activePanelId}"]`);
        
        if (!activePanel) return;

        const cards = activePanel.querySelectorAll('.mod-report-card');
        
        cards.forEach(card => {
            const textContent = card.querySelector('.mod-report-text')?.textContent.toLowerCase() || '';
            const reporterName = card.querySelector('.reporter-name')?.textContent.toLowerCase() || '';
            const modName = card.querySelector('.mod-moderator-info .mod-reporter-name')?.textContent.toLowerCase() || '';
            
            if (textContent.includes(query) || reporterName.includes(query) || modName.includes(query)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    };

    if (searchInput) {
        searchInput.addEventListener('input', filterReports);
    }
    
    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', () => {
            searchInput.value = '';
            filterReports();
        });
    }

    // API INTEGRATION
    const API = '/unihelper/api?controller=moderationController&action=';

    // Helper: Time ago
    function timeAgo(dateStr) {
        if (!dateStr) return '';
        const past = new Date(dateStr.replace(' ', 'T'));
        const diffMs = new Date() - past;
        const mins = Math.floor(diffMs / 60000);
        if (mins < 1) return 'just now';
        if (mins < 60) return mins + 'm ago';
        const hrs = Math.floor(mins / 60);
        if (hrs < 24) return hrs + 'h ago';
        const days = Math.floor(hrs / 24);
        if (days < 30) return days + 'd ago';
        return past.toLocaleDateString();
    }

    function truncate(text, max) {
        if (!text) return '(no content)';
        return text.length > max ? text.substring(0, max) + '…' : text;
    }

    const adminReportTemplate = document.getElementById('adminReportCardTemplate');
    const adminModTemplate = document.getElementById('adminModCardTemplate');

    function makeActionBtn(label, className, onClick) {
        const btn = document.createElement('button');
        btn.className = 'mod-action-btn ' + className;
        btn.textContent = label;
        btn.addEventListener('click', onClick);
        return btn;
    }

    // Moderator Mocking View Activity Connection
    const viewActivityBtn = document.getElementById('viewActivityBtn');

    if (viewActivityBtn && searchInput) {
        viewActivityBtn.addEventListener('click', () => {
            const modName = document.getElementById('viewModName').textContent;
            viewModOverlay.style.display = 'none';
            const resolvedTabBtn = document.querySelector('#adminReportsTabs .mod-tab[data-tab="admin-mods-resolved"]');
            if(resolvedTabBtn) resolvedTabBtn.click();
            searchInput.value = modName;
            filterReports();
        });
    }

    function renderReportCard(report, tabType) {
        if (!adminReportTemplate) return null;
        const clone = adminReportTemplate.content.cloneNode(true);
        const card = clone.querySelector('.mod-report-card');
        
        card.dataset.reportId = report.report_id;

        const isQuestion = report.reported_content_type === 'question';
        const badge = card.querySelector('.mod-report-type-badge');
        badge.textContent = isQuestion ? '❓ Question' : '💬 Answer';
        badge.classList.add(isQuestion ? 'type-question' : 'type-answer');

        card.querySelector('.mod-report-time').textContent = timeAgo(report.created_time || report.created_at);
        
        const reasonEl = card.querySelector('.mod-report-reason');
        let displayAction = report.action_taken || '';
        if ((tabType === 'admin-mods-resolved' || tabType === 'admin-resolved') && displayAction) {
             reasonEl.innerHTML = `<span class="reason-label">Reason:</span> ${report.reason || 'Unspecified'} | <span class="reason-label" style="color:var(--primary)">Action:</span> <span style="text-transform: capitalize;">${displayAction}</span>`;
        } else {
             reasonEl.innerHTML = `<span class="reason-label">Reason:</span> ${report.reason || 'Unspecified'}`;
        }
        
        card.querySelector('.mod-report-text').textContent = truncate(report.text, 280);

        const textEl = card.querySelector('.mod-report-body');
        textEl.style.cursor = 'pointer';
        textEl.addEventListener('click', (e) => {
            e.stopPropagation();
            const baseUrl = '/unihelper/qa-forum';
            const url = isQuestion 
                ? `${baseUrl}?question=${report.q_id}`
                : `${baseUrl}?question=${report.q_id}&answer=${report.a_id}`;
            window.open(url, '_blank');
        });

        // Reporter Info
        const avatar = card.querySelector('.reporter-avatar');
        if (report.reporter_profile_picture) {
            avatar.src = '/unihelper/public/' + report.reporter_profile_picture;
        }
        avatar.onerror = function() { this.src = '/unihelper/views/assets/default-pfp.png'; };
        card.querySelector('.reporter-name').textContent = report.reporter_name || 'Unknown User';

        const reporterGroup = card.querySelector('.mod-reporter-group');
        const actionsContainer = card.querySelector('.mod-report-actions');

        // Moderator Info
        if ((tabType === 'admin-mods-resolved' || tabType === 'admin-resolved' || tabType === 'admin-forwarded') && report.moderator_name) {
            const modInfo = document.createElement('div');
            modInfo.className = 'mod-moderator-info';
            
            let modPfp = '/unihelper/views/assets/default-pfp.png';
            if (report.moderator_profile_picture) {
                modPfp = '/unihelper/public/' + report.moderator_profile_picture;
            }

            modInfo.innerHTML = `
                <img class="mod-reporter-avatar reporter-avatar" src="${modPfp}" onerror="this.src='/unihelper/views/assets/default-pfp.png'">
                <span class="mod-reporter-name reporter-name">${report.moderator_name} (${tabType === 'admin-forwarded' ? 'Forwarded' : 'Resolved'})</span>
            `;
            reporterGroup.appendChild(modInfo);
        }

        // ACTIONS
        if (tabType === 'admin-pending' || tabType === 'admin-forwarded') {
            const ignoreBtn = makeActionBtn('Ignore', 'action-ignore', () => handleReportAction(report.report_id, 'takeAction', { report_action: 'ignored' }, card, tabType));
            const flagBtn = makeActionBtn('Flag', 'action-flag', async () => {
                if(await window.confirm('Flag this content?')) {
                    handleReportAction(report.report_id, 'takeAction', { report_action: 'flagged' }, card, tabType);
                }
            });
            const rmvBtn = makeActionBtn('Remove Content', 'action-flag', async () => {
                if(await window.confirm('Permanently remove this content and delete associated reports?')) {
                    handleReportAction(report.report_id, 'removeContent', {}, card, tabType);
                }
            });
            actionsContainer.append(ignoreBtn, flagBtn, rmvBtn);
        } else if (tabType === 'admin-mods-resolved') {
            const undoBtn = makeActionBtn('Undo', 'action-undo', async () => {
                if(await window.confirm('Undo this moderator action?')) {
                    const endpoint = report.action_taken === 'flagged' ? 'unflagReport' : 'backToPending';
                    handleReportAction(report.report_id, endpoint, {}, card, tabType);
                }
            });
            const rmvBtn = makeActionBtn('Remove Content', 'action-flag', async () => {
                if(await window.confirm('Permanently remove this content?')) {
                    handleReportAction(report.report_id, 'removeContent', {}, card, tabType);
                }
            });
            actionsContainer.append(undoBtn, rmvBtn);
        } else if (tabType === 'admin-resolved') {
            const undoBtn = makeActionBtn('Undo', 'action-undo', async () => {
                if(await window.confirm('Undo admin action?')) {
                    const endpoint = report.action_taken === 'flagged' ? 'unflagReport' : 'backToPending';
                    handleReportAction(report.report_id, endpoint, {}, card, tabType);
                }
            });
            actionsContainer.append(undoBtn);
        }

        return clone;
    }

    function renderModCard(item, tabType) {
        if (!adminModTemplate) return null;
        const clone = adminModTemplate.content.cloneNode(true);
        const card = clone.querySelector('.admin-mod-card');
        
        const name = item.user_name || 'Unknown User';
        const uni = item.university_name || 'System';
        let pfp = '/unihelper/views/assets/default-pfp.png';
        if (item.user_profile_picture) {
            pfp = '/unihelper/public/' + item.user_profile_picture;
        }

        card.querySelector('.admin-mod-avatar').src = pfp;
        card.querySelector('.admin-mod-avatar').onerror = function() { this.src = '/unihelper/views/assets/default-pfp.png'; };
        card.querySelector('.admin-mod-name').textContent = name;
        card.querySelector('.admin-mod-uni').textContent = uni;

        const btn = card.querySelector('.admin-mod-action-btn');
        if (tabType === 'admin-mods-all') {
            btn.textContent = 'View';
            btn.classList.add('action-view');
            btn.classList.remove('action-undo');
            btn.addEventListener('click', () => {
                document.getElementById('viewModAvatar').src = pfp;
                document.getElementById('viewModName').textContent = name;
                document.getElementById('viewModUni').textContent = uni;
                viewModOverlay.style.display = 'flex';
                
                const rmvBtn = document.getElementById('removeModBtn');
                const newRmvBtn = rmvBtn.cloneNode(true);
                rmvBtn.parentNode.replaceChild(newRmvBtn, rmvBtn);
                newRmvBtn.addEventListener('click', async () => {
                    if(await window.confirm('Remove moderator access for ' + name + '?')) {
                        try {
                            const formData = new URLSearchParams();
                            formData.append('user_id', item.user_id);
                            const res = await fetch(API + 'removeModerator', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: formData.toString()
                            });
                            const result = await res.json();
                            if (res.ok && result.success) {
                                if (typeof showToast === 'function') showToast(result.message || 'Moderator access removed.', 'success');
                                viewModOverlay.style.display = 'none';
                                loadModerators('admin-mods-all');
                            } else {
                                if (typeof showToast === 'function') showToast(result.message || 'Failed to remove moderator.', 'error');
                            }
                        } catch (e) {
                            if (typeof showToast === 'function') showToast('Network error.', 'error');
                        }
                    }
                });

                const profileBtn = document.getElementById('viewModProfileBtn');
                profileBtn.onclick = () => window.open('/unihelper/view/profile/' + item.user_id, '_blank');
            });
        } else if (tabType === 'admin-mods-requests') {
            btn.textContent = 'Review';
            btn.classList.add('action-view');
            btn.classList.remove('action-undo');
            btn.addEventListener('click', () => {
                document.getElementById('viewReqAvatar').src = pfp;
                document.getElementById('viewReqName').textContent = name;
                document.getElementById('viewReqUni').textContent = uni;
                document.getElementById('viewReqMotivation').textContent = item.motivation || 'No motivation provided.';
                viewReqOverlay.style.display = 'flex';

                const rejectBtn = document.getElementById('rejectReqBtn');
                const acceptBtn = document.getElementById('acceptReqBtn');

                const newRej = rejectBtn.cloneNode(true);
                rejectBtn.parentNode.replaceChild(newRej, rejectBtn);
                const newAcc = acceptBtn.cloneNode(true);
                acceptBtn.parentNode.replaceChild(newAcc, acceptBtn);

                newRej.addEventListener('click', async () => {
                    if(await window.confirm('Reject application?')) {
                        handleModRequest(item.request_id, 'reject');
                    }
                });
                newAcc.addEventListener('click', async () => {
                    if(await window.confirm('Accept this user as moderator?')) {
                        handleModRequest(item.request_id, 'accept');
                    }
                });

                const profileBtn = document.getElementById('viewReqProfileBtn');
                profileBtn.onclick = () => window.open('/unihelper/view/profile/' + item.user_id, '_blank');
            });
        }

        return clone;
    }

    async function loadReports(tabName) {
        const listMap = {
            'admin-pending': 'adminPendingList',
            'admin-mods-resolved': 'adminModsResolvedList',
            'admin-forwarded': 'adminForwardedList',
            'admin-resolved': 'adminResolvedList'
        };
        
        let endpoint = '';
        if (tabName === 'admin-pending') endpoint = 'getPendingReports';
        else if (tabName === 'admin-mods-resolved' || tabName === 'admin-resolved') endpoint = 'getAllResolvedReports';
        else if (tabName === 'admin-forwarded') endpoint = 'getAllForwardedReports';
        
        const list = document.getElementById(listMap[tabName]);
        if (!list) return;

        try {
            const res = await fetch(API + endpoint);
            if (!res.ok) throw new Error('Bad response');
            const data = await res.json();
            
            list.innerHTML = '';
            let count = 0;

            if (data.success && data.data && data.data.length > 0) {
                data.data.forEach(report => {
                    if (endpoint === 'getAllResolvedReports') {
                        const isModResolved = report.moderator_id !== null && report.moderator_role !== 'role-admin';
                        if (tabName === 'admin-mods-resolved' && !isModResolved) return; 
                        if (tabName === 'admin-resolved' && isModResolved) return; 
                    }
                    const card = renderReportCard(report, tabName);
                    if (card) {
                        list.appendChild(card);
                        count++;
                    }
                });
            }
            
            const countEl = document.getElementById(tabName === 'admin-pending' ? 'adminPendingCount' : (tabName === 'admin-mods-resolved' ? 'adminModsResolvedCount' : (tabName === 'admin-forwarded' ? 'adminForwardedCount' : 'adminResolvedCount')));
            if(countEl) {
                countEl.textContent = count;
                countEl.style.display = count > 0 ? 'inline-flex' : 'none';
            }

            if (typeof filterReports === 'function') filterReports();
        } catch (e) {
            console.error('Failed to load reports for ' + tabName, e);
        }
    }

    async function loadModerators(tabName) {
        let endpoint = '';
        let listId = '';
        if (tabName === 'admin-mods-all') {
            endpoint = 'getCurrentModerators';
            listId = 'adminModsAllList';
        } else {
            endpoint = 'getModeratorRequests';
            listId = 'adminModsRequestsList';
        }

        const list = document.getElementById(listId);
        if (!list) return;

        try {
            const res = await fetch(API + endpoint);
            if (!res.ok) throw new Error('Bad response');
            const data = await res.json();
            
            list.innerHTML = '';
            let count = 0;

            if (data.success && data.data && data.data.length > 0) {
                const filteredData = data.data.filter(item => {
                    if (tabName === 'admin-mods-requests' && item.status !== 'pending') return false;
                    return true;
                });
                
                filteredData.forEach(item => {
                    const card = renderModCard(item, tabName);
                    if(card) {
                         list.appendChild(card);
                         count++;
                    }
                });
            }

            const countEl = document.getElementById(tabName === 'admin-mods-all' ? 'adminModsAllCount' : 'adminModsRequestsCount');
            if(countEl) {
                countEl.textContent = count;
                countEl.style.display = count > 0 ? 'inline-flex' : 'none';
            }
        } catch (e) {
            console.error('Failed to load mods for ' + tabName, e);
        }
    }

    async function handleReportAction(reportId, actionEndpoint, extraData, cardEl, tabType) {
        const btns = cardEl.querySelectorAll('.mod-action-btn');
        btns.forEach(b => b.disabled = true);
        
        try {
            const formData = new URLSearchParams();
            formData.append('report_id', reportId);
            for (const [key, value] of Object.entries(extraData)) {
                formData.append(key, value);
            }

            const res = await fetch(API + actionEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });
            const result = await res.json();

            if (res.ok && result.success) {
                if (typeof showToast === 'function') showToast(result.message || 'Action executed successfully.', 'success');
                cardEl.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                cardEl.style.opacity = '0';
                cardEl.style.transform = 'translateX(30px)';
                setTimeout(() => {
                    cardEl.remove();
                    ['admin-pending', 'admin-mods-resolved', 'admin-forwarded', 'admin-resolved'].forEach(t => loadReports(t));
                }, 300);
            } else {
                if (typeof showToast === 'function') showToast(result.message || 'Action failed.', 'error');
                btns.forEach(b => b.disabled = false);
            }
        } catch (e) {
            if (typeof showToast === 'function') showToast('Network Error.', 'error');
            btns.forEach(b => b.disabled = false);
        }
    }

    async function handleModRequest(appId, reviewAction) {
        try {
            const formData = new URLSearchParams();
            formData.append('application_id', appId);
            formData.append('review_action', reviewAction);

            const res = await fetch(API + 'reviewModeratorApplication', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });
            const result = await res.json();

            if (res.ok && result.success) {
                if (typeof showToast === 'function') showToast(result.message || 'Request updated successfully.', 'success');
                viewReqOverlay.style.display = 'none';
                loadModerators('admin-mods-requests');
                loadModerators('admin-mods-all');
            } else {
                if (typeof showToast === 'function') showToast(result.message || 'Failed to update request.', 'error');
            }
        } catch (e) {
            if (typeof showToast === 'function') showToast('Network Error.', 'error');
        }
    }

    // Load initial data
    loadReports('admin-pending');
    loadReports('admin-mods-resolved');
    loadReports('admin-forwarded');
    loadReports('admin-resolved');

    loadModerators('admin-mods-all');
    loadModerators('admin-mods-requests');

    // Refresh Handlers
    const refreshReportsBtn = document.getElementById('adminRefreshReportsBtn');
    if (refreshReportsBtn) {
        refreshReportsBtn.addEventListener('click', () => {
            const icon = refreshReportsBtn.querySelector('.refresh-icon');
            if(icon) icon.classList.add('spin-anim');
            
            Promise.all([
                loadReports('admin-pending'),
                loadReports('admin-mods-resolved'),
                loadReports('admin-forwarded')
            ]).then(() => {
                setTimeout(() => { if(icon) icon.classList.remove('spin-anim'); }, 600);
            });
        });
    }

    const refreshModsBtn = document.getElementById('adminRefreshModsBtn');
    if (refreshModsBtn) {
        refreshModsBtn.addEventListener('click', () => {
            const icon = refreshModsBtn.querySelector('.refresh-icon');
            if(icon) icon.classList.add('spin-anim');
            
            Promise.all([
                loadModerators('admin-mods-all'),
                loadModerators('admin-mods-requests')
            ]).then(() => {
                setTimeout(() => { if(icon) icon.classList.remove('spin-anim'); }, 600);
            });
        });
    }

});
