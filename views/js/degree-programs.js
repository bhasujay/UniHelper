// degree-programs.js - Real-time search functionality for degree programs
console.log('degree-programs.js loaded');

// Run immediately when script loads, not waiting for DOMContentLoaded
function initializeDegreePrograms() {
    console.log('🚀 Initializing degree programs...');
    
    // Debug: Check what elements actually exist
    console.log('🔍 Debugging elements:');
    console.log('All elements with id="programSearchInput":', document.querySelectorAll('#programSearchInput'));
    console.log('All elements with id="searchBtn":', document.querySelectorAll('#searchBtn'));
    console.log('All input elements:', document.querySelectorAll('input'));
    console.log('All button elements:', document.querySelectorAll('button'));
    
    const searchInput = document.getElementById('programSearchInput');
    const searchBtn = document.getElementById('searchBtn');
    
    console.log('searchInput found:', !!searchInput);
    console.log('searchBtn found:', !!searchBtn);
    
    // If elements not found, wait and try again
    if (!searchInput || !searchBtn) {
        console.log('⏳ Elements not found, retrying in 200ms...');
        setTimeout(initializeDegreePrograms, 200);
        return;
    }
    
    console.log('✅ Elements found, continuing initialization...');
    const toggleAdvanced = document.getElementById('toggleAdvanced');
    const clearFilters = document.getElementById('clearFilters');
    const advancedSearch = document.getElementById('advancedSearch');
    const searchResults = document.getElementById('searchResults');
    const wishlistResults = document.getElementById('wishlistResults');
    const searchSection = document.getElementById('searchSection');
    const loading = document.getElementById('loading');
    
    // Mode toggle functionality
    const modeButtons = document.querySelectorAll('.mode-btn');
    modeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const mode = this.dataset.mode;
            
            // Update active button
            modeButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            if (mode === 'search') {
                searchSection.style.display = 'block';
                searchResults.style.display = 'block';
                wishlistResults.style.display = 'none';
            } else if (mode === 'wishlist') {
                searchSection.style.display = 'none';
                searchResults.style.display = 'none';
                wishlistResults.style.display = 'block';
                // Always refresh wishlist when switching to it to show latest data
                console.log('Switching to wishlist page - refreshing data...');
                refreshWishlistPage();
            }
        });
    });
    
    // Load filters on page load
    loadSearchFilters();
    
    // Search button click
    if (searchBtn) {
        searchBtn.addEventListener('click', performSearch);
    }
    
    // Enter key in search input
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
        
        // Real-time search with debounce
        searchInput.addEventListener('input', debounce(performSearch, 500));
    }
    
    // Toggle advanced search
    if (toggleAdvanced) {
        toggleAdvanced.addEventListener('click', function() {
            const isVisible = advancedSearch.style.display === 'block';
            advancedSearch.style.display = isVisible ? 'none' : 'block';
            this.innerHTML = isVisible ? 
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>Advanced Search' :
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>Hide Filters';
        });
    }
    
    // Clear all filters
    if (clearFilters) {
        clearFilters.addEventListener('click', function() {
            document.getElementById('programSearchInput').value = '';
            document.getElementById('universityFilter').value = '';
            document.getElementById('streamFilter').value = '';
            document.getElementById('majorFilter').value = '';
            document.getElementById('unicodeFilter').value = '';
            searchResults.innerHTML = `
                <div class="no-search-message">
                    <div class="no-search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </div>
                    <h3>Start Your Search</h3>
                    <p>Enter a program name, university, or major to find degree programs</p>
                </div>
            `;
        });
    }
}

// Start initialization immediately when script loads
setTimeout(initializeDegreePrograms, 100);

