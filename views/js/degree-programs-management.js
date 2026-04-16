(function () {
    'use strict';

    const API_PREFIX = '/unihelper/api?controller=ProgramController&action=';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDegreeProgramsManagement);
    } else {
        initDegreeProgramsManagement();
    }

    function initDegreeProgramsManagement() {
        const form = document.getElementById('addDegreeForm');
        const listContent = document.querySelector('.admin-list-content');

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
                if (payload && payload.success === false) {
                    throw new Error(payload.message || 'Unable to save degree program.');
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
                    method: 'GET'
                });

                const payload = await readJsonSafely(response);
                if (payload && payload.success === false) {
                    throw new Error(payload.message || 'Unable to delete degree program.');
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

                const data = await response.json();
                if (!data || typeof data !== 'object') {
                    throw new Error('Invalid degree data response');
                }

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
            cancelButton.style.marginRight = '10px';
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
