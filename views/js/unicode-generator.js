(function () {
    'use strict';

    const APP_BASE = getAppBasePath();
    const API_BASE = APP_BASE + '/api?controller=UnicodeGeneratorController';

    const state = {
        programs: [],
        suggestedOrderIds: [],
        loading: false,
        hasUnsavedChanges: false,
        hasSavedOrder: false,
        lastLoadedAt: null
    };

    const ui = {
        root: null,
        list: null,
        emptyState: null,
        loadingState: null,
        statusText: null,
        count: null,
        selectedProgram: null,
        selectedUnicode: null,
        selectedChance: null,
        changeState: null,
        lastRefresh: null,
        refreshBtn: null,
        autoSortBtn: null,
        resetSuggestedBtn: null,
        saveBtn: null,
        clearSavedBtn: null,
        pdfBtn: null
    };

    let draggedItem = null;

    document.addEventListener('DOMContentLoaded', initUnicodeGenerator);

    function initUnicodeGenerator() {
        const dashboardMain = document.getElementById('dashboardMain');
        if (!dashboardMain || dashboardMain.dataset.component !== 'unicode-generator') {
            return;
        }

        ui.root = document.getElementById('unicodeGeneratorCard');
        ui.list = document.getElementById('unicodePreferenceList');
        ui.emptyState = document.getElementById('unicodeEmptyState');
        ui.loadingState = document.getElementById('unicodeLoadingState');
        ui.statusText = document.getElementById('unicodeStatusText');
        ui.count = document.getElementById('unicodeEligibleCount');
        ui.selectedProgram = document.getElementById('unicodeSelectedProgram');
        ui.selectedUnicode = document.getElementById('unicodeSelectedUnicode');
        ui.selectedChance = document.getElementById('unicodeSelectedChance');
        ui.changeState = document.getElementById('unicodeChangeState');
        ui.lastRefresh = document.getElementById('unicodeLastRefresh');

        ui.refreshBtn = document.getElementById('unicodeRefreshBtn');
        ui.autoSortBtn = document.getElementById('unicodeAutoSortBtn');
        ui.resetSuggestedBtn = document.getElementById('unicodeResetSuggestedBtn');
        ui.saveBtn = document.getElementById('unicodeSaveBtn');
        ui.clearSavedBtn = document.getElementById('unicodeClearSavedBtn');
        ui.pdfBtn = document.getElementById('unicodePdfBtn');

        bindEvents();
        setDirtyFlag(false);
        updateActionButtons();
        updateLastRefreshLabel();
        loadPreferencePrograms();
    }

    function bindEvents() {
        if (!ui.list) {
            return;
        }

        if (ui.refreshBtn) {
            ui.refreshBtn.addEventListener('click', loadPreferencePrograms);
        }

        if (ui.autoSortBtn) {
            ui.autoSortBtn.addEventListener('click', function () {
                if (state.programs.length === 0) {
                    notify('No programs available to sort.', 'error');
                    return;
                }

                state.programs.sort(compareProgramsByRecommendation);
                updateCurrentRanks();
                renderPreferenceList();
                updateSummary();
                setDirtyFlag(true);
                notify('Sorted by recommendation score.', 'success');
            });
        }

        if (ui.resetSuggestedBtn) {
            ui.resetSuggestedBtn.addEventListener('click', function () {
                if (state.suggestedOrderIds.length === 0) {
                    notify('No suggested order available yet.', 'error');
                    return;
                }

                applySuggestedOrder();
                updateCurrentRanks();
                renderPreferenceList();
                updateSummary();
                setDirtyFlag(true);
                notify('Reset to suggested ranking.', 'success');
            });
        }

        if (ui.saveBtn) {
            ui.saveBtn.addEventListener('click', savePreferenceOrder);
        }

        if (ui.clearSavedBtn) {
            ui.clearSavedBtn.addEventListener('click', clearSavedOrder);
        }

        if (ui.pdfBtn) {
            ui.pdfBtn.addEventListener('click', printPreferenceList);
        }

        ui.list.addEventListener('click', onListClick);
        ui.list.addEventListener('dragover', onDragOver);
        ui.list.addEventListener('drop', onDrop);
    }

    async function loadPreferencePrograms() {
        setLoading(true);

        try {
            const result = await fetchJson(API_BASE + '&action=getPreferencePrograms');

            if (!result.success) {
                throw new Error(result.message || 'Failed to load preference programs');
            }

            const programs = Array.isArray(result.data && result.data.programs)
                ? result.data.programs
                : [];

            state.programs = programs.map(normalizeProgram);
            state.hasSavedOrder = Boolean(result.data && result.data.has_saved_order);
            state.suggestedOrderIds = [...state.programs]
                .sort(function (a, b) {
                    return a.suggested_rank - b.suggested_rank;
                })
                .map(function (program) {
                    return program.program_id;
                });

            updateCurrentRanks();
            renderPreferenceList();
            updateSummary();
            setDirtyFlag(false);
            state.lastLoadedAt = new Date();
            updateLastRefreshLabel();

            if (result.data && result.data.has_saved_order) {
                notify('Loaded your saved preference order.', 'success');
            }
        } catch (error) {
            console.error('Unicode generator load error:', error);
            notify(error.message || 'Failed to load preference programs.', 'error');
            state.programs = [];
            state.hasSavedOrder = false;
            setDirtyFlag(false);
            renderPreferenceList();
            updateSummary();
        } finally {
            setLoading(false);
        }
    }

    function normalizeProgram(program) {
        const eligibility = String(program.eligibility || '').toLowerCase();

        return {
            program_id: Number(program.program_id),
            name: String(program.name || 'Unknown Program'),
            university: String(program.university || '-'),
            unicode: program.unicode ? String(program.unicode) : '-',
            eligibility: eligibility,
            probability_percent: program.probability_percent !== null && program.probability_percent !== undefined
                ? Number(program.probability_percent)
                : null,
            recommendation_score: program.recommendation_score !== null && program.recommendation_score !== undefined
                ? Number(program.recommendation_score)
                : 0,
            major_match: Boolean(program.major_match),
            suggested_rank: Number(program.suggested_rank || 0),
            current_rank: Number(program.current_rank || 0)
        };
    }

    function renderPreferenceList() {
        if (!ui.list || !ui.emptyState) {
            return;
        }

        ui.list.innerHTML = '';

        if (state.programs.length === 0) {
            ui.emptyState.hidden = false;
            return;
        }

        ui.emptyState.hidden = true;

        state.programs.forEach(function (program, index) {
            const item = createPreferenceItem(program, index);
            ui.list.appendChild(item);
        });
    }

    function createPreferenceItem(program, index) {
        const item = document.createElement('li');
        item.className = 'unicode-pref-item' + (index === 0 ? ' is-selected' : '');
        item.draggable = true;
        item.tabIndex = 0;
        item.dataset.programId = String(program.program_id);
        item.setAttribute('aria-label', 'Preference rank ' + (index + 1) + ': ' + program.name);

        item.addEventListener('dragstart', onDragStart);
        item.addEventListener('dragend', onDragEnd);
        item.addEventListener('keydown', onItemKeyDown);

        const rank = document.createElement('span');
        rank.className = 'unicode-rank';
        rank.textContent = String(index + 1);

        const main = document.createElement('div');
        main.className = 'unicode-main';

        const head = document.createElement('div');
        head.className = 'unicode-main-head';

        const title = document.createElement('span');
        title.className = 'unicode-main-title';
        title.textContent = program.name;

        const eligibilityBadge = document.createElement('span');
        eligibilityBadge.className = 'unicode-badge ' + (program.eligibility === 'very_likely' ? 'unicode-badge-very-likely' : 'unicode-badge-likely');
        eligibilityBadge.textContent = program.eligibility === 'very_likely' ? 'Very Likely' : 'Likely';

        head.appendChild(title);
        head.appendChild(eligibilityBadge);

        if (index === 0) {
            const selectedBadge = document.createElement('span');
            selectedBadge.className = 'unicode-badge unicode-badge-selected';
            selectedBadge.textContent = 'Selected';
            head.appendChild(selectedBadge);
        }

        if (program.major_match) {
            const majorBadge = document.createElement('span');
            majorBadge.className = 'unicode-badge unicode-badge-major';
            majorBadge.textContent = 'Major Match';
            head.appendChild(majorBadge);
        }

        const meta = document.createElement('div');
        meta.className = 'unicode-meta';
        meta.innerHTML = [
            '<span>' + escapeHtml(program.university) + '</span>',
            '<span>Unicode: <strong>' + escapeHtml(program.unicode || '-') + '</strong></span>',
            '<span>Score: <strong>' + program.recommendation_score.toFixed(2) + '</strong></span>'
        ].join('');

        const probabilityRow = document.createElement('div');
        probabilityRow.className = 'unicode-probability-row';

        const track = document.createElement('div');
        track.className = 'unicode-probability-track';

        const fill = document.createElement('div');
        fill.className = 'unicode-probability-fill';

        const probability = program.probability_percent !== null ? clamp(program.probability_percent, 0, 100) : 0;
        fill.style.width = probability + '%';

        track.appendChild(fill);

        const probabilityText = document.createElement('span');
        probabilityText.className = 'unicode-probability-text';
        probabilityText.textContent = program.probability_percent !== null
            ? program.probability_percent.toFixed(1) + '%'
            : 'N/A';

        probabilityRow.appendChild(track);
        probabilityRow.appendChild(probabilityText);

        main.appendChild(head);
        main.appendChild(meta);
        main.appendChild(probabilityRow);

        const side = document.createElement('div');
        side.className = 'unicode-side';

        const dragHandle = document.createElement('button');
        dragHandle.type = 'button';
        dragHandle.className = 'unicode-drag-handle';
        dragHandle.setAttribute('title', 'Drag to reorder');
        dragHandle.setAttribute('aria-label', 'Drag ' + program.name + ' to reorder');
        dragHandle.textContent = '||';

        const upBtn = document.createElement('button');
        upBtn.type = 'button';
        upBtn.className = 'unicode-move-btn';
        upBtn.dataset.action = 'move-up';
        upBtn.dataset.programId = String(program.program_id);
        upBtn.setAttribute('title', 'Move up');
        upBtn.setAttribute('aria-label', 'Move ' + program.name + ' up');
        upBtn.textContent = '^';
        upBtn.disabled = index === 0;

        const downBtn = document.createElement('button');
        downBtn.type = 'button';
        downBtn.className = 'unicode-move-btn';
        downBtn.dataset.action = 'move-down';
        downBtn.dataset.programId = String(program.program_id);
        downBtn.setAttribute('title', 'Move down');
        downBtn.setAttribute('aria-label', 'Move ' + program.name + ' down');
        downBtn.textContent = 'v';
        downBtn.disabled = index === state.programs.length - 1;

        side.appendChild(dragHandle);
        side.appendChild(upBtn);
        side.appendChild(downBtn);

        item.appendChild(rank);
        item.appendChild(main);
        item.appendChild(side);

        return item;
    }

    function onListClick(event) {
        const button = event.target.closest('button[data-action]');
        if (!button) {
            return;
        }

        const programId = Number(button.dataset.programId);
        const action = button.dataset.action;

        if (!programId || !action) {
            return;
        }

        if (action === 'move-up') {
            moveProgram(programId, -1);
        }

        if (action === 'move-down') {
            moveProgram(programId, 1);
        }
    }

    function onItemKeyDown(event) {
        const item = event.currentTarget;
        if (!item || state.loading) {
            return;
        }

        const programId = Number(item.dataset.programId);
        if (!programId) {
            return;
        }

        if ((event.altKey || event.ctrlKey) && event.key === 'ArrowUp') {
            event.preventDefault();
            moveProgram(programId, -1, true);
            return;
        }

        if ((event.altKey || event.ctrlKey) && event.key === 'ArrowDown') {
            event.preventDefault();
            moveProgram(programId, 1, true);
        }
    }

    function moveProgram(programId, direction, keepFocus) {
        const index = state.programs.findIndex(function (program) {
            return program.program_id === programId;
        });

        if (index < 0) {
            return;
        }

        const targetIndex = index + direction;
        if (targetIndex < 0 || targetIndex >= state.programs.length) {
            return;
        }

        const moved = state.programs[index];
        const [item] = state.programs.splice(index, 1);
        state.programs.splice(targetIndex, 0, item);

        updateCurrentRanks();
        renderPreferenceList();
        updateSummary();
        setDirtyFlag(true);

        if (keepFocus && moved) {
            const focusTarget = ui.list.querySelector('[data-program-id="' + moved.program_id + '"]');
            if (focusTarget) {
                focusTarget.focus();
            }
        }
    }

    function onDragStart(event) {
        draggedItem = event.currentTarget;
        draggedItem.classList.add('dragging');

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', draggedItem.dataset.programId || '');
        }
    }

    function onDragEnd(event) {
        event.currentTarget.classList.remove('dragging');
        draggedItem = null;
        syncStateFromDom(true);
    }

    function onDragOver(event) {
        if (!draggedItem || !ui.list) {
            return;
        }

        event.preventDefault();
        const afterElement = getDragAfterElement(ui.list, event.clientY);

        if (afterElement === null) {
            ui.list.appendChild(draggedItem);
        } else {
            ui.list.insertBefore(draggedItem, afterElement);
        }
    }

    function onDrop(event) {
        event.preventDefault();
        syncStateFromDom(true);
    }

    function getDragAfterElement(container, y) {
        const candidates = Array.from(container.querySelectorAll('.unicode-pref-item:not(.dragging)'));

        let closest = {
            offset: Number.NEGATIVE_INFINITY,
            element: null
        };

        candidates.forEach(function (element) {
            const rect = element.getBoundingClientRect();
            const offset = y - rect.top - rect.height / 2;

            if (offset < 0 && offset > closest.offset) {
                closest = {
                    offset: offset,
                    element: element
                };
            }
        });

        return closest.element;
    }

    function syncStateFromDom(markDirty) {
        if (!ui.list) {
            return;
        }

        const orderedIds = Array.from(ui.list.querySelectorAll('.unicode-pref-item'))
            .map(function (item) {
                return Number(item.dataset.programId);
            })
            .filter(function (id) {
                return id > 0;
            });

        if (orderedIds.length === 0) {
            return;
        }

        const currentIds = state.programs.map(function (program) {
            return program.program_id;
        });

        if (arrayEquals(currentIds, orderedIds)) {
            return;
        }

        const map = new Map(state.programs.map(function (program) {
            return [program.program_id, program];
        }));

        state.programs = orderedIds.map(function (id) {
            return map.get(id);
        }).filter(Boolean);

        updateCurrentRanks();
        renderPreferenceList();
        updateSummary();

        if (markDirty) {
            setDirtyFlag(true);
        }
    }

    function applySuggestedOrder() {
        const map = new Map(state.programs.map(function (program) {
            return [program.program_id, program];
        }));

        const ordered = [];
        state.suggestedOrderIds.forEach(function (id) {
            if (map.has(id)) {
                ordered.push(map.get(id));
                map.delete(id);
            }
        });

        map.forEach(function (program) {
            ordered.push(program);
        });

        state.programs = ordered;
    }

    async function savePreferenceOrder() {
        if (state.programs.length === 0) {
            notify('No preference list to save.', 'error');
            return;
        }

        if (!state.hasUnsavedChanges) {
            notify('There are no unsaved changes.', 'success');
            return;
        }

        const payload = {
            program_ids: state.programs.map(function (program) {
                return program.program_id;
            })
        };

        setLoading(true);

        try {
            const result = await fetchJson(API_BASE + '&action=savePreferenceOrder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!result.success) {
                throw new Error(result.message || 'Failed to save preference order');
            }

            const programs = Array.isArray(result.data && result.data.programs)
                ? result.data.programs
                : [];

            state.programs = programs.map(normalizeProgram);
            state.hasSavedOrder = true;
            updateCurrentRanks();
            renderPreferenceList();
            updateSummary();
            setDirtyFlag(false);
            state.lastLoadedAt = new Date();
            updateLastRefreshLabel();
            notify('Preference order saved successfully.', 'success');
        } catch (error) {
            console.error('Save preference order error:', error);
            notify(error.message || 'Failed to save preference order.', 'error');
        } finally {
            setLoading(false);
        }
    }

    async function clearSavedOrder() {
        if (!state.hasSavedOrder && !state.hasUnsavedChanges) {
            notify('No saved order found to clear.', 'error');
            return;
        }

        const confirmed = await confirmAction('Clear your saved preference order?');
        if (!confirmed) {
            return;
        }

        setLoading(true);

        try {
            const result = await fetchJson(API_BASE + '&action=clearPreferenceOrder', {
                method: 'DELETE'
            });

            if (!result.success) {
                throw new Error(result.message || 'Failed to clear saved preference order');
            }

            state.hasSavedOrder = false;
            notify('Saved order cleared. Re-loading suggested list...', 'success');
            await loadPreferencePrograms();
        } catch (error) {
            console.error('Clear saved order error:', error);
            notify(error.message || 'Failed to clear saved preference order.', 'error');
        } finally {
            setLoading(false);
        }
    }

    function updateSummary() {
        const count = state.programs.length;
        const selected = count > 0 ? state.programs[0] : null;

        if (ui.count) {
            ui.count.textContent = String(count);
        }

        if (!selected) {
            if (ui.selectedProgram) {
                ui.selectedProgram.textContent = '-';
            }
            if (ui.selectedUnicode) {
                ui.selectedUnicode.textContent = '-';
            }
            if (ui.selectedChance) {
                ui.selectedChance.textContent = '-';
            }
            if (ui.statusText) {
                ui.statusText.textContent = 'No likely or very likely programs right now. Refresh after updating your Z-Score details.';
            }
            return;
        }

        if (ui.selectedProgram) {
            ui.selectedProgram.textContent = selected.name + ' (' + selected.university + ')';
        }

        if (ui.selectedUnicode) {
            ui.selectedUnicode.textContent = selected.unicode || '-';
        }

        if (ui.selectedChance) {
            ui.selectedChance.textContent = selected.probability_percent !== null
                ? selected.probability_percent.toFixed(1) + '%'
                : 'N/A';
        }

        if (ui.statusText) {
            ui.statusText.textContent = 'Rank the list to match your preference. The first row is your simulated selection at this moment.';
        }
    }

    function updateCurrentRanks() {
        state.programs.forEach(function (program, index) {
            program.current_rank = index + 1;
        });
    }

    function compareProgramsByRecommendation(a, b) {
        const scoreCompare = (b.recommendation_score || 0) - (a.recommendation_score || 0);
        if (scoreCompare !== 0) {
            return scoreCompare;
        }

        const probabilityCompare = (b.probability_percent || 0) - (a.probability_percent || 0);
        if (probabilityCompare !== 0) {
            return probabilityCompare;
        }

        return a.name.localeCompare(b.name);
    }

    function setLoading(isLoading) {
        state.loading = isLoading;

        if (ui.root) {
            ui.root.classList.toggle('loading', isLoading);
        }

        if (ui.loadingState) {
            ui.loadingState.hidden = !isLoading;
        }

        if (ui.list) {
            ui.list.hidden = isLoading;
        }

        if (ui.emptyState && isLoading) {
            ui.emptyState.hidden = true;
        }

        updateActionButtons();
    }

    function setDirtyFlag(isDirty) {
        state.hasUnsavedChanges = Boolean(isDirty);
        updateChangeState();
        updateActionButtons();
    }

    function updateChangeState() {
        if (!ui.changeState) {
            return;
        }

        if (state.hasUnsavedChanges) {
            ui.changeState.textContent = 'Unsaved order changes';
            ui.changeState.classList.remove('unicode-change-state-synced');
            ui.changeState.classList.add('unicode-change-state-dirty');
            return;
        }

        ui.changeState.textContent = 'All changes saved';
        ui.changeState.classList.remove('unicode-change-state-dirty');
        ui.changeState.classList.add('unicode-change-state-synced');
    }

    function updateLastRefreshLabel() {
        if (!ui.lastRefresh) {
            return;
        }

        if (!state.lastLoadedAt) {
            ui.lastRefresh.textContent = 'Not loaded yet';
            return;
        }

        ui.lastRefresh.textContent = 'Last refresh: ' + state.lastLoadedAt.toLocaleString();
    }

    function updateActionButtons() {
        const disableAll = state.loading;

        if (ui.refreshBtn) {
            ui.refreshBtn.disabled = disableAll;
        }

        if (ui.autoSortBtn) {
            ui.autoSortBtn.disabled = disableAll;
        }

        if (ui.resetSuggestedBtn) {
            ui.resetSuggestedBtn.disabled = disableAll;
        }

        if (ui.saveBtn) {
            ui.saveBtn.disabled = disableAll;
        }

        if (ui.clearSavedBtn) {
            ui.clearSavedBtn.disabled = disableAll;
        }

        if (ui.pdfBtn) {
            ui.pdfBtn.disabled = disableAll;
        }
    }

    async function fetchJson(url, options) {
        const controller = new AbortController();
        const timeout = window.setTimeout(function () {
            controller.abort();
        }, 15000);

        const requestOptions = Object.assign({}, options || {}, {
            credentials: 'same-origin',
            signal: controller.signal
        });

        try {
            const response = await fetch(url, requestOptions);

            let payload = null;
            try {
                payload = await response.json();
            } catch (error) {
                throw new Error('Server returned an invalid response.');
            }

            if (!response.ok) {
                throw new Error(payload && payload.message ? payload.message : 'Request failed with status ' + response.status);
            }

            return payload;
        } catch (error) {
            if (error && error.name === 'AbortError') {
                throw new Error('Request timed out. Please try again.');
            }

            throw error;
        } finally {
            window.clearTimeout(timeout);
        }
    }

    function notify(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type || 'success');
            return;
        }

        alert(message);
    }

    async function confirmAction(message) {
        const confirmation = window.confirm(message);

        if (confirmation && typeof confirmation.then === 'function') {
            const asyncResult = await confirmation;
            return Boolean(asyncResult);
        }

        return Boolean(confirmation);
    }

    function printPreferenceList() {
        if (state.programs.length === 0) {
            notify('No preference list to print.', 'error');
            return;
        }

        const selected = state.programs[0];
        const createdAt = new Date().toLocaleString();

        const rows = state.programs.map(function (program, index) {
            return '<tr>' +
                '<td>' + (index + 1) + '</td>' +
                '<td>' + escapeHtml(program.name) + '</td>' +
                '<td>' + escapeHtml(program.university) + '</td>' +
                '<td>' + escapeHtml(program.unicode || '-') + '</td>' +
                '<td>' + escapeHtml(program.eligibility === 'very_likely' ? 'Very Likely' : 'Likely') + '</td>' +
                '<td>' + (program.probability_percent !== null ? program.probability_percent.toFixed(1) + '%' : 'N/A') + '</td>' +
                '</tr>';
        }).join('');

        const html = '<!doctype html>' +
            '<html><head><meta charset="utf-8"><title>Unicode Preference List</title>' +
            '<style>' +
            'body{font-family:Arial,sans-serif;margin:24px;color:#0f172a;}' +
            'h1{margin:0 0 8px;font-size:24px;}' +
            'p{margin:4px 0 12px;}' +
            '.meta{padding:12px;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;margin-bottom:14px;}' +
            'table{border-collapse:collapse;width:100%;}' +
            'th,td{border:1px solid #cbd5e1;padding:8px;text-align:left;font-size:13px;}' +
            'th{background:#e2e8f0;}' +
            '</style></head><body>' +
            '<h1>Unicode Preference List</h1>' +
            '<p>Generated: ' + escapeHtml(createdAt) + '</p>' +
            '<div class="meta">' +
            '<strong>Simulated Selected Program:</strong> ' + escapeHtml(selected.name) + ' (' + escapeHtml(selected.university) + ')' + '<br>' +
            '<strong>Unicode:</strong> ' + escapeHtml(selected.unicode || '-') + '<br>' +
            '<strong>Chance:</strong> ' + (selected.probability_percent !== null ? selected.probability_percent.toFixed(1) + '%' : 'N/A') +
            '</div>' +
            '<table><thead><tr><th>Rank</th><th>Program</th><th>University</th><th>Unicode</th><th>Eligibility</th><th>Chance</th></tr></thead>' +
            '<tbody>' + rows + '</tbody></table>' +
            '</body></html>';

        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            notify('Popup blocked. Please allow popups to print.', 'error');
            return;
        }

        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }

    function getAppBasePath() {
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        if (pathParts.length > 0) {
            return '/' + pathParts[0];
        }

        const baseElement = document.querySelector('base[href]');
        if (!baseElement) {
            return '';
        }

        try {
            const baseUrl = new URL(baseElement.getAttribute('href'), window.location.origin);
            const path = baseUrl.pathname.replace(/\/+$/, '');
            return path === '/' ? '' : path;
        } catch (error) {
            return '';
        }
    }

    function arrayEquals(a, b) {
        if (a.length !== b.length) {
            return false;
        }

        for (let i = 0; i < a.length; i += 1) {
            if (a[i] !== b[i]) {
                return false;
            }
        }

        return true;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }
})();
