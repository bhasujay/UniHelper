document.addEventListener('DOMContentLoaded', async () => {

    // ================================================================
    // NON-MODERATOR: Application Flow
    // ================================================================
    const applyBtn = document.getElementById('applyModeratorBtn');

    if (applyBtn) {
        const overlay = document.getElementById('modGuideOverlay');
        const agreeBtn = document.getElementById('agreeGuideBtn');
        const disagreeBtn = document.getElementById('disagreeGuideBtn');
        const appForm = document.getElementById('moderatorApplicationForm');
        const motivationInput = document.getElementById('motivationInput');

        // Move overlay to body so it escapes SSR main-content clipping
        if (overlay && overlay.parentNode !== document.body) {
            document.body.appendChild(overlay);
        }

        // Fetch application status
        try {
            const res = await fetch('/unihelper/api?controller=moderationController&action=checkModeratorApplicationStatus');
            if (res.ok) {
                const data = await res.json();
                if (data.success && data.data) {
                    const status = data.data.status;
                    if (status === 'pending') {
                        applyBtn.textContent = 'Application Pending...';
                        applyBtn.disabled = true;
                        applyBtn.classList.add('disabled');
                        motivationInput.disabled = true;
                    } else if (status === 'rejected') {
                        applyBtn.textContent = 'Reapply for a Moderator';
                        applyBtn.disabled = false;
                        applyBtn.classList.remove('disabled');
                    } else if (status === 'clear') {
                        applyBtn.textContent = 'Apply for a Moderator';
                        applyBtn.disabled = false;
                        applyBtn.classList.remove('disabled');
                    }
                }
            }
        } catch (e) {
            console.error('Failed to load moderator application status:', e);
        }

        // Form submit -> show guide overlay
        appForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const motivation = motivationInput.value.trim();
            if (!motivation) {
                if (typeof showToast === 'function') showToast('Please state your motivation.', 'error');
                return;
            }
            overlay.style.display = 'flex';
        });

        disagreeBtn.addEventListener('click', () => {
            overlay.style.display = 'none';
        });

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) overlay.style.display = 'none';
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && overlay.style.display === 'flex') overlay.style.display = 'none';
        });

        // Agree -> confirm -> submit
        agreeBtn.addEventListener('click', async () => {
            overlay.style.display = 'none';
            const motivation = motivationInput.value.trim();

            let confirmed = false;
            if (typeof window.confirm === 'function') {
                confirmed = await window.confirm('Are you sure you want to finalize your moderator application?');
            }
            if (!confirmed) return;

            const originalText = applyBtn.textContent;
            applyBtn.disabled = true;
            applyBtn.textContent = 'Submitting...';
            motivationInput.disabled = true;

            try {
                const formData = new URLSearchParams();
                formData.append('motivation', motivation);

                const res = await fetch('/unihelper/api?controller=moderationController&action=applyForModerator', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                });
                const result = await res.json();

                if (res.ok && result.success) {
                    if (typeof showToast === 'function') showToast(result.message || 'Application submitted!', 'success');
                    applyBtn.textContent = 'Application Pending...';
                    applyBtn.disabled = true;
                    applyBtn.classList.add('disabled');
                } else {
                    if (typeof showToast === 'function') showToast(result.message || 'Submission failed.', 'error');
                    applyBtn.disabled = false;
                    applyBtn.textContent = originalText;
                    motivationInput.disabled = false;
                }
            } catch (err) {
                if (typeof showToast === 'function') showToast('Network error.', 'error');
                applyBtn.disabled = false;
                applyBtn.textContent = originalText;
                motivationInput.disabled = false;
            }
        });

        return; // Non-moderator, stop here
    }

    // ================================================================
    // MODERATOR: Panel Logic
    // ================================================================
    const tabsContainer = document.querySelector('.mod-tabs');
    if (!tabsContainer) return;

    const tabs = tabsContainer.querySelectorAll('.mod-tab');
    const panels = document.querySelectorAll('.mod-tab-panel');
    const template = document.getElementById('reportCardTemplate');

    const API = '/unihelper/api?controller=moderationController&action=';

    // Helpers
    function timeAgo(dateStr) {
        const now = new Date();
        const past = new Date(dateStr);
        const diffMs = now - past;
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

    // Tab switching
    let activeTab = 'pending';
    tabsContainer.setAttribute('data-active', activeTab);

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;
            if (target === activeTab) return;
            activeTab = target;

            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            panels.forEach(p => p.classList.remove('active'));
            document.querySelector(`[data-panel="${target}"]`).classList.add('active');

            tabsContainer.setAttribute('data-active', target);
        });
    });

    // Build report card from template
    function createReportCard(report, tabType) {
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.mod-report-card');
        card.dataset.reportId = report.report_id;

        // Type badge
        const badge = card.querySelector('.mod-report-type-badge');
        const isQuestion = report.reported_content_type === 'question';
        badge.textContent = isQuestion ? '❓ Question' : '💬 Answer';
        badge.classList.add(isQuestion ? 'type-question' : 'type-answer');

        if (tabType === 'resolved' && report.action_taken) {
            const actionBadge = document.createElement('span');
            actionBadge.className = 'mod-report-type-badge';
            
            let displayAction = report.action_taken;
            if (displayAction === 'ignored') displayAction = 'Ignored';
            if (displayAction === 'flagged') displayAction = 'Flagged';
            if (displayAction === 'forwarded to admin') displayAction = 'Forwarded';

            actionBadge.textContent = "Action: " + displayAction;
            
            // set colors based on action
            if (report.action_taken === 'ignored') {
                actionBadge.style.background = 'rgba(0, 170, 255, 0.1)';
                actionBadge.style.color = '#00aaff';
            } else if (report.action_taken === 'flagged') {
                actionBadge.style.background = 'rgba(255, 71, 87, 0.1)';
                actionBadge.style.color = '#ff4757';
            } else if (report.action_taken === 'forwarded to admin') {
                actionBadge.style.background = 'rgba(255, 165, 2, 0.1)';
                actionBadge.style.color = '#ffa502';
            }

            card.querySelector('.mod-report-meta').appendChild(actionBadge);
        }

        // Time
        card.querySelector('.mod-report-time').textContent = timeAgo(report.created_time);

        // Text & Reason
        const reasonEl = card.querySelector('.mod-report-reason');
        reasonEl.innerHTML = `<span class="reason-label">Reason:</span> ${report.reason || 'Unspecified'}`;

        const textEl = card.querySelector('.mod-report-body');
        const textContentEl = card.querySelector('.mod-report-text');
        textContentEl.textContent = truncate(report.text, 280);
        
        textEl.style.cursor = 'pointer';
        textEl.addEventListener('click', (e) => {
            e.stopPropagation();
            const baseUrl = '/unihelper/qa-forum';
            const url = isQuestion 
                ? `${baseUrl}?question=${report.q_id}`
                : `${baseUrl}?question=${report.q_id}&answer=${report.a_id}`;
            window.open(url, '_blank');
        });

        // Reporter info
        const reporterEl = card.querySelector('.mod-reporter');
        const avatar = card.querySelector('.mod-reporter-avatar');
        if (report.reporter_profile_picture) {
            avatar.src = '/unihelper/public/' + report.reporter_profile_picture;
        }
        avatar.onerror = function() { this.src = '/unihelper/views/assets/default-pfp.png'; };
        card.querySelector('.mod-reporter-name').textContent = report.reporter_name || 'Unknown';

        reporterEl.style.cursor = 'pointer';
        reporterEl.addEventListener('click', (e) => {
            e.stopPropagation();
            if (report.reporter_id) {
                window.open('/unihelper/view/profile/' + report.reporter_id, '_blank');
            }
        });

        // Actions
        const actionsContainer = card.querySelector('.mod-report-actions');

        if (tabType === 'pending') {
            // Ignore, Flag, Forward
            const ignoreBtn = makeActionBtn('Ignore', 'action-ignore', async () => {
                await takeAction(report.report_id, 'ignored', card);
            });
            const flagBtn = makeActionBtn('Flag', 'action-flag', async () => {
                const ok = await window.confirm('Flag this content? It will be hidden from public view.');
                if (!ok) return;
                await takeAction(report.report_id, 'flagged', card);
            });
            const forwardBtn = makeActionBtn('Forward', 'action-forward', async () => {
                const ok = await window.confirm('Forward this report to the administrator?');
                if (!ok) return;
                await takeAction(report.report_id, 'forwarded to admin', card);
            });
            actionsContainer.append(ignoreBtn, flagBtn, forwardBtn);

        } else if (tabType === 'resolved') {
            // Delete
            const deleteBtn = makeActionBtn('Delete', 'action-delete', async () => {
                const ok = await window.confirm('Permanently delete this report record?');
                if (!ok) return;
                await deleteReport(report.report_id, card);
            });
            actionsContainer.append(deleteBtn);

        } else if (tabType === 'forwarded') {
            // Read-only indicator
            const tag = document.createElement('span');
            tag.className = 'mod-report-type-badge';
            tag.textContent = 'Escalated';
            tag.style.background = 'rgba(255, 165, 2, 0.12)';
            tag.style.color = '#ffa502';
            actionsContainer.append(tag);
        }

        return clone;
    }

    function makeActionBtn(label, className, onClick) {
        const btn = document.createElement('button');
        btn.className = 'mod-action-btn ' + className;
        btn.textContent = label;
        btn.addEventListener('click', onClick);
        return btn;
    }

    // API: take action
    async function takeAction(reportId, action, cardEl) {
        const btns = cardEl.querySelectorAll('.mod-action-btn');
        btns.forEach(b => b.disabled = true);

        try {
            const formData = new URLSearchParams();
            formData.append('report_id', reportId);
            formData.append('report_action', action);

            const res = await fetch(API + 'takeAction', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });
            const result = await res.json();

            if (res.ok && result.success) {
                if (typeof showToast === 'function') showToast(result.message || 'Done.', 'success');
                cardEl.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                cardEl.style.opacity = '0';
                cardEl.style.transform = 'translateX(30px)';
                setTimeout(() => {
                    cardEl.remove();
                    checkEmpty('pending');
                }, 300);
                // Refresh the target tab in background
                if (action === 'forwarded to admin') loadReports('forwarded');
                else loadReports('resolved');
            } else {
                if (typeof showToast === 'function') showToast(result.message || 'Action failed.', 'error');
                btns.forEach(b => b.disabled = false);
            }
        } catch (err) {
            if (typeof showToast === 'function') showToast('Network error.', 'error');
            btns.forEach(b => b.disabled = false);
        }
    }

    // API: delete report
    async function deleteReport(reportId, cardEl) {
        const btns = cardEl.querySelectorAll('.mod-action-btn');
        btns.forEach(b => b.disabled = true);

        try {
            const formData = new URLSearchParams();
            formData.append('report_id', reportId);

            const res = await fetch(API + 'deleteReport', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });
            const result = await res.json();

            if (res.ok && result.success) {
                if (typeof showToast === 'function') showToast('Report deleted.', 'success');
                cardEl.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                cardEl.style.opacity = '0';
                cardEl.style.transform = 'translateX(30px)';
                setTimeout(() => {
                    cardEl.remove();
                    checkEmpty('resolved');
                }, 300);
            } else {
                if (typeof showToast === 'function') showToast(result.message || 'Delete failed.', 'error');
                btns.forEach(b => b.disabled = false);
            }
        } catch (err) {
            if (typeof showToast === 'function') showToast('Network error.', 'error');
            btns.forEach(b => b.disabled = false);
        }
    }

    // Check empty state
    function checkEmpty(tabName) {
        const list = document.getElementById(tabName + 'List');
        const empty = document.getElementById(tabName + 'Empty');
        if (!list || !empty) return;
        const count = list.querySelectorAll('.mod-report-card').length;
        empty.style.display = count > 0 ? 'none' : 'flex';
        updateCount(tabName, count);
    }

    // Update count badge
    function updateCount(tabName, count) {
        const badge = document.getElementById(tabName + 'Count');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline-flex';
        } else {
            badge.style.display = 'none';
        }
    }

    // Load reports for a tab
    async function loadReports(tabName) {
        const endpointMap = {
            pending: 'getPendingReports',
            resolved: 'getResolvedReports',
            forwarded: 'getForwardedReports'
        };

        const list = document.getElementById(tabName + 'List');
        const empty = document.getElementById(tabName + 'Empty');
        if (!list) return;

        try {
            const res = await fetch(API + endpointMap[tabName]);
            if (!res.ok) throw new Error('Bad response');
            const data = await res.json();

            list.innerHTML = '';

            if (data.success && data.data && data.data.length > 0) {
                empty.style.display = 'none';
                data.data.forEach(report => {
                    list.appendChild(createReportCard(report, tabName));
                });
                updateCount(tabName, data.data.length);
            } else {
                empty.style.display = 'flex';
                updateCount(tabName, 0);
            }
        } catch (err) {
            console.error(`Failed to load ${tabName} reports:`, err);
            empty.style.display = 'flex';
            updateCount(tabName, 0);
        }
    }

    // Initial load all tabs
    await Promise.all([
        loadReports('pending'),
        loadReports('resolved'),
        loadReports('forwarded')
    ]);
});
