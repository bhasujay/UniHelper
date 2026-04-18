// degree-programs.js - Degree search and wishlist interactions
console.log('degree-programs.js loaded');

const API_BASE = '/UniHelper/api';
let degreeProgramsInitialized = false;
let latestSearchRequest = 0;

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
        eligibility: program.eligibility || null,
        source
    };

    normalized.cardNote = normalized.description !== ''
        ? normalized.description
        : (normalized.source === 'wishlist'
            ? 'Saved from your wishlist for quick access.'
            : 'Open this degree to explore detailed entry requirements.');

    return normalized;
}

function getProgramCardHtml(rawProgram, index, source) {
    const program = normalizeProgram(rawProgram, source);
    const badge = getEligibilityBadge(program.eligibility, source);

    const programId = Number.isFinite(program.program_id) ? program.program_id : 0;
    const isWishlistCard = source === 'wishlist';
    const heartClass = isWishlistCard ? 'zscore-program-action wishlist-btn in-wishlist' : 'zscore-program-action wishlist-btn';
    const heartAria = isWishlistCard ? 'Remove from wishlist' : 'Add to wishlist';

    return `
        <div class="degree-program-card" data-program-id="${programId}">
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
                    <button class="${heartClass}" onclick="toggleWishlist(${programId})" aria-label="${heartAria}">
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

    container.innerHTML = programs.map((program, index) => getProgramCardHtml(program, index, source)).join('');
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
