// degree-programs.js - Degree search and wishlist interactions
console.log('degree-programs.js loaded');

const API_BASE = '/UniHelper/api';
let degreeProgramsInitialized = false;
let latestSearchRequest = 0;
const degreeProgramDetailsById = new Map();

function buildApiUrl(controller, action, params = {}) {
    const query = new URLSearchParams({ controller, action });

    Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            query.append(key, value);
        }
    });

    return `${API_BASE}?${query.toString()}`;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatStreamLabel(stream) {
    if (!stream) {
        return 'General';
    }

    return String(stream)
        .replace(/[-_]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function getEligibilityBadge(rawEligibility, source) {
    const value = String(rawEligibility || '').toLowerCase();
    const mapping = {
        'very-likely': { label: 'Very Likely', className: 'is-very-likely' },
        likely: { label: 'Likely', className: 'is-likely' },
        possible: { label: 'Possible', className: 'is-possible' },
        unlikely: { label: 'Unlikely', className: 'is-unlikely' },
        noc: { label: 'NOC', className: 'is-noc' }
    };

    if (mapping[value]) {
        return mapping[value];
    }

    if (source === 'wishlist') {
        return { label: 'Saved', className: 'is-very-likely' };
    }

    return { label: 'Search Match', className: 'is-noc' };
}

function createDescriptionSnippet(text, maxLength = 150) {
    const normalized = String(text || '').replace(/\s+/g, ' ').trim();
    if (normalized.length <= maxLength) {
        return normalized;
    }

    return `${normalized.slice(0, maxLength - 1).trimEnd()}...`;
}

function normalizeProgram(program, source) {
    const normalized = {
        program_id: Number(program.program_id || 0),
        name: program.name || program.program_name || 'Unknown Program',
        university_name: program.university_name || program.university || 'Unknown University',
        major_name: program.major_name || 'General Studies',
        stream: formatStreamLabel(program.stream),
        unicode: program.unicode || 'N/A',
        description: String(program.descriptions || program.description || '').trim(),
        duration: String(program.duration || '').trim(),
        path_description: String(program.path_description || '').trim(),
        subject_requirement_paths: Array.isArray(program.subject_requirement_paths) ? program.subject_requirement_paths : [],
        subject_requirements: Array.isArray(program.subject_requirements) ? program.subject_requirements : [],
        subject_requirements_text: String(program.subject_requirements_text || '').trim(),
        eligibility: program.eligibility || null,
        source
    };

    normalized.detailDescription = normalized.description !== ''
        ? normalized.description
        : (normalized.source === 'wishlist'
            ? 'Saved from your wishlist for quick access.'
            : 'Open this degree to explore detailed entry requirements.');

    normalized.cardNote = createDescriptionSnippet(normalized.detailDescription);
    normalized.detailsLoaded =
        normalized.subject_requirement_paths.length > 0
        || normalized.subject_requirements.length > 0
        || normalized.subject_requirements_text !== '';

    return normalized;
}

function getProgramCardHtml(program, index) {
    const badge = getEligibilityBadge(program.eligibility, program.source);

    const programId = Number.isFinite(program.program_id) ? program.program_id : 0;
    const isWishlistCard = program.source === 'wishlist';
    const heartClass = isWishlistCard ? 'zscore-program-action wishlist-btn in-wishlist' : 'zscore-program-action wishlist-btn';
    const heartAria = isWishlistCard ? 'Remove from wishlist' : 'Add to wishlist';

    return `
        <div
            class="degree-program-card"
            data-program-id="${programId}"
            data-program-source="${escapeHtml(program.source)}"
            role="button"
            tabindex="0"
            aria-label="View details for ${escapeHtml(program.name)}"
        >
            <div class="zscore-program-headline-row">
                <span class="zscore-rank-pill">#${index + 1}</span>
                <span class="zscore-eligibility-badge ${badge.className}">${escapeHtml(badge.label)}</span>
            </div>

            <div class="card-header">
                <h3>${escapeHtml(program.name)}</h3>
                <p>${escapeHtml(program.university_name)}</p>
            </div>

            <div class="card-body">
                <p class="faculty-name">${escapeHtml(program.major_name)}</p>

                <div class="degree-metrics">
                    <div class="cutoff-info">Stream: <strong>${escapeHtml(program.stream)}</strong></div>
                    <div class="unicode-info">Unicode: <strong>${escapeHtml(program.unicode)}</strong></div>
                    ${program.duration ? `<div class="cutoff-info">Duration: <strong>${escapeHtml(program.duration)}</strong></div>` : ''}
                </div>
            </div>

            <p class="zscore-cutoff-insight">${escapeHtml(program.cardNote)}</p>

            <div class="degree-tags">
                <span class="tag">${escapeHtml(program.stream)} Stream</span>
            </div>

            <div class="card-footer">
                <div class="footer-details">
                    <span>${escapeHtml(program.major_name)}</span>
                    <span>Unicode: <strong>${escapeHtml(program.unicode)}</strong></span>
                </div>
                <div class="card-actions">
                    <button type="button" class="${heartClass}" onclick="toggleWishlist(${programId})" aria-label="${heartAria}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    `;
}

function renderNoSearchMessage(container, title, text, iconSvg) {
    container.innerHTML = `
        <div class="no-search-message">
            <div class="no-search-icon">${iconSvg}</div>
            <h3>${escapeHtml(title)}</h3>
            <p>${escapeHtml(text)}</p>
        </div>
    `;
}

function renderPrograms(container, programs, source) {
    if (!container) {
        return;
    }

    if (!Array.isArray(programs) || programs.length === 0) {
        if (source === 'wishlist') {
            renderNoSearchMessage(
                container,
                'Your wishlist is empty',
                'Browse degree programs and click the heart to add them to your wishlist.',
                '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>'
            );
            return;
        }

        renderNoSearchMessage(
            container,
            'No Programs Found',
            'No degree programs match your search criteria. Try adjusting your filters or search terms.',
            '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
        );
        return;
    }

    const normalizedPrograms = programs.map((program) => normalizeProgram(program, source));
    normalizedPrograms.forEach((program) => {
        if (Number.isFinite(program.program_id) && program.program_id > 0) {
            const existingProgram = degreeProgramDetailsById.get(program.program_id);
            if (!existingProgram || !existingProgram.detailsLoaded) {
                degreeProgramDetailsById.set(program.program_id, program);
            }
        }
    });

    container.innerHTML = normalizedPrograms.map((program, index) => getProgramCardHtml(program, index)).join('');
    decorateDegreeCards(container);
}

function decorateDegreeCards(rootElement = document) {
    const cards = Array.from(rootElement.querySelectorAll('.degree-program-card'));
    cards.forEach((card) => {
        card.setAttribute('role', 'button');
        card.setAttribute('tabindex', '0');

        if (!card.hasAttribute('aria-label')) {
            const title = card.querySelector('.card-header h3')?.textContent?.trim() || 'degree program';
            card.setAttribute('aria-label', `View details for ${title}`);
        }
    });
}

function splitTrailingPunctuation(urlText) {
    const match = String(urlText || '').match(/^(.+?)([),.!?;:]*)$/);
    if (!match) {
        return { linkText: String(urlText || ''), trailing: '' };
    }

    return {
        linkText: match[1],
        trailing: match[2]
    };
}

function normalizeExternalUrl(urlText) {
    const trimmed = String(urlText || '').trim();
    if (trimmed === '') {
        return '';
    }

    const candidate = trimmed.startsWith('www.') ? `https://${trimmed}` : trimmed;

    try {
        const parsed = new URL(candidate);
        if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
            return '';
        }

        return parsed.toString();
    } catch (error) {
        return '';
    }
}

