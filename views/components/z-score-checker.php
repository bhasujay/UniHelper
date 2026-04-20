<div class="dashboard-card z-score-card initial-state">

    <!-- =============================================
         State 1: Initial (page load / no Z-Score)
         ============================================= -->
    <div class="initial-content">
        <!-- Animated icon -->
        <div class="zscore-icon-wrapper">
            <div class="zscore-icon-bg">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
        </div>

        <!-- Text -->
        <div style="text-align:center;">
            <h2 class="zscore-headline">Find Your Dream Course</h2>
            <p class="zscore-subtext">Enter your A/L Z-Score to discover all<br>degree programs you're eligible for.</p>
        </div>

        <!-- Primary CTA + quick links -->
        <div class="zscore-cta-group">
            <button type="button" id="startZScoreBtn" class="btn-zscore-start">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 8 16 12 12 16"/>
                    <line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
                Get Started
            </button>

            <!-- <div class="zscore-quick-links">
                <a href="#" class="btn-quick-link" onclick="event.preventDefault();">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.19-9.51L1 10"/>
                    </svg>
                    Recently Viewed
                </a>
                <a href="#" class="btn-quick-link" onclick="event.preventDefault();">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    Top Programs
                </a>
            </div> -->
        </div>
    </div>

    <!-- =============================================
         State 2: Submitted (Z-Score saved)
         ============================================= -->
    <div class="submitted-content">

        <!-- Score hero -->
        <div class="zscore-result-hero">
            <div class="zscore-badge-circle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div class="zscore-result-meta">
                <div class="zscore-result-label">Your Z-Score</div>
                <div class="zscore-result-value z-score-value">—</div>
            </div>
        </div>

        <!-- Detail chips -->
        <div class="zscore-details-grid">
            <div class="zscore-detail-chip">
                <div class="chip-label">Stream</div>
                <div class="chip-value stream-value">—</div>
            </div>
            <div class="zscore-detail-chip">
                <div class="chip-label">District</div>
                <div class="chip-value district-value">—</div>
            </div>
            <div class="zscore-detail-chip full-width">
                <div class="chip-label">A/L Subjects</div>
                <div class="zscore-subjects-row">
                    <span class="zscore-subject-tag subject1-value"></span>
                    <span class="zscore-subject-tag subject2-value"></span>
                    <span class="zscore-subject-tag subject3-value"></span>
                </div>
            </div>
        </div>

        <!-- Action buttons -->
        <div class="zscore-action-row">
            <button type="button" id="findDegreesBtn" class="btn-find-degrees">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Find Eligible Degrees
            </button>

            <button type="button" id="changeDetailsBtn" class="btn-change-details">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Edit
            </button>

            <button type="button" id="removeZScoreBtn" class="btn-remove-score">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14H6L5 6"/>
                    <path d="M10 11v6"/><path d="M14 11v6"/>
                    <path d="M9 6V4h6v2"/>
                </svg>
                Remove
            </button>
        </div>
    </div>
</div>


<!-- =============================================
     Inline Z-Score Form (scrolls with dashboard)
     ============================================= -->
