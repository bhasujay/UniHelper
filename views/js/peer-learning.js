/**
 * Peer Learning — client-side logic.
 * Uses hidden HTML templates from peer-learning.php (clones + populates).
 * Modals are moved to document.body to escape main-content clipping.
 */

// ────────────────────────────────────────────────────────
// CONSTANTS
// ────────────────────────────────────────────────────────
var PEER_BASE       = '/UniHelper';
var PEER_API        = PEER_BASE + '/api?controller=SessionController&action=';
var PEER_DEFAULT_PFP = '/unihelper/views/assets/default-pfp.png';
var PEER_PAGE_SIZE  = 12;
var PEER_USER_ID    = 0;

// ────────────────────────────────────────────────────────
// STATE
// ────────────────────────────────────────────────────────
var peerCurrentTab   = 'all-sessions';
var peerPages        = { 'all-sessions': 1, 'my-sessions': 1 };
var peerSearchTimer  = null;
var peerSearchQuery  = '';
var peerActiveDetail = 0;

// ────────────────────────────────────────────────────────
// DOM REFS (set in DOMContentLoaded)
// ────────────────────────────────────────────────────────
var peerFeedView, peerDetailView, peerDetailCard;
var peerSearchInput, peerCreateBtn;
var peerCreateModal, peerCreateModalTitle, peerCreateModalBody, peerCreateModalClose;
var tplCard, tplTag, tplSubscriber, tplEmpty;


document.addEventListener('DOMContentLoaded', function () {
    PEER_USER_ID = parseInt(document.getElementById('profileUserId')?.textContent || '0', 10);

    peerFeedView   = document.getElementById('peerFeedView');
    peerDetailView = document.getElementById('peerDetailView');
    peerDetailCard = document.getElementById('peerDetailCard');
    peerSearchInput = document.getElementById('peerSearchInput');
    peerCreateBtn   = document.getElementById('peerCreateBtn');

    // Move modal to document.body so it escapes main-content clipping
    peerCreateModal      = document.getElementById('peerCreateModal');
    peerCreateModalTitle = document.getElementById('peerCreateModalTitle');
    peerCreateModalBody  = document.getElementById('peerCreateModalBody');
    peerCreateModalClose = document.getElementById('peerCreateModalClose');
    peerCreateModal.parentElement.removeChild(peerCreateModal);
    document.body.appendChild(peerCreateModal);

    // Grab templates
    tplCard       = document.getElementById('tpl-peer-card');
    tplTag        = document.getElementById('tpl-peer-tag');
    tplSubscriber = document.getElementById('tpl-subscriber-item');
    tplEmpty      = document.getElementById('tpl-peer-empty');

    // ── EVENT: Tab switching ──
    document.querySelectorAll('.peer-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tab = btn.dataset.tab;
            if (tab === peerCurrentTab) return;
            peerSwitchTab(tab);
        });
    });

    // ── EVENT: Load more ──
    document.querySelectorAll('.peer-load-more button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tab = btn.closest('.peer-panel').id.replace('panel-', '');
            peerLoadTab(tab, peerPages[tab] || 1);
        });
    });

    // ── EVENT: Search ──
    peerSearchInput.addEventListener('input', function () {
        clearTimeout(peerSearchTimer);
        peerSearchTimer = setTimeout(function () {
            var q = peerSearchInput.value.trim();
            if (q === peerSearchQuery) return;
            peerSearchQuery = q;
            peerPages['all-sessions'] = 1;
            peerPages['my-sessions'] = 1;
            peerLoadTab(peerCurrentTab, 1);
        }, 400);
    });

    // ── EVENT: Card click → detail ──
    document.addEventListener('click', function (e) {
        var card = e.target.closest('.peer-card:not(.template)');
        if (!card) return;
        if (e.target.closest('a')) return; // let profile links through
        var id = parseInt(card.dataset.sessionId, 10);
        if (id > 0) peerOpenDetail(id);
    });

    // ── EVENT: Create button ──
    peerCreateBtn.addEventListener('click', function () { peerOpenCreateModal(0); });
    peerCreateModalClose.addEventListener('click', peerCloseCreateModal);
    peerCreateModal.addEventListener('click', function (e) {
        if (e.target === peerCreateModal) peerCloseCreateModal();
    });

    // ── EVENT: Detail back button ──
    document.getElementById('peerDetailBack').addEventListener('click', peerCloseDetail);

    // ── EVENT: Detail action buttons (delegated) ──
    peerDetailCard.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;
        peerHandleDetailAction(btn.dataset.action, parseInt(btn.dataset.id, 10));
    });

    // ── EVENT: Subscriber approve/reject (delegated) ──
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-sub-action]');
        if (!btn) return;
        var action = btn.dataset.subAction === 'approve' ? 'approveSubscriber' : 'rejectSubscriber';
        var sessionId    = parseInt(btn.dataset.session, 10);
        var subscriberId = parseInt(btn.dataset.subscriber, 10);
        btn.disabled = true;
        peerApiPost(action, { session_id: sessionId, subscriber_id: subscriberId }).then(function (res) {
            if (!res.success) { showToast(res.message || 'Error', 'error'); btn.disabled = false; return; }
            showToast(res.message, 'success');
            peerLoadSubscribers(sessionId);
        });
    });

    // Load default tab
    peerLoadTab('all-sessions', 1);

    // Deep-link: ?session=ID
    var pageParams = {};
    try { pageParams = JSON.parse(document.getElementById('dashboardMain')?.dataset?.pageParams || '{}'); } catch (_) {}
    var deepId = parseInt(pageParams.session || '0', 10);
    if (deepId > 0) peerOpenDetail(deepId);

    // Cleanup on unload
    window.addEventListener('beforeunload', function () {
        if (peerCreateModal && peerCreateModal.parentElement) peerCreateModal.remove();
    });
});