function convertDescriptionToHtml(descriptionText) {
    const text = String(descriptionText || '').trim();
    if (text === '') {
        return '<p class="degree-program-details-empty">No additional details are available for this degree program yet.</p>';
    }

    const urlPattern = /((?:https?:\/\/|www\.)[^\s<]+)/gi;
    let html = '';
    let cursor = 0;

    text.replace(urlPattern, (rawMatch, _group, offset) => {
        html += escapeHtml(text.slice(cursor, offset));

        const { linkText, trailing } = splitTrailingPunctuation(rawMatch);
        const safeHref = normalizeExternalUrl(linkText);

        if (safeHref !== '') {
            html += `<a href="${escapeHtml(safeHref)}" target="_blank" rel="noopener noreferrer">${escapeHtml(linkText)}</a>`;
        } else {
            html += escapeHtml(linkText);
        }

        html += escapeHtml(trailing);
        cursor = offset + rawMatch.length;
        return rawMatch;
    });

    html += escapeHtml(text.slice(cursor));
    return html.replace(/\r\n|\r|\n/g, '<br>');
}

function normalizeSubjectRequirement(requirement) {
    const subjectName = String(requirement?.subject_name || requirement?.subject || '').trim();
    if (subjectName === '') {
        return null;
    }

    const minGrade = String(requirement?.min_grade || requirement?.grade || 'S').trim().toUpperCase() || 'S';

    return {
        subject_name: subjectName,
        min_grade: minGrade
    };
}

