<div class="dashboard-card unicode-generator-card" id="unicodeGeneratorCard">
    <div class="unicode-generator-header">
        <div class="unicode-header-copy">
            <h2>Unicode Preference Generator</h2>
            <p>
                Build your UGC-style preference order using programs where your eligibility is
                <strong>Likely</strong> or <strong>Very Likely</strong>.
            </p>
        </div>

        <div class="unicode-header-actions">
            <button type="button" id="unicodeRefreshBtn" class="btn btn-outline">Refresh Eligible</button>
            <button type="button" id="unicodeAutoSortBtn" class="btn btn-outline">Auto Sort</button>
            <button type="button" id="unicodeResetSuggestedBtn" class="btn btn-outline">Reset Suggested</button>
            <button type="button" id="unicodeSaveBtn" class="btn btn-primary">Save Order</button>
            <button type="button" id="unicodeClearSavedBtn" class="btn btn-outline">Clear Saved</button>
            <button type="button" id="unicodePdfBtn" class="btn btn-outline">Print / Save PDF</button>
        </div>

        <div class="unicode-toolbar-meta" aria-live="polite">
            <span id="unicodeChangeState" class="unicode-change-state unicode-change-state-synced">All changes saved</span>
            <span id="unicodeLastRefresh" class="unicode-last-refresh">Not loaded yet</span>
        </div>
    </div>

    <div class="unicode-summary-grid">
        <article class="unicode-summary-card">
            <span class="unicode-summary-label">Eligible Programs</span>
            <strong class="unicode-summary-value" id="unicodeEligibleCount">0</strong>
        </article>

        <article class="unicode-summary-card">
            <span class="unicode-summary-label">Selected by List (Simulated)</span>
            <strong class="unicode-summary-value unicode-summary-text" id="unicodeSelectedProgram">-</strong>
        </article>

        <article class="unicode-summary-card">
            <span class="unicode-summary-label">Selected Unicode</span>
            <strong class="unicode-summary-value" id="unicodeSelectedUnicode">-</strong>
        </article>

        <article class="unicode-summary-card">
            <span class="unicode-summary-label">Selection Chance</span>
            <strong class="unicode-summary-value" id="unicodeSelectedChance">-</strong>
        </article>
    </div>

    <section class="unicode-list-container" aria-labelledby="unicodePreferenceHeading">
        <div class="unicode-list-title-row">
            <h3 id="unicodePreferenceHeading">Your Preference Order</h3>
            <p id="unicodeStatusText">
                Drag and drop to reorder. Use <strong>Alt + Arrow Up/Down</strong> for keyboard reorder.
            </p>
        </div>

        <div id="unicodeLoadingState" class="unicode-loading-state" hidden>
            <ul class="unicode-loading-list" aria-hidden="true">
                <li class="unicode-loading-item">
                    <span class="unicode-loading-rank"></span>
                    <div class="unicode-loading-main">
                        <span class="unicode-loading-line unicode-loading-line-lg"></span>
                        <span class="unicode-loading-line unicode-loading-line-md"></span>
                        <span class="unicode-loading-line unicode-loading-line-sm"></span>
                    </div>
                </li>
                <li class="unicode-loading-item">
                    <span class="unicode-loading-rank"></span>
                    <div class="unicode-loading-main">
                        <span class="unicode-loading-line unicode-loading-line-lg"></span>
                        <span class="unicode-loading-line unicode-loading-line-md"></span>
                        <span class="unicode-loading-line unicode-loading-line-sm"></span>
                    </div>
                </li>
                <li class="unicode-loading-item">
                    <span class="unicode-loading-rank"></span>
                    <div class="unicode-loading-main">
                        <span class="unicode-loading-line unicode-loading-line-lg"></span>
                        <span class="unicode-loading-line unicode-loading-line-md"></span>
                        <span class="unicode-loading-line unicode-loading-line-sm"></span>
                    </div>
                </li>
            </ul>
        </div>

        <ul id="unicodePreferenceList" class="unicode-preference-list" aria-live="polite"></ul>

        <div id="unicodeEmptyState" class="unicode-empty-state" hidden>
            <h4>No likely programs available right now</h4>
            <p>
                Update your Z-Score details first, then return here and click <strong>Refresh Eligible</strong>.
            </p>
        </div>
    </section>
</div>

<script src="views/js/unicode-generator.js"></script>