// Load search filters (universities, majors)
async function loadSearchFilters() {
    try {
        const response = await fetch('/UniHelper/api/programs/filters');
        const result = await response.json();
        
        if (result.success) {
            // Populate university dropdown
            const universityFilter = document.getElementById('universityFilter');
            if (universityFilter && result.data.universities) {
                result.data.universities.forEach(university => {
                    const option = document.createElement('option');
                    option.value = university.university_id;
                    option.textContent = university.name;
                    universityFilter.appendChild(option);
                });
            }
            
            // Populate major dropdown
            const majorFilter = document.getElementById('majorFilter');
            if (majorFilter && result.data.majors) {
                result.data.majors.forEach(major => {
                    const option = document.createElement('option');
                    option.value = major.major_id;
                    option.textContent = major.name;
                    majorFilter.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading filters:', error);
    }
}

// Perform search
async function performSearch() {
    const searchTerm = document.getElementById('programSearchInput').value;
    const universityId = document.getElementById('universityFilter').value;
    const stream = document.getElementById('streamFilter').value;
    const majorId = document.getElementById('majorFilter').value;
    const unicodeCode = document.getElementById('unicodeFilter').value;
    
    // Build search parameters
    const params = new URLSearchParams();
    if (searchTerm.trim()) params.append('q', searchTerm.trim());
    if (universityId) params.append('university_id', universityId);
    if (stream) params.append('stream', stream);
    if (majorId) params.append('major_id', majorId);
    if (unicodeCode.trim()) params.append('unicode_code', unicodeCode.trim());
    
    // Show loading
    loading.style.display = 'block';
    searchResults.innerHTML = '';
    
    try {
        const response = await fetch(`/UniHelper/api/programs/search?${params}`);
        const result = await response.json();
        
        if (result.success) {
            displaySearchResults(result.data);
        } else {
            searchResults.innerHTML = `<p class="error">Error: ${result.message}</p>`;
        }
    } catch (error) {
        console.error('Search error:', error);
        searchResults.innerHTML = '<p class="error">Search failed. Please try again.</p>';
    } finally {
        loading.style.display = 'none';
    }
}

// Display search results
function displaySearchResults(programs) {
    if (programs.length === 0) {
        searchResults.innerHTML = `
            <div class="no-results">
                <div class="no-results-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <h3>No Programs Found</h3>
                <p>No degree programs match your search criteria. Try adjusting your filters or search terms.</p>
            </div>
        `;
        return;
    }
    
    const html = programs.map(program => `
        <div class="degree-program-card" data-program-id="${program.program_id}">
            <div class="card-header">
                <h3>${program.name}</h3>
                <p>${program.university_name}</p>
            </div>
            
            <div class="card-body">
                <p class="faculty-name">${program.major_name}</p>
                
                <div class="degree-metrics">
                    <div class="cutoff-info">Stream: <strong>${program.stream}</strong></div>
                    <div class="unicode-info">Unicode: <strong>${program.unicode_code}</strong></div>
                </div>
                
                <div class="degree-tags">
                    <span class="tag">${program.stream} Stream</span>
                </div>
            </div>
            
            <div class="card-footer">
                <div class="footer-details">
                    <span>Major: <strong>${program.major_name}</strong></span>
                    <span>Unicode: <strong>${program.unicode_code}</strong></span>
                </div>
                <div class="card-actions">
                    <button class="icon-btn wishlist-btn" onclick="toggleWishlist(${program.program_id})" aria-label="Add to Wishlist">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                    </button>
                    <button class="icon-btn" aria-label="View Details">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
    
    searchResults.innerHTML = html;
    
    // Initialize wishlist status for all cards
    initializeWishlistStatus();
}

// Debounce function for real-time search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Toggle wishlist functionality
async function toggleWishlist(programId) {
    const heartBtn = document.querySelector(`[onclick="toggleWishlist(${programId})"]`);
    const isInWishlist = heartBtn.classList.contains('in-wishlist');
    
    // Show loading state
    setHeartLoading(heartBtn, true);
    
    try {
        if (isInWishlist) {
            // Remove from wishlist
            const result = await removeFromWishlist(programId);
            if (result.success) {
                // Update heart icon on search page
                updateHeartIcon(programId, false);
                
                // Always rebuild wishlist if it's visible (whether on search or wishlist page)
                const wishlistResults = document.getElementById('wishlistResults');
                if (wishlistResults && wishlistResults.style.display !== 'none') {
                    console.log('Removing from wishlist - rebuilding wishlist page...');
                    refreshWishlistPage();
                } else {
                    console.log('Wishlist page not visible, skipping rebuild');
                }
                updateWishlistCount(); // Update count immediately
                showMessage('Removed from wishlist', 'success');
            } else {
                showMessage(result.message || 'Failed to remove from wishlist', 'error');
            }
        } else {
            // Add to wishlist
            const result = await addToWishlist(programId);
            if (result.success) {
                updateHeartIcon(programId, true);
                
                // Always rebuild wishlist if it's visible (whether on search or wishlist page)
                const wishlistResults = document.getElementById('wishlistResults');
                if (wishlistResults && wishlistResults.style.display !== 'none') {
                    console.log('Adding to wishlist - rebuilding wishlist page...');
                    refreshWishlistPage();
                } else {
                    console.log('Wishlist page not visible, skipping rebuild');
                }
                
                updateWishlistCount(); // Update count immediately
                showMessage('Added to wishlist', 'success');
            } else {
                showMessage(result.message || 'Failed to add to wishlist', 'error');
            }
        }
    } catch (error) {
        console.error('Wishlist toggle error:', error);
        showMessage('Something went wrong. Please try again.', 'error');
    } finally {
        // Remove loading state
        setHeartLoading(heartBtn, false);
    }
}

// Check if program is in user's wishlist
async function checkWishlistStatus(programId) {
    try {
        const response = await fetch(`/UniHelper/api/wishlist/check?program_id=${programId}`);
        const result = await response.json();
        
        if (result.success) {
            return result.data.isInWishlist;
        } else {
            console.error('Error checking wishlist status:', result.message);
            return false;
        }
    } catch (error) {
        console.error('Error checking wishlist status:', error);
        return false;
    }
}

// Add program to wishlist
async function addToWishlist(programId) {
    try {
        const response = await fetch('/UniHelper/api/wishlist/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                program_id: programId
            })
        });
        
        return await response.json();
    } catch (error) {
        console.error('Error adding to wishlist:', error);
        return { success: false, message: 'Network error' };
    }
}

// Remove program from wishlist
async function removeFromWishlist(programId) {
    try {
        const response = await fetch('/UniHelper/api/wishlist/remove', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                program_id: programId
            })
        });
        
        return await response.json();
    } catch (error) {
        console.error('Error removing from wishlist:', error);
        return { success: false, message: 'Network error' };
    }
}

// Update heart icon based on wishlist status
function updateHeartIcon(programId, isInWishlist) {
    const heartBtn = document.querySelector(`[onclick="toggleWishlist(${programId})"]`);
    if (!heartBtn) return;
    
    if (isInWishlist) {
        // Filled heart (in wishlist)
        heartBtn.classList.add('in-wishlist');
        heartBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
        `;
    } else {
        // Outline heart (not in wishlist)
        heartBtn.classList.remove('in-wishlist');
        heartBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
        `;
    }
}

// Set loading state for heart button
function setHeartLoading(heartBtn, isLoading) {
    if (!heartBtn) return;
    
    if (isLoading) {
        heartBtn.disabled = true;
        heartBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 6v6l4 2"></path>
            </svg>
        `;
    } else {
        heartBtn.disabled = false;
    }
}

// Show success/error messages
function showMessage(message, type = 'info') {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.wishlist-message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.className = `wishlist-message wishlist-message-${type}`;
    messageDiv.innerHTML = `
        <div class="message-content">
            <span class="message-text">${message}</span>
            <button class="message-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(messageDiv);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (messageDiv.parentElement) {
            messageDiv.remove();
        }
    }, 3000);
}

// Initialize wishlist status for all visible cards
async function initializeWishlistStatus() {
    const programCards = document.querySelectorAll('.degree-program-card');
    console.log('🔍 Initializing wishlist status for', programCards.length, 'cards');
    
    for (const card of programCards) {
        const programId = card.getAttribute('data-program-id');
        if (programId) {
            try {
                console.log('Checking wishlist status for program:', programId);
                const isInWishlist = await checkWishlistStatus(programId);
                console.log('Program', programId, 'is in wishlist:', isInWishlist);
                updateHeartIcon(programId, isInWishlist);
            } catch (error) {
                console.error(`Error initializing wishlist status for program ${programId}:`, error);
            }
        }
    }
}

// Update wishlist count in the mode toggle button
async function updateWishlistCount() {
    try {
        const response = await fetch('/UniHelper/api/wishlist/count');
        const result = await response.json();
        
        if (result.success) {
            const wishlistBtn = document.querySelector('[data-mode="wishlist"]');
            if (wishlistBtn) {
                wishlistBtn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    My Wishlist (${result.data.count})
                `;
            }
        }
    } catch (error) {
        console.error('Error updating wishlist count:', error);
    }
}

// Add a single program to the wishlist page in real-time
async function addProgramToWishlistPage(programId) {
    try {
        // Get the program details from the search results
        const searchCard = document.querySelector(`[data-program-id="${programId}"]`);
        if (!searchCard) {
            console.log('Search card not found for program:', programId);
            return;
        }
        
        // Extract program details from the search card
        const programName = searchCard.querySelector('.card-header h3')?.textContent || 'Unknown Program';
        const universityName = searchCard.querySelector('.card-header p')?.textContent || 'Unknown University';
        const majorName = searchCard.querySelector('.faculty-name')?.textContent || 'Unknown Major';
        const stream = searchCard.querySelector('.cutoff-info strong')?.textContent || 'Unknown Stream';
        const unicode = searchCard.querySelector('.unicode-info strong')?.textContent || '';
        
        // Create the new card HTML
        const newCard = document.createElement('div');
        newCard.className = 'degree-program-card';
        newCard.setAttribute('data-program-id', programId);
        newCard.style.opacity = '0';
        newCard.style.transform = 'translateY(-20px)';
        newCard.innerHTML = `
            <div class="card-header">
                <h3>${programName}</h3>
                <p>${universityName}</p>
            </div>

            <div class="card-body">
                <p class="faculty-name">${majorName}</p>

                <div class="degree-metrics">
                    <div class="cutoff-info">Stream: <strong>${stream}</strong></div>
                    <div class="unicode-info">Unicode: <strong>${unicode}</strong></div>
                </div>

                <div class="degree-tags">
                    <span class="tag">${stream} Stream</span>
                </div>
            </div>

            <div class="card-footer">
                <div class="footer-details">
                    <span>Major: <strong>${majorName}</strong></span>
                    <span>Unicode: <strong>${unicode}</strong></span>
                </div>
                <div class="card-actions">
                    <button class="icon-btn wishlist-btn in-wishlist" onclick="toggleWishlist(${programId})" aria-label="Remove from Wishlist">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                    </button>
                    <button class="icon-btn" aria-label="View Details">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>
        `;
        
        const wishlistResults = document.getElementById('wishlistResults');
        
        // Check if wishlist is currently empty
        const isEmpty = wishlistResults.querySelector('.no-search-message');
        if (isEmpty) {
            wishlistResults.innerHTML = '';
        }
        
        // Add the new card
        wishlistResults.appendChild(newCard);
        
        // Animate the card in
        setTimeout(() => {
            newCard.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            newCard.style.opacity = '1';
            newCard.style.transform = 'translateY(0)';
        }, 10);
        
    } catch (error) {
        console.error('Error adding program to wishlist page:', error);
    }
}

// Refresh wishlist page with latest data
async function refreshWishlistPage() {
    try {
        console.log('🔄 Refreshing wishlist page...');
        const response = await fetch('/UniHelper/api/wishlist/items');
        const result = await response.json();
        console.log('📦 Wishlist API response:', result);
        
        if (result.success) {
            const wishlistResults = document.getElementById('wishlistResults');
            
            if (result.data.length === 0) {
                wishlistResults.innerHTML = `
                    <div class="no-search-message">
                        <div class="no-search-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </div>
                        <h3>Your wishlist is empty</h3>
                        <p>Browse degree programs and click the heart to add them to your wishlist.</p>
                    </div>
                `;
            } else {
                const html = result.data.map(program => `
                    <div class="degree-program-card" data-program-id="${program.program_id}">
                        <div class="card-header">
                            <h3>${program.program_name}</h3>
                            <p>${program.university_name}</p>
                        </div>

                        <div class="card-body">
                            <p class="faculty-name">${program.major_name}</p>

                            <div class="degree-metrics">
                                <div class="cutoff-info">Stream: <strong>${program.stream}</strong></div>
                                <div class="unicode-info">Unicode: <strong>${program.unicode || ''}</strong></div>
                            </div>

                            <div class="degree-tags">
                                <span class="tag">${program.stream} Stream</span>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="footer-details">
                                <span>Major: <strong>${program.major_name}</strong></span>
                                <span>Unicode: <strong>${program.unicode || ''}</strong></span>
                            </div>
                            <div class="card-actions">
                                <button class="icon-btn wishlist-btn in-wishlist" onclick="toggleWishlist(${program.program_id})" aria-label="Remove from Wishlist">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                </button>
                                <button class="icon-btn" aria-label="View Details">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                wishlistResults.innerHTML = html;
                console.log('Wishlist refreshed with', result.data.length, 'items');
            }
        }
    } catch (error) {
        console.error('Error refreshing wishlist page:', error);
    }
}