function parseSubjectRequirementsText(requirementsText) {
    const lines = String(requirementsText || '').split(/\r\n|\r|\n/);
    const requirements = [];

    lines.forEach((line) => {
        const trimmedLine = line.trim();
        if (trimmedLine === '') {
            return;
        }

        let parts;
        if (trimmedLine.includes('|')) {
            parts = trimmedLine.split('|', 2);
        } else if (trimmedLine.includes(':')) {
            parts = trimmedLine.split(':', 2);
        } else {
            parts = [trimmedLine, 'S'];
        }

        const normalizedRequirement = normalizeSubjectRequirement({
            subject_name: parts[0],
            min_grade: parts[1]
        });

        if (normalizedRequirement) {
            requirements.push(normalizedRequirement);
        }
    });

    return requirements;
}

function normalizeRequirementPath(path, index = 0) {
    if (!path || typeof path !== 'object') {
        return null;
    }

    const pathIdValue = Number(path.path_id || path.id || (index + 1));
    const pathDescription = String(path.path_description || path.description || '').trim();
    const rawRequirements = Array.isArray(path.subject_requirements)
        ? path.subject_requirements
        : (Array.isArray(path.requirements) ? path.requirements : []);

    const requirements = rawRequirements
        .map(normalizeSubjectRequirement)
        .filter(Boolean);

    if (requirements.length === 0 && pathDescription === '') {
        return null;
    }

    return {
        path_id: Number.isFinite(pathIdValue) ? pathIdValue : (index + 1),
        path_description: pathDescription,
        requirements
    };
}

function parseRequirementPathsFromText(requirementsText) {
    const text = String(requirementsText || '').trim();
    if (text === '') {
        return [];
    }

    const blocks = text
        .split(/(?:\r?\n\s*){2,}/)
        .map((block) => block.trim())
        .filter((block) => block !== '');

    if (blocks.length === 0) {
        return [];
    }

    return blocks.map((block, index) => ({
        path_id: index + 1,
        path_description: '',
        requirements: parseSubjectRequirementsText(block)
    })).filter((path) => path.requirements.length > 0);
}

function getRequirementPaths(program) {
    const directPaths = Array.isArray(program.subject_requirement_paths)
        ? program.subject_requirement_paths.map(normalizeRequirementPath).filter(Boolean)
        : [];

    if (directPaths.length > 0) {
        return directPaths;
    }

    const parsedPathsFromText = parseRequirementPathsFromText(program.subject_requirements_text);
    if (parsedPathsFromText.length > 0) {
        return parsedPathsFromText;
    }

    const flatRequirements = Array.isArray(program.subject_requirements)
        ? program.subject_requirements.map(normalizeSubjectRequirement).filter(Boolean)
        : [];

    if (flatRequirements.length === 0) {
        return [];
    }

    return [{
        path_id: 1,
        path_description: String(program.path_description || '').trim(),
        requirements: flatRequirements
    }];
}

function buildSubjectRequirementsHtml(program) {
    const requirementPaths = getRequirementPaths(program);
    if (requirementPaths.length === 0) {
        return '<p class="degree-program-details-empty">Subject requirements are not available for this degree program yet.</p>';
    }

    return requirementPaths.map((path, index) => {
        const title = String(path.path_description || '').trim() || `Path ${index + 1}`;

        const listHtml = path.requirements.map((requirement) => `
            <li class="degree-program-requirement-item">
                <span class="subject">${escapeHtml(requirement.subject_name)}</span>
                <span class="grade">${escapeHtml(requirement.min_grade || 'S')}</span>
            </li>
        `).join('');

        return `
            <div class="degree-program-requirement-path">
                <p class="degree-program-path-title">${escapeHtml(title)}</p>
                <ul class="degree-program-requirements-list">${listHtml}</ul>
            </div>
        `;
    }).join('');
}

function mergeProgramDetails(existingProgram, detailData) {
    const merged = {
        ...existingProgram,
        name: detailData.name || existingProgram.name,
        stream: formatStreamLabel(detailData.stream || existingProgram.stream),
        unicode: detailData.unicode || existingProgram.unicode,
        duration: String(detailData.duration || existingProgram.duration || '').trim(),
        description: String(detailData.descriptions || detailData.description || existingProgram.description || '').trim(),
        detailDescription: String(
            detailData.descriptions
            || detailData.description
            || existingProgram.detailDescription
            || existingProgram.description
            || ''
        ).trim(),
        path_description: String(detailData.path_description || existingProgram.path_description || '').trim(),
        subject_requirement_paths: Array.isArray(detailData.subject_requirement_paths)
            ? detailData.subject_requirement_paths
            : (Array.isArray(existingProgram.subject_requirement_paths) ? existingProgram.subject_requirement_paths : []),
        subject_requirements: Array.isArray(detailData.subject_requirements)
            ? detailData.subject_requirements
            : (Array.isArray(existingProgram.subject_requirements) ? existingProgram.subject_requirements : []),
        subject_requirements_text: String(detailData.subject_requirements_text || existingProgram.subject_requirements_text || '').trim(),
        detailsLoaded: true
    };

    if (merged.detailDescription === '') {
        merged.detailDescription = 'Open this degree to explore detailed entry requirements.';
    }

    merged.cardNote = createDescriptionSnippet(merged.detailDescription);
    return merged;
}

