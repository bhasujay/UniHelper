// degree-programs.js - Search functionality for degree programs page

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('programSearchInput');
    const searchBtn = document.getElementById('searchBtn');
    const searchResults = document.getElementById('searchResults');
    const loading = document.getElementById('loading');
    const filterChips = document.querySelectorAll('.filter-chip');
    
    let searchTimeout;
    let currentSearchTerm = '';
    let currentFilter = '';
    
    // Initialize global search
    if (searchInput) {
        // Real-time search with debounce
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            currentSearchTerm = searchTerm;
            
            if (searchTerm.length >= 2) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    performGlobalSearch(searchTerm, currentFilter);
                }, 300);
            } else {
                hideSearchResults();
            }
        });
        
        // Search button click
        if (searchBtn) {
            searchBtn.addEventListener('click', function() {
                const searchTerm = searchInput.value.trim();
                if (searchTerm.length >= 2) {
                    performGlobalSearch(searchTerm, currentFilter);
                }
            });
        }
        
        // Enter key search
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm.length >= 2) {
                    performGlobalSearch(searchTerm, currentFilter);
                }
            }
        });
        
        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResultsDropdown.contains(e.target)) {
                hideSearchResults();
            }
        });
    }
    
    // Filter chips functionality
    filterChips.forEach(chip => {
        chip.addEventListener('click', function() {
            // Toggle active state
            filterChips.forEach(c => c.classList.remove('active'));
            this.classList.toggle('active');
            
            // Set current filter
            if (this.classList.contains('active')) {
                currentFilter = this.dataset.filter;
            } else {
                currentFilter = '';
            }
            
            // Perform search if there's a search term
            if (currentSearchTerm.length >= 2) {
                performGlobalSearch(currentSearchTerm, currentFilter);
            }
        });
    });
    
    // View all results button
    if (viewAllBtn) {
        viewAllBtn.addEventListener('click', function() {
            // Navigate to degree programs page with search
            window.location.href = `/UniHelper/dashboard/applicant/degree-programs?search=${encodeURIComponent(currentSearchTerm)}`;
        });
    }
});

// Perform global search
async function performGlobalSearch(searchTerm, filter = '') {
    try {
        showSearchLoading();
        
        const params = new URLSearchParams();
        params.append('q', searchTerm);
        if (filter) {
            // Map filter chips to actual filter values
            const filterMap = {
                'university': 'university',
                'technology': 'Technology',
                'science': 'physical Science',
                'commerce': 'Commerce'
            };
            
            if (filterMap[filter]) {
                if (filter === 'university') {
                    // For university filter, we'll search by university name
                    params.append('university_search', 'true');
                } else {
                    params.append('stream', filterMap[filter]);
                }
            }
        }
        
        const response = await fetch(`/UniHelper/api/programs/search?${params}`);
        const result = await response.json();
        
        if (result.success) {
            displayGlobalSearchResults(result.data, searchTerm);
        } else {
            showSearchError(result.message);
        }
    } catch (error) {
        console.error('Global search error:', error);
        showSearchError('Search failed. Please try again.');
    }
}

// Display global search results
function displayGlobalSearchResults(programs, searchTerm) {
    const searchResultsList = document.getElementById('searchResultsList');
    const resultsCount = document.getElementById('resultsCount');
    const searchResultsDropdown = document.getElementById('searchResultsDropdown');
    
    if (programs.length === 0) {
        searchResultsList.innerHTML = `
            <div class="no-results-item">
                <div style="margin-bottom: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <p>No programs found for "${searchTerm}"</p>
            </div>
        `;
        resultsCount.textContent = '0 results';
    } else {
        const html = programs.slice(0, 5).map(program => `
            <div class="search-result-item" onclick="selectSearchResult(${program.program_id}, '${program.name}')">
                <div class="search-result-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5"></path>
                        <path d="M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <div class="search-result-content">
                    <div class="search-result-title">${highlightSearchTerm(program.name, searchTerm)}</div>
                    <div class="search-result-subtitle">${program.university_name}</div>
                    <div class="search-result-meta">${program.major_name} • ${program.stream}</div>
                </div>
            </div>
        `).join('');
        
        searchResultsList.innerHTML = html;
        resultsCount.textContent = `${programs.length} result${programs.length !== 1 ? 's' : ''}`;
    }
    
    searchResultsDropdown.style.display = 'block';
}

// Show search loading state
function showSearchLoading() {
    const searchResultsList = document.getElementById('searchResultsList');
    const resultsCount = document.getElementById('resultsCount');
    const searchResultsDropdown = document.getElementById('searchResultsDropdown');
    
    searchResultsList.innerHTML = `
        <div class="search-loading">
            <div class="spinner"></div>
            <p>Searching...</p>
        </div>
    `;
    resultsCount.textContent = 'Searching...';
    searchResultsDropdown.style.display = 'block';
}

// Show search error
function showSearchError(message) {
    const searchResultsList = document.getElementById('searchResultsList');
    const resultsCount = document.getElementById('resultsCount');
    const searchResultsDropdown = document.getElementById('searchResultsDropdown');
    
    searchResultsList.innerHTML = `
        <div class="no-results-item">
            <p style="color: #dc3545;">${message}</p>
        </div>
    `;
    resultsCount.textContent = 'Error';
    searchResultsDropdown.style.display = 'block';
}

// Hide search results
function hideSearchResults() {
    const searchResultsDropdown = document.getElementById('searchResultsDropdown');
    searchResultsDropdown.style.display = 'none';
}

// Select search result
function selectSearchResult(programId, programName) {
    // Navigate to degree programs page with the selected program
    window.location.href = `/UniHelper/dashboard/applicant/degree-programs?highlight=${programId}`;
}

// Highlight search term in results
function highlightSearchTerm(text, searchTerm) {
    if (!searchTerm) return text;
    
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    return text.replace(regex, '<strong>$1</strong>');
}

// Initialize search from URL parameters
function initializeSearchFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');
    const highlightParam = urlParams.get('highlight');
    
    if (searchParam && document.getElementById('globalSearchInput')) {
        document.getElementById('globalSearchInput').value = searchParam;
        performGlobalSearch(searchParam);
    }
    
    if (highlightParam) {
        // Scroll to and highlight the specific program
        setTimeout(() => {
            const programCard = document.querySelector(`[data-program-id="${highlightParam}"]`);
            if (programCard) {
                programCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                programCard.style.border = '2px solid #007bff';
                programCard.style.boxShadow = '0 0 0 3px rgba(0, 123, 255, 0.1)';
                
                // Remove highlight after 3 seconds
                setTimeout(() => {
                    programCard.style.border = '';
                    programCard.style.boxShadow = '';
                }, 3000);
            }
        }, 500);
    }
}

// Initialize search from URL when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeSearchFromURL();
});
