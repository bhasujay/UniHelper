<?php
use app\models\WishlistModel;

// Load wishlist items for wishlist mode
$wishlistItems = [];
if (isset($_SESSION['user_id'])) {
    $wishlistModel = new WishlistModel();
    $wishlistItems = $wishlistModel->getUserWishlist($_SESSION['user_id']);
}
?>

<!-- Degree Programs Page with Search and Wishlist -->
<div class="degree-programs-page">
    <!-- Mode Toggle -->
    <div class="mode-toggle">
        <button class="mode-btn active" data-mode="search">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            Search Programs
        </button>
        <button class="mode-btn" data-mode="wishlist">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            My Wishlist (<?= count($wishlistItems) ?>)
        </button>
    </div>

    <!-- Search Bar Section - Only visible in search mode -->
    <div class="page-search-section" id="searchSection">
        <div class="search-header">
            <h2 style="color: white;">Search Degree Programs</h2>
            <p style="color: white;">Find the perfect degree program for your future</p>
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
    
    <!-- Results Section - Shows either search results or wishlist -->
    <div class="search-results" id="searchResults">
        <!-- Default search message -->
        <div class="no-search-message" id="defaultMessage">
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

    <!-- Wishlist Results (hidden by default) -->
    <div class="wishlist-results" id="wishlistResults" style="display: none;">
        <?php if (empty($wishlistItems)): ?>
            <div class="no-search-message">
                <div class="no-search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </div>
                <h3>Your wishlist is empty</h3>
                <p>Browse degree programs and click the heart to add them to your wishlist.</p>
            </div>
        <?php else: ?>
            <?php foreach ($wishlistItems as $program): ?>
                <div class="degree-program-card" data-program-id="<?= htmlspecialchars($program['program_id']) ?>">
                    <div class="card-header">
                        <h3><?= htmlspecialchars($program['program_name']) ?></h3>
                        <p><?= htmlspecialchars($program['university_name']) ?></p>
                    </div>

                    <div class="card-body">
                        <p class="faculty-name"><?= htmlspecialchars($program['major_name']) ?></p>

                        <div class="degree-metrics">
                            <div class="cutoff-info">Stream: <strong><?= htmlspecialchars($program['stream']) ?></strong></div>
                            <div class="unicode-info">Unicode: <strong><?= htmlspecialchars($program['unicode'] ?? '') ?></strong></div>
                        </div>

                        <div class="degree-tags">
                            <span class="tag"><?= htmlspecialchars($program['stream']) ?> Stream</span>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="footer-details">
                            <span>Major: <strong><?= htmlspecialchars($program['major_name']) ?></strong></span>
                            <span>Unicode: <strong><?= htmlspecialchars($program['unicode'] ?? '') ?></strong></span>
                        </div>
                        <div class="card-actions">
                            <button class="icon-btn wishlist-btn in-wishlist" onclick="toggleWishlist(<?= (int)$program['program_id'] ?>)" aria-label="Remove from Wishlist">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                            </button>
                            <button class="icon-btn" aria-label="View Details">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="views/js/degree-programs.js"></script>