async function fetchDegreeProgramDetails(programId) {
    const response = await fetch(
        buildApiUrl('ProgramController', 'getDegreeProgramDetails', { program_id: programId }),
        { credentials: 'same-origin' }
    );

    if (!response.ok) {
        throw new Error(`Details request failed (${response.status})`);
    }

    const result = await response.json();
    if (!result.success || !result.data) {
        throw new Error(result.message || 'Failed to load degree details');
    }

    return result.data;
}

function getProgramFromCard(cardElement, programId) {
    if (!cardElement) {
        return null;
    }

    const name = cardElement.querySelector('.card-header h3')?.textContent?.trim() || 'Unknown Program';
    const universityName = cardElement.querySelector('.card-header p')?.textContent?.trim() || 'Unknown University';
    const majorName = cardElement.querySelector('.faculty-name')?.textContent?.trim() || 'General Studies';
    const stream = cardElement.querySelector('.cutoff-info strong')?.textContent?.trim() || 'General';
    const unicode = cardElement.querySelector('.unicode-info strong')?.textContent?.trim() || 'N/A';
    const durationElement = Array.from(cardElement.querySelectorAll('.cutoff-info')).find((element) => element.textContent.includes('Duration:'));
    const duration = durationElement?.querySelector('strong')?.textContent?.trim() || '';
    const detailDescription = cardElement.querySelector('.zscore-cutoff-insight')?.textContent?.trim() || '';

    return {
        program_id: programId,
        name,
        university_name: universityName,
        major_name: majorName,
        stream,
        unicode,
        duration,
        detailDescription,
        path_description: '',
        subject_requirement_paths: [],
        subject_requirements: [],
        subject_requirements_text: '',
        detailsLoaded: false,
        source: cardElement.getAttribute('data-program-source') || 'search'
    };
}

function ensureDegreeDetailsModal() {
    let modal = document.getElementById('degreeProgramDetailsModal');
    if (modal) {
        return modal;
    }

    modal = document.createElement('div');
    modal.id = 'degreeProgramDetailsModal';
    modal.className = 'degree-program-details-modal';
    modal.innerHTML = `
        <div class="degree-program-details-backdrop" data-close-degree-details="true"></div>
        <div class="degree-program-details-panel" role="dialog" aria-modal="true" aria-labelledby="degreeProgramDetailsTitle">
            <button type="button" class="degree-program-details-close" data-close-degree-details="true" aria-label="Close details">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>

            <div class="degree-program-details-header">
                <h3 id="degreeProgramDetailsTitle"></h3>
                <p id="degreeProgramDetailsUniversity"></p>
            </div>

            <div class="degree-program-details-meta">
                <div class="detail-chip">
                    <span class="label">Major</span>
                    <span class="value" id="degreeProgramDetailsMajor"></span>
                </div>
                <div class="detail-chip">
                    <span class="label">Stream</span>
                    <span class="value" id="degreeProgramDetailsStream"></span>
                </div>
                <div class="detail-chip">
                    <span class="label">Unicode</span>
                    <span class="value" id="degreeProgramDetailsUnicode"></span>
                </div>
                <div class="detail-chip">
                    <span class="label">Duration</span>
                    <span class="value" id="degreeProgramDetailsDuration"></span>
                </div>
            </div>

            <div class="degree-program-details-body">
                <h4>Program Details</h4>
                <div id="degreeProgramDetailsDescription" class="degree-program-details-description"></div>
            </div>

            <div class="degree-program-details-subjects">
                <h4>Required Subjects</h4>
                <div id="degreeProgramDetailsSubjects" class="degree-program-details-requirements"></div>
            </div>
        </div>
    `;

    modal.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        if (event.target.closest('[data-close-degree-details="true"]')) {
            closeDegreeProgramDetails();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeDegreeProgramDetails();
        }
    });

    document.body.appendChild(modal);
    return modal;
}

