(function () {
    'use strict';

    const API_PREFIX = '/unihelper/api?controller=ProgramController&action=';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDegreeProgramsManagement);
    } else {
        initDegreeProgramsManagement();
    }

    function initDegreeProgramsManagement() {
        const dashboardMain = document.getElementById('dashboardMain');
        if (dashboardMain && dashboardMain.dataset.component && dashboardMain.dataset.component !== 'degree-programs-management') {
            return;
        }

        const form = document.getElementById('addDegreeForm');
        const listContent = document.querySelector('.admin-list-content');
        const universitySelect = document.getElementById('university');
        const majorSelect = document.getElementById('major');

        if (!form || !listContent) {
            return;
        }

        if (form.dataset.boundDegreeProgramsManagement === '1') {
            return;
        }
        form.dataset.boundDegreeProgramsManagement = '1';

        const searchInput = document.querySelector('.admin-search-input');
        const searchOptions = document.querySelectorAll('input[name="searchIndex"]');
        const submitButton = form.querySelector('button[type="submit"]');

        const publishButtonMarkup = getSubmitButtonMarkup('Publish Degree Program');
        const saveButtonMarkup = getSubmitButtonMarkup('Save Changes');

        const pathDescriptionInput = document.getElementById('pathDescription');
        const defaultPathDescription = pathDescriptionInput ? pathDescriptionInput.value : 'Default Entry Path';

        let editingDegreeId = null;
        let cancelButton = null;

        form.addEventListener('submit', onFormSubmit);
        listContent.addEventListener('click', onListActionClick);

        if (searchInput) {
            searchInput.addEventListener('input', filterCards);
        }

        searchOptions.forEach((option) => {
            option.addEventListener('change', filterCards);
        });

        loadInitialData();

        async function loadInitialData() {
            renderListMessage('Loading degree programs...', '&#9203;');

            try {
                const response = await apiRequest('getDegreeManagementData', {
                    method: 'GET'
                });

                const payload = await readJsonSafely(response);
                if (!payload || payload.success !== true || !payload.data || typeof payload.data !== 'object') {
                    throw new Error((payload && payload.message) || 'Failed to load degree management data.');
                }

                const data = payload.data;
                populateSelect(universitySelect, data.universities, 'Select University');
                populateSelect(majorSelect, data.majors, 'Select Major');
                renderDegreeCards(data.degrees);
                filterCards();
            } catch (error) {
                console.error('Failed to load degree management data:', error);
                renderListMessage(error && error.message ? error.message : 'Failed to load degree programs.', '&#9888;');
            }
        }

        function renderDegreeCards(degrees) {
            if (!Array.isArray(degrees) || degrees.length === 0) {
                renderListMessage('No degree programs found. Add your first program using the form.', '&#128218;');
                return;
            }

            listContent.innerHTML = degrees.map(getDegreeCardMarkup).join('');
        }

        function populateSelect(selectElement, options, placeholderText) {
            if (!selectElement) {
                return;
            }

            const currentValue = String(selectElement.value || '');
            const optionRows = Array.isArray(options) ? options : [];
            const markup = ['<option value="">' + escapeHtml(placeholderText) + '</option>'];

            optionRows.forEach(function (option) {
                if (!option || option.id == null) {
                    return;
                }

                markup.push(
                    '<option value="' + escapeHtml(option.id) + '">' + escapeHtml(option.name || '') + '</option>'
                );
            });

            selectElement.innerHTML = markup.join('');

            if (currentValue !== '') {
                selectElement.value = currentValue;
            }
        }

        function getDegreeCardMarkup(rawDegree) {
            const degree = rawDegree || {};
            const degreeId = escapeHtml(degree.id || '');
            const name = escapeHtml(degree.name || '');
            const unicode = escapeHtml(degree.unicode || '');
            const university = escapeHtml(degree.university || '');
            const stream = escapeHtml(degree.stream || '');
            const duration = escapeHtml(degree.duration || '');
            const description = escapeHtml(degree.description || '');
            const statusText = escapeHtml(degree.status || 'Active');
            const statusClass = escapeHtml(String(degree.status || 'active').toLowerCase().replace(/\s+/g, '-'));
            const requirementsMarkup = getRequirementsMarkup(normalizeRequirements(degree.subject_requirements));

            return [
                '<div class="degree-card" data-degree-id="' + degreeId + '">',
                '<input type="hidden" name="degree_id" value="' + degreeId + '">',
                '<div class="degree-card-header">',
                '<h3 class="degree-card-title">' + name + '</h3>',
                '<span class="degree-card-code">' + unicode + '</span>',
                '</div>',
                '<div class="degree-card-info">',
                '<div class="degree-card-item">',
                '<span class="degree-card-item-label">University</span>',
                '<span class="degree-card-item-value">' + university + '</span>',
                '</div>',
                '<div class="degree-card-item">',
                '<span class="degree-card-item-label">Stream</span>',
                '<span class="degree-card-item-value">' + stream + '</span>',
                '</div>',
                '<div class="degree-card-item">',
                '<span class="degree-card-item-label">Duration</span>',
                '<span class="degree-card-item-value">' + duration + ' Years</span>',
                '</div>',
                '<div class="degree-card-item">',
                '<span class="degree-card-item-label">Status</span>',
                '<span class="status-badge status-' + statusClass + '">' + statusText + '</span>',
                '</div>',
                '</div>',
                '<div class="degree-card-description">' + description + '</div>',
                requirementsMarkup,
                '<div class="degree-card-actions">',
                '<button class="degree-card-button" title="Edit" data-degree-id="' + degreeId + '">',
                '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">',
                '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>',
                '<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>',
                '</svg>',
                '</button>',
                '<button class="degree-card-button delete-button" title="Delete" data-degree-id="' + degreeId + '">',
                '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">',
                '<polyline points="3 6 5 6 21 6"></polyline>',
                '<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>',
                '<line x1="10" y1="11" x2="10" y2="17"></line>',
                '<line x1="14" y1="11" x2="14" y2="17"></line>',
                '</svg>',
                '</button>',
                '</div>',
                '</div>'
            ].join('');
        }

        function getRequirementsMarkup(requirements) {
            if (!Array.isArray(requirements) || requirements.length === 0) {
                return '';
            }

            const listItems = requirements.map(function (requirement) {
                return [
                    '<li>',
                    escapeHtml(requirement.subject_name),
                    '<span>(Min: ' + escapeHtml(requirement.min_grade) + ')</span>',
                    '</li>'
                ].join('');
            }).join('');

            return [
                '<div class="degree-card-requirements">',
                '<span class="degree-card-item-label">Subject Requirements</span>',
                '<ul class="degree-card-requirements-list">',
                listItems,
                '</ul>',
                '</div>'
            ].join('');
        }

        function normalizeRequirements(rawRequirements) {
            if (Array.isArray(rawRequirements)) {
                return rawRequirements.map(normalizeRequirement).filter(Boolean);
            }

            if (typeof rawRequirements === 'string') {
                const value = rawRequirements.trim();
                if (value === '') {
                    return [];
                }

                try {
                    const parsed = JSON.parse(value);
                    if (Array.isArray(parsed)) {
                        return parsed.map(normalizeRequirement).filter(Boolean);
                    }
                } catch (error) {
                    return [];
                }
            }

            return [];
        }

        function normalizeRequirement(requirement) {
            if (!requirement || typeof requirement !== 'object') {
                return null;
            }

            const subjectName = String(requirement.subject_name || requirement.subject || '').trim();
            if (subjectName === '') {
                return null;
            }

            const minGrade = String(requirement.min_grade || requirement.minGrade || 'S').trim() || 'S';

            return {
                subject_name: subjectName,
                min_grade: minGrade
            };
        }

        function renderListMessage(message, iconHtml) {
            listContent.innerHTML = [
                '<div class="empty-state">',
                '<div class="empty-state-icon">' + iconHtml + '</div>',
                '<p class="empty-state-message">' + escapeHtml(message) + '</p>',
                '</div>'
            ].join('');
        }

        function onListActionClick(event) {
            const actionButton = event.target.closest('.degree-card-button');
            if (!actionButton) {
                return;
            }

            event.preventDefault();

            const degreeId = actionButton.getAttribute('data-degree-id');
            if (!degreeId) {
                return;
            }

            if (actionButton.classList.contains('delete-button')) {
                handleDelete(degreeId, actionButton);
                return;
            }

            handleEdit(degreeId, actionButton);
        }

        async function onFormSubmit(event) {
            event.preventDefault();

            const formData = new FormData(form);
            const currentEditId = editingDegreeId;
            const isEditMode = Boolean(currentEditId);
            const action = editingDegreeId
                ? 'updateDegreeProgramForm&id=' + encodeURIComponent(editingDegreeId)
                : 'addDegreeProgram';

            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const response = await apiRequest(action, {
                    method: 'POST',
                    body: formData
                });

                const payload = await readJsonSafely(response);
                if (!payload || payload.success !== true) {
                    throw new Error((payload && payload.message) || 'Unable to save degree program.');
                }

                if (isEditMode) {
                    applyFormValuesToCard(currentEditId);
                    resetFormState(true);
                    return;
                }

                window.location.reload();
            } catch (error) {
                console.error('Failed to save degree program:', error);
                window.alert(error && error.message
                    ? error.message
                    : 'Unable to save degree program. Please try again.');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        }

        async function handleDelete(degreeId, button) {
            const degreeCard = button.closest('.degree-card');
            const degreeName = degreeCard && degreeCard.querySelector('.degree-card-title')
                ? degreeCard.querySelector('.degree-card-title').textContent.trim()
                : 'this degree program';

            const confirmed = await Promise.resolve(window.confirm(
                'Are you sure you want to delete the degree program "' + degreeName + '"? This action cannot be undone.'
            ));

            if (!confirmed) {
                return;
            }

            try {
                const response = await apiRequest('removeDegreeProgram&id=' + encodeURIComponent(degreeId), {
                    method: 'POST'
                });

                const payload = await readJsonSafely(response);
                if (!payload || payload.success !== true) {
                    throw new Error((payload && payload.message) || 'Unable to delete degree program.');
                }

                window.location.reload();
            } catch (error) {
                console.error('Failed to delete degree program:', error);
                window.alert(error && error.message
                    ? error.message
                    : 'Unable to delete degree program. Please try again.');
            }
        }

        async function handleEdit(degreeId, button) {
            const degreeCard = button.closest('.degree-card');
            if (!degreeCard) {
                return;
            }

            try {
                const response = await apiRequest('getDegreeProgramData&id=' + encodeURIComponent(degreeId), {
                    method: 'GET'
                });

                const payload = await readJsonSafely(response);
                if (!payload || payload.success !== true || !payload.data || typeof payload.data !== 'object') {
                    throw new Error('Invalid degree data response');
                }
                const data = payload.data;

                const fallbackDegreeName = getText(degreeCard, '.degree-card-title');
                const fallbackUnicode = getText(degreeCard, '.degree-card-code');
                const fallbackDuration = getDurationFromCard(degreeCard);
                const fallbackDescription = getText(degreeCard, '.degree-card-description');

                setValue('degreeName', data.name || fallbackDegreeName);
                setValue('unicode', data.unicode || fallbackUnicode);
                setValue('duration', data.duration || fallbackDuration);
                setValue('description', data.descriptions || fallbackDescription);
                setValue('pathDescription', data.path_description || defaultPathDescription);
                setValue('subjectRequirements', data.subject_requirements_text || '');
                setValue('stream', data.stream || '');
                setValue('university', data.university_id || '');
                setValue('major', data.major_id || '');

                editingDegreeId = degreeId;
                form.action = API_PREFIX + 'updateDegreeProgramForm&id=' + encodeURIComponent(degreeId);

                if (submitButton) {
                    submitButton.innerHTML = saveButtonMarkup;
                }

                ensureCancelButton();
                showOnlyCard(degreeCard);
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } catch (error) {
                console.error('Failed to load degree program for editing:', error);
                window.alert('Unable to load degree program data. Please try again.');
            }
        }

        function ensureCancelButton() {
            if (cancelButton && cancelButton.isConnected) {
                return;
            }

            cancelButton = document.createElement('button');
            cancelButton.type = 'button';
            cancelButton.className = 'admin-form-button cancel-edit';
            cancelButton.textContent = 'Cancel';
            cancelButton.addEventListener('click', function () {
                resetFormState(true);
            });

            if (submitButton && submitButton.parentNode) {
                submitButton.parentNode.insertBefore(cancelButton, submitButton);
            }
        }

        function resetFormState(showAllCards) {
            editingDegreeId = null;
            form.reset();
            form.action = API_PREFIX + 'addDegreeProgram';

            if (pathDescriptionInput) {
                pathDescriptionInput.value = defaultPathDescription;
            }

            if (submitButton) {
                submitButton.innerHTML = publishButtonMarkup;
            }

            if (cancelButton && cancelButton.isConnected) {
                cancelButton.remove();
            }

            if (showAllCards) {
                listContent.querySelectorAll('.degree-card').forEach(function (card) {
                    card.style.display = 'block';
                });
            }
        }

        function showOnlyCard(targetCard) {
            listContent.querySelectorAll('.degree-card').forEach(function (card) {
                card.style.display = card === targetCard ? 'block' : 'none';
            });
        }

        function filterCards() {
            const cards = listContent.querySelectorAll('.degree-card');
            if (cards.length === 0 || !searchInput) {
                return;
            }

            const searchTerm = searchInput.value.toLowerCase().trim();
            const selectedSearchOption = document.querySelector('input[name="searchIndex"]:checked');
            const searchIndex = selectedSearchOption ? selectedSearchOption.value : 'name';
            const searchEmptyState = getOrCreateSearchEmptyState();

            let matchFound = false;

            cards.forEach(function (card) {
                const title = getText(card, '.degree-card-title').toLowerCase();
                const unicode = getText(card, '.degree-card-code').toLowerCase();
                const contentToSearch = searchIndex === 'unicode' ? unicode : title;

                if (contentToSearch.indexOf(searchTerm) !== -1) {
                    card.style.display = 'block';
                    matchFound = true;
                } else {
                    card.style.display = 'none';
                }
            });

            if (!matchFound && searchTerm !== '') {
                searchEmptyState.querySelector('.empty-state-message').textContent = 'No matching degree programs found.';
                searchEmptyState.style.display = 'flex';
            } else {
                searchEmptyState.style.display = 'none';
            }
        }

        function getOrCreateSearchEmptyState() {
            let searchEmptyState = listContent.querySelector('.search-empty-state');
            if (searchEmptyState) {
                return searchEmptyState;
            }

            searchEmptyState = document.createElement('div');
            searchEmptyState.className = 'empty-state search-empty-state';
            searchEmptyState.style.display = 'none';
            searchEmptyState.innerHTML = [
                '<div class="empty-state-icon">&#128269;</div>',
                '<p class="empty-state-message">No matching degree programs found.</p>'
            ].join('');

            listContent.appendChild(searchEmptyState);
            return searchEmptyState;
        }

        function getText(scope, selector) {
            const node = scope.querySelector(selector);
            return node ? node.textContent.trim() : '';
        }

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function applyFormValuesToCard(degreeId) {
            const selector = '.degree-card[data-degree-id="' + String(degreeId).replace(/"/g, '\\"') + '"]';
            const degreeCard = listContent.querySelector(selector);
            if (!degreeCard) {
                return;
            }

            const degreeName = getFieldValue('degreeName');
            const unicode = getFieldValue('unicode');
            const description = getFieldValue('description');
            const streamText = getSelectedOptionText('stream');
            const universityText = getSelectedOptionText('university');
            const duration = getFieldValue('duration');

            const titleNode = degreeCard.querySelector('.degree-card-title');
            if (titleNode) {
                titleNode.textContent = degreeName;
            }

            const unicodeNode = degreeCard.querySelector('.degree-card-code');
            if (unicodeNode) {
                unicodeNode.textContent = unicode;
            }

            const descriptionNode = degreeCard.querySelector('.degree-card-description');
            if (descriptionNode) {
                descriptionNode.textContent = description;
            }

            const infoValues = degreeCard.querySelectorAll('.degree-card-item-value');
            if (infoValues[0]) {
                infoValues[0].textContent = universityText;
            }
            if (infoValues[1]) {
                infoValues[1].textContent = streamText;
            }
            if (infoValues[2]) {
                infoValues[2].textContent = duration ? duration + ' Years' : '';
            }
        }

        function getFieldValue(elementId) {
            const element = document.getElementById(elementId);
            if (!element) {
                return '';
            }

            return String(element.value || '').trim();
        }

        function getSelectedOptionText(selectId) {
            const selectElement = document.getElementById(selectId);
            if (!selectElement || !selectElement.options) {
                return '';
            }

            const selectedOption = selectElement.options[selectElement.selectedIndex];
            return selectedOption ? selectedOption.textContent.trim() : '';
        }

        function getDurationFromCard(card) {
            const durationText = getText(card, '.degree-card-item:nth-child(3) .degree-card-item-value');
            return durationText.replace(' Years', '').trim();
        }

        function setValue(elementId, value) {
            const element = document.getElementById(elementId);
            if (!element) {
                return;
            }

            element.value = value == null ? '' : String(value);
        }

        function getSubmitButtonMarkup(label) {
            return [
                '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">',
                '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>',
                '<polyline points="17 21 17 13 7 13 7 21"></polyline>',
                '<polyline points="7 3 7 8 15 8"></polyline>',
                '</svg>',
                label
            ].join('');
        }

        async function apiRequest(actionQuery, options) {
            const requestOptions = options || {};
            const headers = Object.assign(
                {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                requestOptions.headers || {}
            );

            const response = await fetch(API_PREFIX + actionQuery, Object.assign({}, requestOptions, {
                credentials: 'same-origin',
                headers: headers
            }));

            if (!response.ok) {
                throw new Error('Request failed with status ' + response.status);
            }

            return response;
        }

        async function readJsonSafely(response) {
            const contentType = (response.headers.get('content-type') || '').toLowerCase();
            if (contentType.indexOf('application/json') === -1) {
                return null;
            }

            try {
                return await response.json();
            } catch (error) {
                console.warn('Failed to parse JSON response:', error);
                return null;
            }
        }
    }
})();
