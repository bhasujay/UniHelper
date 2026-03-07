<div class="dashboard-card z-score-card initial-state">
    <!-- State 1: Initial (when page loads) -->
    <div class="initial-content">
        <div class="card-main-action">
            <h2>Ready to find your course?</h2>
            <button type="button" id="startZScoreBtn" class="btn btn-primary btn-large">Start Here</button>
        </div>

        <div class="card-secondary-actions">
            <a href="#" class="btn btn-outline" onclick="event.preventDefault();">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 4v6h6"></path>
                    <path d="M3.51 15a9 9 0 1 0 2.19-9.51L1 10"></path>
                </svg>
                <span>Recently Viewed</span>
            </a>
            <a href="#" class="btn btn-outline" onclick="event.preventDefault();">
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

<!-- Z-Score Modal Form -->
<div id="zScoreModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Enter Your Z-Score Details</h2>
            <span class="close" id="closeModal">&times;</span>
        </div>
        
        <form id="zScoreForm" class="modal-form">
            <div class="form-group">
                <label for="district" class="form-label required">District</label>
                <select id="district" name="district" class="form-select" required>
                    <option value="">Select District</option>
                    <option value="Colombo">Colombo</option>
                    <option value="Gampaha">Gampaha</option>
                    <option value="Kalutara">Kalutara</option>
                    <option value="Kandy">Kandy</option>
                    <option value="Matale">Matale</option>
                    <option value="Nuwara Eliya">Nuwara Eliya</option>
                    <option value="Galle">Galle</option>
                    <option value="Matara">Matara</option>
                    <option value="Hambantota">Hambantota</option>
                    <option value="Jaffna">Jaffna</option>
                    <option value="Kilinochchi">Kilinochchi</option>
                    <option value="Mannar">Mannar</option>
                    <option value="Vavuniya">Vavuniya</option>
                    <option value="Mullaitivu">Mullaitivu</option>
                    <option value="Batticaloa">Batticaloa</option>
                    <option value="Ampara">Ampara</option>
                    <option value="Trincomalee">Trincomalee</option>
                    <option value="Kurunegala">Kurunegala</option>
                    <option value="Puttalam">Puttalam</option>
                    <option value="Anuradhapura">Anuradhapura</option>
                    <option value="Polonnaruwa">Polonnaruwa</option>
                    <option value="Badulla">Badulla</option>
                    <option value="Moneragala">Moneragala</option>
                    <option value="Ratnapura">Ratnapura</option>
                    <option value="Kegalle">Kegalle</option>
                </select>
            </div>

            <div class="form-group">
                <label for="stream" class="form-label required">Stream</label>
                <select id="stream" name="stream" class="form-select" required>
                    <option value="">Select Stream</option>
                    <option value="physical-science">Physical Science</option>
                    <option value="biological-science">Biological Science</option>
                    <option value="commerce">Commerce</option>
                    <option value="arts">Arts</option>
                    <option value="technology">Technology</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label required">A/L Subjects</label>
                <div class="subjects-container">
                    <div>
                        <input type="text" id="subject1" name="subject1" class="form-input" placeholder="Subject 1" required>
                    </div>
                    <div>
                        <input type="text" id="subject2" name="subject2" class="form-input" placeholder="Subject 2" required>
                    </div>
                    <div>
                        <input type="text" id="subject3" name="subject3" class="form-input" placeholder="Subject 3" required>
                    </div>
                </div>
                <small class="form-instructions">Subjects will auto-fill based on stream selection</small>
            </div>

            <div class="form-group">
                <label for="zScore" class="form-label required">Z-Score</label>
                <input type="number" id="zScore" name="zScore" class="form-input" step="0.0001" min="0" max="3" placeholder="e.g., 1.8234" required>
                <small class="form-instructions">Enter your Z-Score (0.0000 to 3.0000)</small>
            </div>

            <div class="form-actions">
                <button type="button" id="cancelBtn" class="btn btn-outline btn-full">Cancel</button>
                <button type="submit" class="btn btn-primary btn-full">Save Z-Score</button>
            </div>
        </form>
    </div>
</div>

<!-- Eligible Programs Results Section -->
<div id="eligibleProgramsSection" style="display: none; margin-top: 30px;">
    <div style="background: #1a1a1a; border: 1px solid #444; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="color: white; margin: 0;">🎓 Eligible Degree Programs</h2>
            <button onclick="closeEligibleProgramsModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <p style="color: #ccc; margin: 10px 0 0 0; font-size: 14px;">
            <strong>Your Z-Score:</strong> <span id="resultZScore">-</span> | 
            <strong>Stream:</strong> <span id="resultStream">-</span> | 
            <strong>District:</strong> <span id="resultDistrict">-</span> | 
            <strong style="color: #007bff;">Total Eligible:</strong> <span id="resultTotal" style="color: #007bff; font-weight: bold;">0</span> programs
        </p>
    </div>
    
    <div id="programsList" class="search-results">
        <!-- Programs will be inserted here by JavaScript as degree-program-card -->
    </div>
    
    <div id="noResultsMessage" class="no-results" style="display: none;">
        <div class="no-results-icon">🔍</div>
        <h3>No Eligible Programs Found</h3>
        <p>No programs match your Z-Score and criteria.</p>
    </div>
</div>

<script src="views/js/z-score-modal.js"></script>