function closeDegreeProgramDetails() {
    const modal = document.getElementById('degreeProgramDetailsModal');
    if (!modal) {
        return;
    }

    modal.classList.remove('is-open');
    document.body.classList.remove('degree-details-open');
}

async function openDegreeProgramDetails(programId, cardElement = null) {
    const modal = ensureDegreeDetailsModal();

    const fallbackProgram = getProgramFromCard(cardElement, programId);
    let program = degreeProgramDetailsById.get(programId) || fallbackProgram;

    if (!program) {
        showMessage('Program details are not available right now.', 'error');
        return;
    }

    degreeProgramDetailsById.set(programId, program);

    const titleElement = modal.querySelector('#degreeProgramDetailsTitle');
    const universityElement = modal.querySelector('#degreeProgramDetailsUniversity');
    const majorElement = modal.querySelector('#degreeProgramDetailsMajor');
    const streamElement = modal.querySelector('#degreeProgramDetailsStream');
    const unicodeElement = modal.querySelector('#degreeProgramDetailsUnicode');
    const durationElement = modal.querySelector('#degreeProgramDetailsDuration');
    const descriptionElement = modal.querySelector('#degreeProgramDetailsDescription');
    const subjectsElement = modal.querySelector('#degreeProgramDetailsSubjects');

    if (!titleElement || !universityElement || !majorElement || !streamElement || !unicodeElement || !durationElement || !descriptionElement || !subjectsElement) {
        return;
    }

    const renderModal = (programData) => {
        titleElement.textContent = programData.name || 'Degree Program';
        universityElement.textContent = programData.university_name || 'University information unavailable';
        majorElement.textContent = programData.major_name || 'General Studies';
        streamElement.textContent = programData.stream || 'General';
        unicodeElement.textContent = programData.unicode || 'N/A';
        durationElement.textContent = programData.duration || 'Not specified';
        descriptionElement.innerHTML = convertDescriptionToHtml(programData.detailDescription || programData.description || '');
        subjectsElement.innerHTML = buildSubjectRequirementsHtml(programData);
    };

    renderModal(program);

    modal.classList.add('is-open');
    document.body.classList.add('degree-details-open');

    if (program.detailsLoaded) {
        return;
    }

    subjectsElement.innerHTML = '<p class="degree-program-details-empty">Loading required subjects...</p>';

    try {
        const detailData = await fetchDegreeProgramDetails(programId);
        program = mergeProgramDetails(program, detailData);
        degreeProgramDetailsById.set(programId, program);

        if (modal.classList.contains('is-open')) {
            renderModal(program);
        }
    } catch (error) {
        console.error('Error loading degree program details:', error);
        if (modal.classList.contains('is-open')) {
            subjectsElement.innerHTML = '<p class="degree-program-details-empty">Subject requirements are not available right now.</p>';
        }
    }
}

function bindCardDetailsEvents() {
    const pageRoot = document.querySelector('.degree-programs-page');
    if (!pageRoot || pageRoot.dataset.degreeDetailsBound === '1') {
        return;
    }

    pageRoot.dataset.degreeDetailsBound = '1';

    pageRoot.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        if (event.target.closest('.wishlist-btn')) {
            return;
        }

        const card = event.target.closest('.degree-program-card');
        if (!card || !pageRoot.contains(card)) {
            return;
        }

        const programId = Number(card.getAttribute('data-program-id'));
        if (!Number.isFinite(programId) || programId <= 0) {
            return;
        }

        openDegreeProgramDetails(programId, card);
    });

    pageRoot.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        if (!(event.target instanceof Element)) {
            return;
        }

        if (event.target.closest('.wishlist-btn')) {
            return;
        }

        const card = event.target.closest('.degree-program-card');
        if (!card || !pageRoot.contains(card)) {
            return;
        }

        event.preventDefault();

        const programId = Number(card.getAttribute('data-program-id'));
        if (!Number.isFinite(programId) || programId <= 0) {
            return;
        }

        openDegreeProgramDetails(programId, card);
    });
}

function getSearchElements() {
    return {
        searchInput: document.getElementById('programSearchInput'),
        searchBtn: document.getElementById('searchBtn'),
        searchResults: document.getElementById('searchResults'),
        wishlistResults: document.getElementById('wishlistResults'),
        searchSection: document.getElementById('searchSection'),
        loading: document.getElementById('loading'),
        toggleAdvanced: document.getElementById('toggleAdvanced'),
        clearFilters: document.getElementById('clearFilters'),
        advancedSearch: document.getElementById('advancedSearch'),
        universityFilter: document.getElementById('universityFilter'),
        streamFilter: document.getElementById('streamFilter'),
        majorFilter: document.getElementById('majorFilter'),
        unicodeFilter: document.getElementById('unicodeFilter')
    };
}

