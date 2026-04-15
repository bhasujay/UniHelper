(function () {
    'use strict';

    const API_BASE = '/UniHelper/api?controller=UnicodeGeneratorController';

    const state = {
        programs: [],
        suggestedOrderIds: [],
        loading: false
    };

    const ui = {
        root: null,
        list: null,
        emptyState: null,
        statusText: null,
        count: null,
        selectedProgram: null,
        selectedUnicode: null,
        selectedChance: null,
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
        ui.statusText = document.getElementById('unicodeStatusText');
        ui.count = document.getElementById('unicodeEligibleCount');
        ui.selectedProgram = document.getElementById('unicodeSelectedProgram');
        ui.selectedUnicode = document.getElementById('unicodeSelectedUnicode');
        ui.selectedChance = document.getElementById('unicodeSelectedChance');

        ui.refreshBtn = document.getElementById('unicodeRefreshBtn');
        ui.autoSortBtn = document.getElementById('unicodeAutoSortBtn');
        ui.resetSuggestedBtn = document.getElementById('unicodeResetSuggestedBtn');
        ui.saveBtn = document.getElementById('unicodeSaveBtn');
        ui.clearSavedBtn = document.getElementById('unicodeClearSavedBtn');
        ui.pdfBtn = document.getElementById('unicodePdfBtn');

        bindEvents();
        loadPreferencePrograms();
    }

    function bindEvents() {
        if (!ui.list) {
            return;
        }

        ui.refreshBtn && ui.refreshBtn.addEventListener('click', loadPreferencePrograms);

        ui.autoSortBtn && ui.autoSortBtn.addEventListener('click', function () {
            if (state.programs.length === 0) {
                notify('No programs available to sort.', 'error');
                return;
            }

            state.programs.sort(compareProgramsByRecommendation);
            updateCurrentRanks();
            renderPreferenceList();
            updateSummary();
            notify('Sorted by recommendation score.', 'success');
        });

        ui.resetSuggestedBtn && ui.resetSuggestedBtn.addEventListener('click', function () {
            if (state.suggestedOrderIds.length === 0) {
                notify('No suggested order available yet.', 'error');
                return;
            }

            applySuggestedOrder();
            updateCurrentRanks();
            renderPreferenceList();
            updateSummary();
            notify('Reset to suggested ranking.', 'success');
        });

        ui.saveBtn && ui.saveBtn.addEventListener('click', savePreferenceOrder);
        ui.clearSavedBtn && ui.clearSavedBtn.addEventListener('click', clearSavedOrder);
        ui.pdfBtn && ui.pdfBtn.addEventListener('click', printPreferenceList);

        ui.list.addEventListener('click', onListClick);
        ui.list.addEventListener('dragover', onDragOver);
        ui.list.addEventListener('drop', onDrop);
    }

    async function loadPreferencePrograms() {
        setLoading(true);

        try {
            const response = await fetch(API_BASE + '&action=getPreferencePrograms');
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Failed to load preference programs');
            }

            const programs = Array.isArray(result.data && result.data.programs)
                ? result.data.programs
                : [];

            state.programs = programs.map(normalizeProgram);
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

            if (ui.statusText) {
                ui.statusText.textContent = state.programs.length > 0
                    ? 'Drag and drop to reorder. Top item is your simulated UGC selection.'
                    : 'No Likely / Very Likely results right now. Update your Z-Score and refresh.';
            }

            if (result.data && result.data.has_saved_order) {
                notify('Loaded your saved preference order.', 'success');
            }
        } catch (error) {
            console.error('Unicode generator load error:', error);
            notify(error.message || 'Failed to load preference programs.', 'error');
            state.programs = [];
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
        item.className = 'unicode-pref-item';
        item.draggable = true;
        item.dataset.programId = String(program.program_id);

        item.addEventListener('dragstart', onDragStart);
        item.addEventListener('dragend', onDragEnd);

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
        dragHandle.textContent = '::';

        const upBtn = document.createElement('button');
        upBtn.type = 'button';
        upBtn.className = 'unicode-move-btn';
        upBtn.dataset.action = 'move-up';
        upBtn.dataset.programId = String(program.program_id);
        upBtn.setAttribute('title', 'Move up');
        upBtn.textContent = '^';

        const downBtn = document.createElement('button');
        downBtn.type = 'button';
        downBtn.className = 'unicode-move-btn';
        downBtn.dataset.action = 'move-down';
        downBtn.dataset.programId = String(program.program_id);
        downBtn.setAttribute('title', 'Move down');
        downBtn.textContent = 'v';

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

    function moveProgram(programId, direction) {
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

        const [item] = state.programs.splice(index, 1);
        state.programs.splice(targetIndex, 0, item);

        updateCurrentRanks();
        renderPreferenceList();
        updateSummary();
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
        syncStateFromDom();
    }

    function onDragOver(event) {
        if (!draggedItem) {
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
        syncStateFromDom();
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

    function syncStateFromDom() {
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

        const map = new Map(state.programs.map(function (program) {
            return [program.program_id, program];
        }));

        state.programs = orderedIds.map(function (id) {
            return map.get(id);
        }).filter(Boolean);

        updateCurrentRanks();
        renderPreferenceList();
        updateSummary();
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

        const payload = {
            program_ids: state.programs.map(function (program) {
                return program.program_id;
            })
        };

        setLoading(true);

        try {
            const response = await fetch(API_BASE + '&action=savePreferenceOrder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Failed to save preference order');
            }

            const programs = Array.isArray(result.data && result.data.programs)
                ? result.data.programs
                : [];

            state.programs = programs.map(normalizeProgram);
            updateCurrentRanks();
            renderPreferenceList();
            updateSummary();
            notify('Preference order saved successfully.', 'success');
        } catch (error) {
            console.error('Save preference order error:', error);
            notify(error.message || 'Failed to save preference order.', 'error');
        } finally {
            setLoading(false);
        }
    }

    function clearSavedOrder() {
        handleConfirm('Clear your saved preference order?', async function (confirmed) {
            if (!confirmed) {
                return;
            }

            setLoading(true);

            try {
                const response = await fetch(API_BASE + '&action=clearPreferenceOrder', {
                    method: 'DELETE'
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Failed to clear saved preference order');
                }

                notify('Saved order cleared. Re-loading suggested list...', 'success');
                await loadPreferencePrograms();
            } catch (error) {
                console.error('Clear saved order error:', error);
                notify(error.message || 'Failed to clear saved preference order.', 'error');
            } finally {
                setLoading(false);
            }
        });
    }

    function updateSummary() {
        const count = state.programs.length;
        const selected = count > 0 ? state.programs[0] : null;

        if (ui.count) {
            ui.count.textContent = String(count);
        }

        if (!selected) {
            ui.selectedProgram && (ui.selectedProgram.textContent = '-');
            ui.selectedUnicode && (ui.selectedUnicode.textContent = '-');
            ui.selectedChance && (ui.selectedChance.textContent = '-');
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

        [ui.refreshBtn, ui.autoSortBtn, ui.resetSuggestedBtn, ui.saveBtn, ui.clearSavedBtn, ui.pdfBtn].forEach(function (button) {
            if (button) {
                button.disabled = isLoading;
            }
        });
    }

    function notify(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type || 'success');
            return;
        }

        alert(message);
    }

    function handleConfirm(message, callback) {
        const confirmation = window.confirm(message);

        if (confirmation && typeof confirmation.then === 'function') {
            confirmation.then(function (result) {
                callback(Boolean(result));
            });
            return;
        }

        callback(Boolean(confirmation));
    }

    function printPreferenceList() {
        if (state.programs.length === 0) {
            notify('No preference list to print.', 'error');
            return;
        }

        const selected = state.programs[0];
        const now = new Date();
        const createdAt = now.toLocaleString();

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