// ════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════

function peerProfileUrl(userId) {
    return PEER_BASE + '/view/profile/' + userId;
}

function peerPfp(pic) {
    return pic ? '/unihelper/public/' + pic : PEER_DEFAULT_PFP;
}

function peerFormatDate(dateStr) {
    if (!dateStr) return '';
    var d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function peerFormatTime(dateStr) {
    if (!dateStr) return '';
    var d = new Date(dateStr);
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

function peerDurationLabel(min) {
    min = parseInt(min, 10);
    if (min < 60) return min + ' min';
    var h = Math.floor(min / 60);
    var m = min % 60;
    return h + 'h' + (m > 0 ? ' ' + m + 'm' : '');
}

function peerIsOwner(session) {
    return parseInt(session.user_id, 10) === PEER_USER_ID;
}

function peerFullName(s) {
    return ((s.first_name || '') + ' ' + (s.last_name || '')).trim();
}


// ════════════════════════════════════════════════════════
// API
// ════════════════════════════════════════════════════════

function peerApiFetch(action) {
    return fetch(PEER_API + action, { credentials: 'same-origin' }).then(function (r) { return r.json(); });
}

function peerApiPost(action, body) {
    var form = new FormData();
    if (body) Object.keys(body).forEach(function (k) { if (body[k] !== null && body[k] !== undefined) form.append(k, body[k]); });
    return fetch(PEER_API + action, { method: 'POST', credentials: 'same-origin', body: form }).then(function (r) { return r.json(); });
}


// ════════════════════════════════════════════════════════
// TEMPLATE RENDERING (clone + populate, no innerHTML strings)
// ════════════════════════════════════════════════════════

var AUDIENCE_MAP = {
    'public':          { cls: 'peer-badge-public',     text: '🌐 Public' },
    'university_only': { cls: 'peer-badge-university',  text: '🏫 University' },
    'private':         { cls: 'peer-badge-private',     text: '🔒 Private' },
};
var STATUS_MAP = {
    'ongoing':   { cls: 'peer-badge-ongoing',   text: '🟢 LIVE' },
    'scheduled': { cls: 'peer-badge-scheduled',  text: '⏳ Upcoming' },
    'completed': { cls: 'peer-badge-completed',  text: '✅ Completed' },
    'cancelled': { cls: 'peer-badge-cancelled',  text: '✖ Cancelled' },
};

function peerRenderCard(session) {
    var card = tplCard.cloneNode(true);
    card.removeAttribute('id');
    card.classList.remove('template');
    card.style.display = '';
    card.dataset.sessionId = session.id;

    // Badges
    var aud = AUDIENCE_MAP[session.audience] || {};
    var stat = STATUS_MAP[session.status] || {};
    var audBadge = card.querySelector('.js-audience-badge');
    audBadge.textContent = aud.text || '';
    audBadge.className = 'peer-badge ' + (aud.cls || '');
    var statBadge = card.querySelector('.js-status-badge');
    statBadge.textContent = stat.text || '';
    statBadge.className = 'peer-badge ' + (stat.cls || '');

    // Major
    var majorEl = card.querySelector('.js-major');
    if (session.major_name) { majorEl.textContent = session.major_name; }
    else { majorEl.style.display = 'none'; }

    // Title, desc
    card.querySelector('.js-title').textContent = session.title || '';
    card.querySelector('.js-desc').textContent = session.description || '';

    // Meta
    card.querySelector('.js-date-text').textContent = peerFormatDate(session.scheduled_at);
    card.querySelector('.js-time-text').textContent = peerFormatTime(session.scheduled_at);
    card.querySelector('.js-duration-text').textContent = peerDurationLabel(session.duration_minutes);

    // Tags
    var tagsContainer = card.querySelector('.js-tags');
    var tagsStr = session.tags || '';
    if (tagsStr.trim()) {
        tagsStr.split(',').forEach(function (t) {
            t = t.trim();
            if (!t) return;
            var tagEl = document.getElementById('tpl-peer-tag').content.cloneNode(true).firstElementChild;
            tagEl.textContent = t;
            tagsContainer.appendChild(tagEl);
        });
    } else {
        tagsContainer.style.display = 'none';
    }

    // Author
    var authorLink = card.querySelector('.js-author-link');
    authorLink.href = peerProfileUrl(session.user_id);
    card.querySelector('.js-author-avatar').src = peerPfp(session.profile_picture);
    var nameEl = card.querySelector('.js-author-name');
    nameEl.textContent = peerFullName(session);
    nameEl.href = peerProfileUrl(session.user_id);
    card.querySelector('.js-author-uni').textContent = session.university_name || '';

    // Subs
    card.querySelector('.js-subs-count').textContent = parseInt(session.sub_count, 10) || 0;

    return card;
}

function peerRenderEmpty(title, subtitle) {
    var el = tplEmpty.cloneNode(true);
    el.removeAttribute('id');
    el.classList.remove('template');
    el.style.display = '';
    el.querySelector('.js-empty-title').textContent = title;
    el.querySelector('.js-empty-subtitle').textContent = subtitle;
    return el;
}

function peerRenderSubscriberItem(sub, sessionId) {
    var item = tplSubscriber.cloneNode(true);
    item.removeAttribute('id');
    item.classList.remove('template');
    item.style.display = '';
    item.dataset.subId = sub.subscriber_id;

    item.querySelector('.js-sub-profile-link').href = peerProfileUrl(sub.subscriber_id);
    item.querySelector('.js-sub-avatar').src = peerPfp(sub.profile_picture);
    var nameLink = item.querySelector('.js-sub-name');
    nameLink.textContent = ((sub.first_name || '') + ' ' + (sub.last_name || '')).trim();
    nameLink.href = peerProfileUrl(sub.subscriber_id);
    item.querySelector('.js-sub-time').textContent = peerFormatDate(sub.requested_at);

    var statusEl = item.querySelector('.js-sub-status');
    statusEl.textContent = sub.status;
    statusEl.className = 'peer-subscriber-status peer-subscriber-status-' + sub.status;

    // Show approve/reject for pending
    if (sub.status === 'pending') {
        var actionsDiv = item.querySelector('.js-sub-actions');
        actionsDiv.style.display = 'flex';
        actionsDiv.querySelector('.js-btn-approve').dataset.subAction = 'approve';
        actionsDiv.querySelector('.js-btn-approve').dataset.session = sessionId;
        actionsDiv.querySelector('.js-btn-approve').dataset.subscriber = sub.subscriber_id;
        actionsDiv.querySelector('.js-btn-reject').dataset.subAction = 'reject';
        actionsDiv.querySelector('.js-btn-reject').dataset.session = sessionId;
        actionsDiv.querySelector('.js-btn-reject').dataset.subscriber = sub.subscriber_id;
    }

    return item;
}


// ════════════════════════════════════════════════════════
// TAB LOGIC
// ════════════════════════════════════════════════════════

function peerSwitchTab(tab) {
    peerCurrentTab = tab;
    document.querySelectorAll('.peer-tab').forEach(function (t) { t.classList.remove('active'); });
    document.querySelector('.peer-tab[data-tab="' + tab + '"]')?.classList.add('active');
    document.querySelectorAll('.peer-panel').forEach(function (p) { p.classList.remove('active'); });
    document.getElementById('panel-' + tab)?.classList.add('active');

    var grid = document.getElementById('grid-' + tab);
    if (!grid.children.length) peerLoadTab(tab, 1);
}

function peerLoadTab(tab, page) {
    var grid    = document.getElementById('grid-' + tab);
    var loading = document.getElementById('loading-' + tab);
    var more    = document.getElementById('more-' + tab);

    if (page === 1) { grid.innerHTML = ''; loading.style.display = 'flex'; }

    var action;
    if (peerSearchQuery.length >= 2) {
        action = 'searchSessions&query=' + encodeURIComponent(peerSearchQuery) + '&tab=' + tab + '&page=' + page;
    } else if (tab === 'all-sessions') {
        action = 'getAllSessions&page=' + page;
    } else if (tab === 'my-sessions') {
        action = 'getMySessions&page=' + page;
    } else {
        action = 'getSubscribedSessions';
    }

    peerApiFetch(action).then(function (res) {
        loading.style.display = 'none';
        if (!res.success) { showToast(res.message || 'Failed to load', 'error'); return; }

        var sessions = res.data || [];
        if (sessions.length === 0 && page === 1) {
            var emptyTitle = tab === 'my-sessions' ? 'No sessions yet' : tab === 'subscribed-sessions' ? 'No subscriptions' : 'No sessions available';
            var emptySub   = tab === 'my-sessions' ? 'Create your first study session!' : 'Browse sessions and subscribe!';
            grid.appendChild(peerRenderEmpty(emptyTitle, emptySub));
        }

        sessions.forEach(function (s) { grid.appendChild(peerRenderCard(s)); });
        if (more) more.style.display = (res.count >= PEER_PAGE_SIZE) ? 'block' : 'none';
        if (tab !== 'subscribed-sessions') peerPages[tab] = page + 1;
    }).catch(function (err) {
        loading.style.display = 'none';
        showToast('Failed to load sessions', 'error');
    });
}


// ════════════════════════════════════════════════════════
// DETAIL VIEW
// ════════════════════════════════════════════════════════

function peerOpenDetail(sessionId) {
    peerActiveDetail = sessionId;
    peerFeedView.style.display = 'none';
    peerDetailView.style.display = 'block';
    peerDetailCard.innerHTML = '';

    var loadingEl = document.createElement('div');
    loadingEl.className = 'peer-loading';
    var loadingSpan = document.createElement('span');
    loadingSpan.textContent = 'Loading session...';
    loadingEl.appendChild(loadingSpan);
    peerDetailCard.appendChild(loadingEl);

    peerApiFetch('getSession&id=' + sessionId).then(function (res) {
        if (!res.success || !res.data) { peerDetailCard.innerHTML = ''; peerDetailCard.appendChild(peerRenderEmpty('Error', res.message || 'Session not found.')); return; }
        peerRenderDetail(res.data);
    }).catch(function () {
        peerDetailCard.innerHTML = '';
        peerDetailCard.appendChild(peerRenderEmpty('Error', 'Failed to load session.'));
    });
}

function peerCloseDetail() {
    peerActiveDetail = 0;
    peerDetailView.style.display = 'none';
    peerFeedView.style.display = '';
}

function peerRenderDetail(s) {
    var frag = document.createDocumentFragment();
    var owner = peerIsOwner(s);
    var subStatus = s.subscription_status || 'none';
    var isPrivate = s.audience === 'private';
    var isActive  = s.status === 'scheduled' || s.status === 'ongoing';

    // Badges row
    var badgesDiv = document.createElement('div');
    badgesDiv.className = 'peer-detail-badges';
    var aud = AUDIENCE_MAP[s.audience] || {};
    var stat = STATUS_MAP[s.status] || {};
    var audSpan = document.createElement('span');
    audSpan.className = 'peer-badge ' + (aud.cls || '');
    audSpan.textContent = aud.text || '';
    badgesDiv.appendChild(audSpan);
    var statSpan = document.createElement('span');
    statSpan.className = 'peer-badge ' + (stat.cls || '');
    statSpan.textContent = stat.text || '';
    badgesDiv.appendChild(statSpan);
    frag.appendChild(badgesDiv);

    // Major
    if (s.major_name) {
        var majorDiv = document.createElement('div');
        majorDiv.className = 'peer-card-major';
        majorDiv.style.marginBottom = '0.5rem';
        majorDiv.textContent = s.major_name;
        frag.appendChild(majorDiv);
    }

    // Title
    var titleH2 = document.createElement('h2');
    titleH2.className = 'peer-detail-title';
    titleH2.textContent = s.title;
    frag.appendChild(titleH2);

    // Description
    var descDiv = document.createElement('div');
    descDiv.className = 'peer-detail-desc';
    descDiv.textContent = s.description;
    frag.appendChild(descDiv);

    // Meta row
    var metaDiv = document.createElement('div');
    metaDiv.className = 'peer-detail-meta';
    var metaItems = [
        { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>', text: peerFormatDate(s.scheduled_at) },
        { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>', text: peerFormatTime(s.scheduled_at) },
        { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 22h14"></path><path d="M5 2h14"></path><path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"></path><path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"></path></svg>', text: peerDurationLabel(s.duration_minutes) },
        { icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>', text: (parseInt(s.sub_count,10)||0) + ' subscriber' + (parseInt(s.sub_count,10)!==1?'s':'') },
    ];
    metaItems.forEach(function (mi) {
        var span = document.createElement('span');
        span.className = 'peer-detail-meta-item';
        span.innerHTML = mi.icon;
        var txt = document.createElement('span');
        txt.textContent = mi.text;
        span.appendChild(txt);
        metaDiv.appendChild(span);
    });
    frag.appendChild(metaDiv);

    // Tags
    var tagsStr = s.tags || '';
    if (tagsStr.trim()) {
        var tagsDiv = document.createElement('div');
        tagsDiv.className = 'peer-detail-tags';
        tagsStr.split(',').forEach(function (t) {
            t = t.trim();
            if (!t) return;
            var tagSpan = document.createElement('span');
            tagSpan.className = 'peer-card-tag';
            tagSpan.textContent = t;
            tagsDiv.appendChild(tagSpan);
        });
        frag.appendChild(tagsDiv);
    }

    // Author section
    var authorDiv = document.createElement('div');
    authorDiv.className = 'peer-detail-author';
    var authorAnchor = document.createElement('a');
    authorAnchor.href = peerProfileUrl(s.user_id);
    var authorImg = document.createElement('img');
    authorImg.className = 'peer-detail-author-avatar';
    authorImg.src = peerPfp(s.profile_picture);
    authorAnchor.appendChild(authorImg);
    authorDiv.appendChild(authorAnchor);
    var authorInfo = document.createElement('div');
    authorInfo.className = 'peer-detail-author-info';
    var authorName = document.createElement('a');
    authorName.className = 'peer-detail-author-name';
    authorName.href = peerProfileUrl(s.user_id);
    authorName.textContent = peerFullName(s);
    authorInfo.appendChild(authorName);
    var authorUni = document.createElement('span');
    authorUni.className = 'peer-detail-author-uni';
    authorUni.textContent = s.university_name || '';
    authorInfo.appendChild(authorUni);
    authorDiv.appendChild(authorInfo);
    frag.appendChild(authorDiv);

    // Action buttons
    if (isActive) {
        var actionsDiv = document.createElement('div');
        actionsDiv.className = 'peer-detail-actions';

        if (owner) {
            actionsDiv.appendChild(peerMakeBtn('Edit', 'btn btn-edit-session', 'edit', s.id));
            actionsDiv.appendChild(peerMakeBtn('Cancel Session', 'btn btn-danger', 'delete', s.id));
        } else {
            if (subStatus === 'none' || subStatus === 'rejected') {
                actionsDiv.appendChild(peerMakeBtn(isPrivate ? 'Request to Join' : 'Subscribe', 'btn btn-subscribe', 'subscribe', s.id));
            } else if (subStatus === 'pending') {
                var pendingBtn = document.createElement('button');
                pendingBtn.className = 'btn btn-pending';
                pendingBtn.disabled = true;
                pendingBtn.textContent = '⏳ Pending Approval';
                actionsDiv.appendChild(pendingBtn);
                actionsDiv.appendChild(peerMakeBtn('Withdraw Request', 'btn btn-unsubscribe', 'unsubscribe', s.id));
            } else if (subStatus === 'approved') {
                actionsDiv.appendChild(peerMakeBtn('Unsubscribe', 'btn btn-unsubscribe', 'unsubscribe', s.id));
            }

            // Join button: public if approved, private if approved
            if (subStatus === 'approved' && s.session_link) {
                var joinLink = document.createElement('a');
                joinLink.className = 'btn btn-join';
                joinLink.href = s.session_link;
                joinLink.target = '_blank';
                joinLink.rel = 'noopener';
                joinLink.textContent = 'Join Session';
                actionsDiv.appendChild(joinLink);
            }
        }

        frag.appendChild(actionsDiv);
    }

    // Subscriber section (for author)
    if (owner) {
        var subSection = document.createElement('div');
        subSection.id = 'peerSubscribersSection';
        frag.appendChild(subSection);
    }

    peerDetailCard.innerHTML = '';
    peerDetailCard.appendChild(frag);

    if (owner) peerLoadSubscribers(s.id);
}

function peerMakeBtn(text, cls, action, id) {
    var btn = document.createElement('button');
    btn.className = cls;
    btn.textContent = text;
    btn.dataset.action = action;
    btn.dataset.id = id;
    return btn;
}


// ════════════════════════════════════════════════════════
// DETAIL ACTIONS
// ════════════════════════════════════════════════════════

function peerHandleDetailAction(action, id) {
    if (action === 'subscribe') {
        peerApiPost('subscribe', { session_id: id }).then(function (res) {
            if (!res.success) { showToast(res.message, 'error'); return; }
            showToast(res.message || 'Subscribed!', 'success');
            peerOpenDetail(id);
        });
    }

    if (action === 'unsubscribe') {
        window.confirm('Are you sure you want to unsubscribe?').then(function (ok) {
            if (!ok) return;
            peerApiPost('unsubscribe', { session_id: id }).then(function (res) {
                if (!res.success) { showToast(res.message, 'error'); return; }
                showToast('Unsubscribed.', 'success');
                peerOpenDetail(id);
            });
        });
    }

    if (action === 'edit') {
        peerOpenCreateModal(id);
    }

    if (action === 'delete') {
        window.confirm('Cancel this session? All subscribers will be notified.').then(function (ok) {
            if (!ok) return;
            peerApiPost('deleteSession', { id: id }).then(function (res) {
                if (!res.success) { showToast(res.message, 'error'); return; }
                showToast('Session cancelled.', 'success');
                peerCloseDetail();
                peerPages['my-sessions'] = 1;
                peerPages['all-sessions'] = 1;
                peerLoadTab('my-sessions', 1);
                peerLoadTab('all-sessions', 1);
            });
        });
    }
}


// ════════════════════════════════════════════════════════
// SUBSCRIBERS LIST (Author detail view)
// ════════════════════════════════════════════════════════

function peerLoadSubscribers(sessionId) {
    var container = document.getElementById('peerSubscribersSection');
    if (!container) return;

    peerApiFetch('getSubscribers&session_id=' + sessionId).then(function (res) {
        if (!res.success) return;
        container.innerHTML = '';

        var subs = res.data || [];
        var section = document.createElement('div');
        section.className = 'peer-subscribers-section';

        var title = document.createElement('h3');
        title.className = 'peer-subscribers-title';
        title.textContent = 'Subscribers ';
        var countSpan = document.createElement('span');
        countSpan.textContent = '(' + subs.length + ')';
        title.appendChild(countSpan);
        section.appendChild(title);

        if (subs.length === 0) {
            section.appendChild(peerRenderEmpty('No subscribers yet', ''));
        } else {
            var list = document.createElement('div');
            list.className = 'peer-subscriber-list';
            subs.forEach(function (sub) {
                list.appendChild(peerRenderSubscriberItem(sub, sessionId));
            });
            section.appendChild(list);
        }

        container.appendChild(section);
    });
}


// ════════════════════════════════════════════════════════
// CREATE / EDIT MODAL
// ════════════════════════════════════════════════════════

function peerOpenCreateModal(sessionId) {
    sessionId = sessionId || 0;
    peerCreateModal.style.display = 'flex';
    peerCreateModalTitle.textContent = sessionId > 0 ? 'Edit Session' : 'Create Session';
    peerCreateModalBody.textContent = '';
    var loadEl = document.createElement('div');
    loadEl.className = 'peer-loading';
    var loadSpan = document.createElement('span');
    loadSpan.textContent = 'Loading form...';
    loadEl.appendChild(loadSpan);
    peerCreateModalBody.appendChild(loadEl);

    var query = sessionId > 0 ? '&session_id=' + sessionId : '';
    peerApiFetch('getSessionForm' + query).then(function (res) {
        if (!res.success) { peerCreateModalBody.textContent = res.message || 'Failed to load form.'; return; }
        peerCreateModalTitle.textContent = res.data.title || peerCreateModalTitle.textContent;
        peerCreateModalBody.innerHTML = res.data.html;
        peerBindFormEvents();
    }).catch(function () {
        peerCreateModalBody.textContent = 'Failed to load form.';
    });
}

function peerCloseCreateModal() {
    peerCreateModal.style.display = 'none';
    peerCreateModalBody.innerHTML = '';
}

function peerBindFormEvents() {
    var form = peerCreateModalBody.querySelector('form');
    if (!form) return;

    // Cancel
    var cancelBtn = form.querySelector('.js-cancel-session-form');
    if (cancelBtn) cancelBtn.addEventListener('click', peerCloseCreateModal);

    // Audience radio visual toggle
    form.querySelectorAll('.audience-option input[type="radio"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            form.querySelectorAll('.audience-option').forEach(function (o) { o.classList.remove('selected'); });
            radio.closest('.audience-option')?.classList.add('selected');
        });
    });

    // Submit
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var submitBtn = form.querySelector('.js-submit-session-form');
        if (submitBtn.disabled) return;
        submitBtn.disabled = true;
        var origText = submitBtn.textContent;
        submitBtn.textContent = 'Saving...';

        var fd = new FormData(form);
        fetch(PEER_API + 'submitSession', { method: 'POST', credentials: 'same-origin', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) {
                    if (res.errors) {
                        Object.keys(res.errors).forEach(function (field) {
                            var input = form.querySelector('[name="' + field + '"]');
                            var group = input ? input.closest('.session-form-group') : null;
                            if (group) {
                                group.classList.add('has-error');
                                var errEl = group.querySelector('.field-error');
                                if (!errEl) { errEl = document.createElement('span'); errEl.className = 'field-error'; group.appendChild(errEl); }
                                errEl.textContent = res.errors[field];
                            }
                        });
                        submitBtn.disabled = false;
                        submitBtn.textContent = origText;
                        return;
                    }
                    throw new Error(res.message);
                }
                peerCloseCreateModal();
                showToast(res.message || 'Session saved!', 'success');
                peerPages['my-sessions'] = 1;
                peerPages['all-sessions'] = 1;
                peerLoadTab('my-sessions', 1);
                peerLoadTab('all-sessions', 1);

                // Refresh detail if editing the active one
                var saved = res.data?.session;
                if (saved && peerActiveDetail === parseInt(saved.id, 10)) {
                    peerRenderDetail(saved);
                }
            })
            .catch(function (err) {
                showToast(err.message || 'Failed to save session.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = origText;
            });
    });
}