function setStreamFilterByKeyword(streamFilter, keyword) {
    if (!streamFilter) {
        return;
    }

    const normalizedKeyword = String(keyword || '').toLowerCase();
    const matched = Array.from(streamFilter.options).find((option) => {
        const optionValue = String(option.value || '').toLowerCase();
        const optionText = String(option.textContent || '').toLowerCase();
        return optionValue.includes(normalizedKeyword) || optionText.includes(normalizedKeyword);
    });

    streamFilter.value = matched ? matched.value : '';
}

function applyQuickFilter(filterKey, elements, chips) {
    const { searchInput, streamFilter, advancedSearch, universityFilter } = elements;

    chips.forEach((chip) => {
        chip.classList.toggle('active', chip.dataset.filter === filterKey);
    });

    if (filterKey === 'university') {
        if (advancedSearch) {
            advancedSearch.style.display = 'block';
        }
        if (universityFilter) {
            universityFilter.focus();
        }
        performSearch();
        return;
    }

    if (filterKey === 'science') {
        if (searchInput) {
            searchInput.value = 'science';
        }
        if (streamFilter) {
            streamFilter.value = '';
        }
        performSearch();
        return;
    }

    if (searchInput) {
        searchInput.value = '';
    }

    if (filterKey === 'technology' || filterKey === 'commerce' || filterKey === 'arts') {
        setStreamFilterByKeyword(streamFilter, filterKey);
    }

    performSearch();
}

function clearSearchResultsToDefault() {
    const { searchResults } = getSearchElements();
    if (!searchResults) {
        return;
    }

    renderNoSearchMessage(
        searchResults,
        'Start Your Search',
        'Enter a program name, university, or major to find degree programs.',
        '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>'
    );
}

function initializeDegreePrograms() {
    const elements = getSearchElements();

    if (!elements.searchInput || !elements.searchBtn) {
        setTimeout(initializeDegreePrograms, 200);
        return;
    }

    if (degreeProgramsInitialized) {
        return;
    }

    degreeProgramsInitialized = true;

    const {
        searchInput,
        searchBtn,
        searchResults,
        wishlistResults,
        searchSection,
        toggleAdvanced,
        clearFilters,
        advancedSearch,
        universityFilter,
        streamFilter,
        majorFilter,
        unicodeFilter
    } = elements;

    const debouncedInputSearch = debounce(performSearch, 350);

    bindCardDetailsEvents();
    decorateDegreeCards(document);

    document.querySelectorAll('.mode-btn').forEach((button) => {
        button.addEventListener('click', function onModeClick() {
            const mode = this.dataset.mode;
            document.querySelectorAll('.mode-btn').forEach((item) => item.classList.remove('active'));
            this.classList.add('active');

            if (mode === 'wishlist') {
                searchSection.style.display = 'none';
                searchResults.style.display = 'none';
                wishlistResults.style.display = 'grid';
                refreshWishlistPage();
                return;
            }

            searchSection.style.display = 'block';
            searchResults.style.display = 'grid';
            wishlistResults.style.display = 'none';
        });
    });

    searchBtn.addEventListener('click', performSearch);
    searchInput.addEventListener('keypress', (event) => {
        if (event.key === 'Enter') {
            performSearch();
        }
    });
    searchInput.addEventListener('input', debouncedInputSearch);

    if (toggleAdvanced) {
        toggleAdvanced.addEventListener('click', function onToggleAdvanced() {
            const isVisible = advancedSearch && advancedSearch.style.display === 'block';
            if (advancedSearch) {
                advancedSearch.style.display = isVisible ? 'none' : 'block';
            }
            this.innerHTML = isVisible
                ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>Advanced Search'
                : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>Hide Filters';
        });
    }

    [universityFilter, streamFilter, majorFilter].forEach((filterElement) => {
        if (filterElement) {
            filterElement.addEventListener('change', performSearch);
        }
    });

    if (unicodeFilter) {
        unicodeFilter.addEventListener('input', debounce(performSearch, 300));
    }

    const quickFilterChips = Array.from(document.querySelectorAll('.filter-chip'));
    quickFilterChips.forEach((chip) => {
        chip.addEventListener('click', function onQuickFilterClick() {
            const target = this.dataset.filter || '';
            const wasActive = this.classList.contains('active');

            if (wasActive) {
                quickFilterChips.forEach((item) => item.classList.remove('active'));
                if (streamFilter) {
                    streamFilter.value = '';
                }
                performSearch();
                return;
            }

            applyQuickFilter(target, elements, quickFilterChips);
        });
    });

    if (clearFilters) {
        clearFilters.addEventListener('click', function onClearFilters() {
            searchInput.value = '';
            if (universityFilter) universityFilter.value = '';
            if (streamFilter) streamFilter.value = '';
            if (majorFilter) majorFilter.value = '';
            if (unicodeFilter) unicodeFilter.value = '';
            quickFilterChips.forEach((chip) => chip.classList.remove('active'));
            clearSearchResultsToDefault();
        });
    }

    loadSearchFilters();
    updateWishlistCount();
}

