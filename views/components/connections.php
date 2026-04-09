<link rel="stylesheet" href="/unihelper/views/css/components/connections.css">
<script src="/unihelper/views/js/connection.js"></script>

<!-- the main card we are gonna use for a connection card -->
<div class="connection-card template" style="display: none;">
    <!-- only the name, profile picture and the role is getting fetched form the backend -->
    <div class="connection-card-header">
        <img src="" alt="">
        <div class="connection-card-header-info">
            <h3 class="connection-card-header-info-name"></h3>
            <p class="connection-card-header-info-role"></p>
        </div>
    </div>
    <!-- then there should be a generic button for ['Add friend', 'Accept', 'Reject', 'View'] -->
    <div class="connection-card-button">
        <button></button>
    </div>
</div>

<!-- main component view -->
<div class="connections-view">
    <div class="connections-view-header">
        <div class="connections-searchbar">
            <input type="text" id="connections-search-input" placeholder="Search connections..." class="search-input">
            <button type="button" class="search-clear-btn" aria-label="Clear search" style="display: none;">×</button>
            <button type="button" class="search-trigger-btn" aria-label="Search">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </button>
        </div>
        <div class="connections-view-header-tabs">
            <button id="suggestions-tab" class="connections-view-header-tabs-tab active">Suggestions</button>
            <button id="friends-tab" class="connections-view-header-tabs-tab">Your Friends</button>
            <button id="requests-tab" class="connections-view-header-tabs-tab">Requests</button>
        </div>
    </div>
    <div class="connections-view-content">
        <!-- Three divs for the tabs and one for search results-->
        <div id="search-results-tab-content" class="connections-view-content">
            <!-- this is for search results tab -->
            <div class="connections-view-cards-space">
                <!-- all the connection cards are gonna be here -->
            </div>
            <!-- searching buffer -->
            <!-- search result summary -->
            <!-- no result found -->
        </div>
        <div id="suggestions-tab-content" class="connections-view-content">
            <!-- this is for suggestions tab -->
            <div class="connections-view-content-tags">
                <button class="connections-view-content-tags-tag active">Mutual suggestions</button>
                <button class="connections-view-content-tags-tag">Major based suggestions</button>
                <button class="connections-view-content-tags-tag">University based suggestions</button>
            </div>
            <div class="connections-view-cards-space">
                <!-- all the connection cards are gonna be here -->
            </div>
        </div>
        <div id="friends-tab-content" class="connections-view-content">
            <!-- this is for friends tab -->
            <div class="connections-view-cards-space">
                <!-- all the connection cards are gonna be here -->
            </div>
        </div>
        <div id="requests-tab-content" class="connections-view-content">
            <!-- this is for requests tab -->
            <div class="connections-view-content-tags">
                <button class="connections-view-content-tags-tag active">Incoming requests</button>
                <button class="connections-view-content-tags-tag">Outgoing requests</button>
            </div>
            <div class="connections-view-cards-space">
                <!-- all the connection cards are gonna be here -->
            </div>
        </div>
    </div>
</div>