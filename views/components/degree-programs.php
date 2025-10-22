<!-- Degree Programs Page with Search Bar -->
<div class="degree-programs-page">
    <!-- Search Bar Section - Always visible at top -->
    <div class="page-search-section">
        <div class="search-header">
            <h2>Search Degree Programs</h2>
            <p>Find the perfect degree program for your future</p>
        </div>
        
        <div class="search-bar-container">
            <div class="search-input-wrapper">
                <input type="text" id="programSearchInput" placeholder="Search programs, universities, or majors..." class="search-input">
                <button id="searchBtn" class="search-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Quick Filters -->
        <div class="quick-filters">
            <button class="filter-chip" data-filter="university">Universities</button>
            <button class="filter-chip" data-filter="technology">Technology</button>
            <button class="filter-chip" data-filter="science">Science</button>
            <button class="filter-chip" data-filter="commerce">Commerce</button>
        </div>
        
        <!-- Advanced Search Filters -->
        <div class="advanced-search" id="advancedSearch" style="display: none;">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="universityFilter" class="filter-label">University</label>
                    <select id="universityFilter" class="filter-select">
                        <option value="">All Universities</option>
                        <!-- Populated by JavaScript -->
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="streamFilter" class="filter-label">Stream</label>
                    <select id="streamFilter" class="filter-select">
                        <option value="">All Streams</option>
                        <option value="Technology">Technology</option>
                        <option value="physical Science">Physical Science</option>
                        <option value="biological science">Biological Science</option>
                        <option value="Commerce">Commerce</option>
                        <option value="Arts">Arts</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="majorFilter" class="filter-label">Major</label>
                    <select id="majorFilter" class="filter-select">
                        <option value="">All Majors</option>
                        <!-- Populated by JavaScript -->
                    </select>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="unicodeFilter" class="filter-label">Unicode Code</label>
                    <input type="text" id="unicodeFilter" placeholder="e.g., CS001" class="filter-input">
                </div>
            </div>
        </div>
        
        <div class="search-controls">
            <button id="toggleAdvanced" class="toggle-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
                Advanced Search
            </button>
            <button id="clearFilters" class="clear-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3,6 5,6 21,6"></polyline>
                    <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
                </svg>
                Clear All
            </button>
        </div>
    </div>
    
    <!-- Loading Indicator -->
    <div class="loading" id="loading" style="display: none;">
        <div class="spinner"></div>
        <p>Searching programs...</p>
    </div>
    
    <!-- Search Results -->
    <div class="search-results" id="searchResults">
        <!-- Results will be populated here -->
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
    </div>
</div>