setTimeout(initializeDegreePrograms, 100);

async function loadSearchFilters() {
    try {
        const response = await fetch(buildApiUrl('ProgramController', 'getSearchFilters'), {
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`Failed to load filters (${response.status})`);
        }

        const result = await response.json();
        if (!result.success) {
            return;
        }

        const { universityFilter, majorFilter } = getSearchElements();

        if (universityFilter && Array.isArray(result.data?.universities)) {
            universityFilter.innerHTML = '<option value="">All Universities</option>';
            result.data.universities.forEach((university) => {
                const option = document.createElement('option');
                option.value = university.university_id;
                option.textContent = university.name;
                universityFilter.appendChild(option);
            });
        }

        if (majorFilter && Array.isArray(result.data?.majors)) {
            majorFilter.innerHTML = '<option value="">All Majors</option>';
            result.data.majors.forEach((major) => {
                const option = document.createElement('option');
                option.value = major.major_id;
                option.textContent = major.name;
                majorFilter.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading filters:', error);
    }
}

async function performSearch() {
    const { searchInput, universityFilter, streamFilter, majorFilter, unicodeFilter, searchResults, loading } = getSearchElements();
    if (!searchInput || !searchResults || !loading) {
        return;
    }

    const params = {
        q: searchInput.value.trim(),
        university_id: universityFilter ? universityFilter.value : '',
        stream: streamFilter ? streamFilter.value : '',
        major_id: majorFilter ? majorFilter.value : '',
        unicode_code: unicodeFilter ? unicodeFilter.value.trim() : ''
    };

    const requestId = ++latestSearchRequest;
    loading.style.display = 'block';
    searchResults.innerHTML = '';

    try {
        const response = await fetch(buildApiUrl('ProgramController', 'searchPrograms', params), {
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`Search request failed with status ${response.status}`);
        }

        const result = await response.json();
        if (requestId !== latestSearchRequest) {
            return;
        }

        if (!result.success) {
            searchResults.innerHTML = `<p class="error">Error: ${escapeHtml(result.message || 'Search failed')}</p>`;
            return;
        }

        const programs = Array.isArray(result.data) ? result.data : [];
        renderPrograms(searchResults, programs, 'search');
        if (programs.length > 0) {
            initializeWishlistStatus();
        }
    } catch (error) {
        console.error('Search error:', error);
        if (requestId === latestSearchRequest) {
            searchResults.innerHTML = '<p class="error">Search failed. Please try again.</p>';
        }
    } finally {
        if (requestId === latestSearchRequest) {
            loading.style.display = 'none';
        }
    }
}

function debounce(func, wait) {
    let timeout;
    return function debouncedFunction(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

function getWishlistButtonsByProgram(programId) {
    return document.querySelectorAll(`.degree-program-card[data-program-id="${programId}"] .wishlist-btn`);
}

function getOutlineHeartSvg() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>';
}

function getFilledHeartSvg() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>';
}

function updateHeartIcon(programId, isInWishlist) {
    const heartButtons = getWishlistButtonsByProgram(programId);
    heartButtons.forEach((heartBtn) => {
        if (isInWishlist) {
            heartBtn.classList.add('in-wishlist');
            heartBtn.innerHTML = getFilledHeartSvg();
            heartBtn.setAttribute('aria-label', 'Remove from wishlist');
        } else {
            heartBtn.classList.remove('in-wishlist');
            heartBtn.innerHTML = getOutlineHeartSvg();
            heartBtn.setAttribute('aria-label', 'Add to wishlist');
        }
    });
}

function setHeartLoading(programId, isLoading) {
    const heartButtons = getWishlistButtonsByProgram(programId);
    heartButtons.forEach((heartBtn) => {
        if (isLoading) {
            heartBtn.disabled = true;
            heartBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>';
            return;
        }

        heartBtn.disabled = false;
    });
}

function showMessage(message, type = 'info') {
    const existingMessages = document.querySelectorAll('.wishlist-message');
    existingMessages.forEach((element) => element.remove());

    const messageDiv = document.createElement('div');
    messageDiv.className = `wishlist-message wishlist-message-${type}`;
    messageDiv.innerHTML = `
        <div class="message-content">
            <span class="message-text">${escapeHtml(message)}</span>
            <button class="message-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;

    document.body.appendChild(messageDiv);

    setTimeout(() => {
        if (messageDiv.parentElement) {
            messageDiv.remove();
        }
    }, 3000);
}

async function checkWishlistStatus(programId) {
    try {
        const response = await fetch(buildApiUrl('WishlistController', 'checkWishlist', { program_id: programId }), {
            credentials: 'same-origin'
        });

        if (!response.ok) {
            return false;
        }

        const result = await response.json();
        return Boolean(result.success && result.data && result.data.isInWishlist);
    } catch (error) {
        console.error('Error checking wishlist status:', error);
        return false;
    }
}

async function addToWishlist(programId) {
    try {
        const response = await fetch(buildApiUrl('WishlistController', 'addToWishlist'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ program_id: programId })
        });

        return await response.json();
    } catch (error) {
        console.error('Error adding to wishlist:', error);
        return { success: false, message: 'Network error' };
    }
}

async function removeFromWishlist(programId) {
    try {
        const response = await fetch(buildApiUrl('WishlistController', 'removeFromWishlist'), {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ program_id: programId })
        });

        return await response.json();
    } catch (error) {
        console.error('Error removing from wishlist:', error);
        return { success: false, message: 'Network error' };
    }
}

async function toggleWishlist(programId) {
    const numericProgramId = Number(programId);
    if (!Number.isFinite(numericProgramId) || numericProgramId <= 0) {
        return;
    }

    const heartButtons = getWishlistButtonsByProgram(numericProgramId);
    if (heartButtons.length === 0) {
        return;
    }

    const isInWishlist = heartButtons[0].classList.contains('in-wishlist');
    setHeartLoading(numericProgramId, true);

    try {
        const result = isInWishlist
            ? await removeFromWishlist(numericProgramId)
            : await addToWishlist(numericProgramId);

        if (!result.success) {
            showMessage(result.message || 'Wishlist operation failed', 'error');
            return;
        }

        updateHeartIcon(numericProgramId, !isInWishlist);
        updateWishlistCount();

        const wishlistResults = document.getElementById('wishlistResults');
        if (wishlistResults && wishlistResults.style.display !== 'none') {
            await refreshWishlistPage();
        }

        showMessage(isInWishlist ? 'Removed from wishlist' : 'Added to wishlist', 'success');
    } catch (error) {
        console.error('Wishlist toggle error:', error);
        showMessage('Something went wrong. Please try again.', 'error');
    } finally {
        setHeartLoading(numericProgramId, false);
    }
}

async function initializeWishlistStatus() {
    const searchResults = document.getElementById('searchResults');
    if (!searchResults) {
        return;
    }

    const cards = Array.from(searchResults.querySelectorAll('.degree-program-card'));
    await Promise.all(cards.map(async (card) => {
        const programId = Number(card.getAttribute('data-program-id'));
        if (!Number.isFinite(programId) || programId <= 0) {
            return;
        }

        const isInWishlist = await checkWishlistStatus(programId);
        updateHeartIcon(programId, isInWishlist);
    }));
}

async function updateWishlistCount() {
    try {
        const response = await fetch(buildApiUrl('WishlistController', 'getWishlistCount'), {
            credentials: 'same-origin'
        });

        if (!response.ok) {
            return;
        }

        const result = await response.json();
        if (!result.success) {
            return;
        }

        const wishlistBtn = document.querySelector('[data-mode="wishlist"]');
        if (!wishlistBtn) {
            return;
        }

        wishlistBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            My Wishlist (${result.data.count})
        `;
    } catch (error) {
        console.error('Error updating wishlist count:', error);
    }
}

async function addProgramToWishlistPage(programId) {
    await refreshWishlistPage();
}

async function refreshWishlistPage() {
    const wishlistResults = document.getElementById('wishlistResults');
    if (!wishlistResults) {
        return;
    }

    try {
        wishlistResults.innerHTML = '<div class="loading"><div class="spinner"></div><p>Loading wishlist...</p></div>';

        const response = await fetch(buildApiUrl('WishlistController', 'getWishlistItems'), {
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`Wishlist request failed with status ${response.status}`);
        }

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'Failed to load wishlist');
        }

        renderPrograms(wishlistResults, Array.isArray(result.data) ? result.data : [], 'wishlist');
    } catch (error) {
        console.error('Error refreshing wishlist page:', error);
        wishlistResults.innerHTML = '<p class="error">Failed to load wishlist. Please try again.</p>';
    }
}