<div id="zScoreModal" style="display: none;">
    <div class="zscore-form-card">

        <!-- Header -->
        <div class="zscore-form-header">
            <div class="zscore-form-header-left">
                <div class="zscore-form-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
                <div>
                    <h2>Enter Your Z-Score Details</h2>
                    <p class="zscore-form-header-subtitle">Fill in your A/L stream, district, subjects &amp; score</p>
                </div>
            </div>
            <button type="button" class="zscore-form-close" id="closeModal" aria-label="Close form">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <!-- Form -->
        <form id="zScoreForm">

            <!-- Step 1: Academic Profile -->
            <div class="form-step-header">
                <div class="form-step-dot">1</div>
                <span class="form-step-title">Academic Profile</span>
            </div>

            <div class="form-group">
                <label for="district" class="form-label">District</label>
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
                <label for="stream" class="form-label">A/L Stream</label>
                <select id="stream" name="stream" class="form-select" required>
                    <option value="">Select Stream</option>
                    <option value="physical-science">Physical Science</option>
                    <option value="biological-science">Biological Science</option>
                    <option value="commerce">Commerce</option>
                    <option value="arts">Arts</option>
                    <option value="technology">Technology</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <!-- Step 2: Subjects -->
            <div class="form-step-header">
                <div class="form-step-dot">2</div>
                <span class="form-step-title">A/L Subjects</span>
            </div>

            <div class="form-group full-width">
                <div class="subjects-container">
                    <input type="text" id="subject1" name="subject1" class="form-input"
                           placeholder="Subject 1" required>
                    <input type="text" id="subject2" name="subject2" class="form-input"
                           placeholder="Subject 2" required>
                    <input type="text" id="subject3" name="subject3" class="form-input"
                              placeholder="Subject 3 (Chemistry or ICT)" required>
                    <select id="subject3Option" class="form-select" style="display: none;">
                        <option value="Chemistry">Chemistry</option>
                        <option value="Information &amp; Communication Technology">Information Communication Technology</option>
                    </select>
                </div>
                <small class="form-instructions">Subjects auto-fill when you select a known stream above.</small>
            </div>

            <!-- Step 3: Z-Score -->
            <div class="form-step-header">
                <div class="form-step-dot">3</div>
                <span class="form-step-title">Your Z-Score</span>
            </div>

            <div class="form-group full-width">
                <label for="zScore" class="form-label">Z-Score</label>
                <div class="zscore-field-group">
                    <input type="number" id="zScore" name="zScore" class="form-input"
                           step="0.0001" min="-1" max="4.0"
                           placeholder="e.g. 1.8234" required>
                    <span class="zscore-range-hint">Range: -1.0000 – 4.0000</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <button type="button" id="cancelBtn" class="btn-outline">Cancel</button>
                <button type="submit" class="btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    Save Z-Score
                </button>
            </div>
        </form>
    </div>
</div>


<!-- =============================================
     Eligible Programs Results Section
     ============================================= -->
<div id="eligibleProgramsSection" style="display: none; margin-top: 2rem;">

    <div class="eligible-section-header">
        <div class="eligible-section-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            Eligible Programs for You
        </div>
        <span id="programsCount" class="eligible-section-count">Loading…</span>
    </div>

    <div id="eligibleFilters" class="eligible-filters" hidden>
        <div class="eligible-filter-field eligible-filter-field-search">
            <label for="eligibleFilterSearch">Search</label>
            <input type="text" id="eligibleFilterSearch" class="eligible-filter-input" placeholder="Program or university">
        </div>

        <div class="eligible-filter-field">
            <label for="eligibleFilterUniversity">University</label>
            <select id="eligibleFilterUniversity" class="eligible-filter-select">
                <option value="">All Universities</option>
            </select>
        </div>

        <div class="eligible-filter-field">
            <label for="eligibleFilterEligibility">Eligibility</label>
            <select id="eligibleFilterEligibility" class="eligible-filter-select">
                <option value="">All Chances</option>
                <option value="very_likely">Very Likely</option>
                <option value="likely">Likely</option>
                <option value="possible">Possible</option>
                <option value="unlikely">Low Chance</option>
                <option value="noc">Open Entry</option>
            </select>
        </div>

        <div class="eligible-filter-field">
            <label for="eligibleFilterMinCutoff">Minimum Cutoff</label>
            <input type="number" id="eligibleFilterMinCutoff" class="eligible-filter-input" step="0.0001" placeholder="e.g. 1.2000">
        </div>

        <div class="eligible-filter-field">
            <label for="eligibleFilterMaxCutoff">Maximum Cutoff</label>
            <input type="number" id="eligibleFilterMaxCutoff" class="eligible-filter-input" step="0.0001" placeholder="e.g. 2.0000">
        </div>

        <div class="eligible-filter-actions">
            <button type="button" id="eligibleFilterReset" class="eligible-filter-reset">Reset Filters</button>
        </div>
    </div>

    <div id="programsList" class="search-results">
        <!-- Degree program cards inserted by JavaScript -->
    </div>

    <div id="noResultsMessage" class="no-results" style="display: none;">
        <div class="no-results-icon">🔍</div>
        <h3 id="noResultsTitle">No Eligible Programs Found</h3>
        <p id="noResultsText">No programs match your Z-Score and criteria. Try adjusting your details.</p>
    </div>
</div>

<script src="views/js/z-score-modal.js"></script>