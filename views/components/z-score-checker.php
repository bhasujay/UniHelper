<div class="dashboard-card z-score-card initial-state">
    <!-- State 1: Initial (when page loads) -->
    <div class="initial-content">
        <div class="card-main-action">
            <h2>Ready to find your course?</h2>
            <button type="button" id="startZScoreBtn" class="btn btn-primary btn-large">Start Here</button>
        </div>

        <div class="card-secondary-actions">
            <a href="#" class="btn btn-outline">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 4v6h6"></path>
                    <path d="M3.51 15a9 9 0 1 0 2.19-9.51L1 10"></path>
                </svg>
                <span>Recently Viewed</span>
            </a>
            <a href="#" class="btn btn-outline">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                    <polyline points="17 6 23 6 23 12"></polyline>
                </svg>
                <span>Popular Programs</span>
            </a>
        </div>
    </div>
    
    <!-- State 2: Submitted (after Z-Score submission) -->
    <div class="submitted-content">
        <div class="card-main-action">
            <h2>Your Z-Score: <span class="z-score-value"></span></h2>
            <div class="score-details">
                <p><strong>Stream:</strong> <span class="stream-value"></span></p>
                <p><strong>District:</strong> <span class="district-value"></span></p>
                <div class="subjects-list">
                    <p><strong>Subjects:</strong></p>
                    <ul>
                        <li class="subject1-value"></li>
                        <li class="subject2-value"></li>
                        <li class="subject3-value"></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card-secondary-actions">
            <button type="button" id="findDegreesBtn" class="btn btn-primary btn-large">
                Find Eligible Degrees for You
            </button>
            <button type="button" id="changeDetailsBtn" class="btn btn-outline">
                Change Details
            </button>
            <button type="button" id="removeZScoreBtn" class="btn btn-outline">
                Remove Z-Score
            </button>
        </div>
    </div>
</div>


<script src="views/js/z-score-modal.js"></script>