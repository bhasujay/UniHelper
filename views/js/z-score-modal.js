// z-score-modal.js - Z-Score Modal functionality

document.addEventListener('DOMContentLoaded', function () {
    // Load existing Z-Score data when page loads
    loadZScoreFromAPI();

    const startBtn = document.getElementById('startZScoreBtn');
    const modal = document.getElementById('zScoreModal');
    const closeBtn = document.getElementById('closeModal'); // now a <button>
    const cancelBtn = document.getElementById('cancelBtn');
    const form = document.getElementById('zScoreForm');
    const streamSelect = document.getElementById('stream');
    const streamSubjects = {
        'physical-science': {
            subject1: 'Combined Mathematics',
            subject2: 'Physics',
            subject3: 'Chemistry'
        },
        'biological-science': {
            subject1: 'Biology',
            subject2: 'Physics',
            subject3: 'Chemistry'
        }
    }

    console.log('🔧 Elements found:', {
        startBtn: !!startBtn,
        modal: !!modal,
        closeBtn: !!closeBtn,
        cancelBtn: !!cancelBtn,
        form: !!form
    });

    // Show modal when Start Here is clicked
    if (startBtn) {
        console.log('✅ Adding click listener to start button');

        startBtn.addEventListener('click', function (e) {
            console.log('🎯 Start button clicked!');
            modal.style.display = 'block';
            modal.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // Debug: Check if button is still being covered
        const rect = startBtn.getBoundingClientRect();
        const elementAtPoint = document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2);
        console.log('🔧 After CSS fix - Element at button center:', elementAtPoint);
        console.log('🔧 Is it the button itself now?', elementAtPoint === startBtn);

    } else {
        console.error('❌ Start button not found!');
    }

    // Hide modal when close button is clicked
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            modal.style.display = 'none';
        });
    }

    // Hide modal when cancel button is clicked
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            modal.style.display = 'none';
        });
    }

    if (streamSelect) {
        const subject1Input = document.getElementById('subject1');
        const subject2Input = document.getElementById('subject2');
        const subject3Input = document.getElementById('subject3');
        const subject3OptionSelect = document.getElementById('subject3Option');

        if (subject3OptionSelect) {
            subject3OptionSelect.addEventListener('change', function () {
                subject3Input.value = this.value;
            });
        }

        streamSelect.addEventListener('change', function () {
            const selectedStream = this.value;
            const subjects = streamSubjects[selectedStream];

            if (selectedStream === 'physical-science') {
                subject1Input.value = streamSubjects['physical-science'].subject1;
                subject2Input.value = streamSubjects['physical-science'].subject2;

                const currentSubject3 = String(subject3Input.value || '').trim().toLowerCase();
                const isIct = [
                    'ict',
                    'information communication technology',
                    'information & communication technology',
                    'information and communication technology',
                    'informantion communication technology'
                ].includes(currentSubject3);

                if (currentSubject3 === '' || (!isIct && currentSubject3 !== 'chemistry')) {
                    subject3Input.value = 'Chemistry';
                } else if (isIct) {
                    subject3Input.value = 'Information & Communication Technology';
                } else {
                    subject3Input.value = 'Chemistry';
                }

                // For Physical Science, subject 3 can be Chemistry or ICT.
                subject1Input.readOnly = true;
                subject2Input.readOnly = true;
                subject3Input.readOnly = true;
                if (subject3OptionSelect) {
                    subject3OptionSelect.value = subject3Input.value;
                    subject3OptionSelect.style.display = '';
                }
                subject3Input.style.display = 'none';
                subject3Input.placeholder = 'Subject 3 (Chemistry or ICT)';
            } else if (subjects) {
                subject1Input.value = subjects.subject1;
                subject2Input.value = subjects.subject2;
                subject3Input.value = subjects.subject3;

                // Make subject fields read-only for fixed stream subject sets.
                subject1Input.readOnly = true;
                subject2Input.readOnly = true;
                subject3Input.readOnly = true;
                if (subject3OptionSelect) {
                    subject3OptionSelect.style.display = 'none';
                }
                subject3Input.style.display = '';
                subject3Input.placeholder = 'Subject 3';
            } else {
                subject1Input.value = '';
                subject2Input.value = '';
                subject3Input.value = '';

                // Make subject fields editable for "Other" streams
                subject1Input.readOnly = false;
                subject2Input.readOnly = false;
                subject3Input.readOnly = false;
                if (subject3OptionSelect) {
                    subject3OptionSelect.style.display = 'none';
                }
                subject3Input.style.display = '';
                subject3Input.placeholder = 'Subject 3';
            }
        });
    }
    // Handle form submission
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(form);
            const district = formData.get('district');
            const stream = formData.get('stream');
            const subject1 = formData.get('subject1');
            const subject2 = formData.get('subject2');
            const subject3 = formData.get('subject3');
            const zScore = formData.get('zScore');

            // Basic validation
            if (!district || !stream || !subject1 || !subject2 || !subject3 || !zScore) {
                showToast('Please fill in all fields', 'error');
                return;
            }

            if (parseFloat(zScore) < 0 || parseFloat(zScore) > 3.0) {
                showToast('Z-Score must be between 0 and 3.0', 'error');
                return;
            }

            // Validate Z-Score decimal places (max 4 decimal places)
            const decimalPlaces = (zScore.toString().split('.')[1] || '').length;
            if (decimalPlaces > 4) {
                showToast('Z-Score can have maximum 4 decimal places', 'error');
                return;
            }

            // Debug: Log form data
            console.log('🔧 Form data being sent:');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}: ${value}`);
            }

            console.log('🎯🎯🎯 ABOUT TO CALL API FUNCTIONS 🎯🎯🎯');

            // Send data to backend API using ZScoreController
            console.log('🔧 Form submitted, saving Z-Score data');
            saveZScoreToAPI(formData);
        });
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            modal.style.display = 'none';
        }
    });
});

// Store the submitted data globally so buttons can access it
let submittedZScoreData = null;
let listenersInitialized = false; // Flag to prevent multiple listener additions
let eligibleProgramsSource = [];
let eligibleProgramsFiltered = [];
let eligibleFilterListenersBound = false;

// Function to update Z-Score card after submission
function updateZScoreCard(formData) {
    const card = document.querySelector('.z-score-card');

    // Store the form data globally
    submittedZScoreData = {
        zScore: formData.get('zScore'),
        stream: formData.get('stream'),
        district: formData.get('district'),
        subject1: formData.get('subject1'),
        subject2: formData.get('subject2'),
        subject3: formData.get('subject3')
    };

    // Switch from initial-state to submitted-state
    card.classList.remove('initial-state');
    card.classList.add('submitted-state');

    // Clear cache since the user updated their details
    sessionStorage.removeItem('has_calculated_eligibility');

    // Update the values in submitted content
    document.querySelector('.z-score-value').textContent = submittedZScoreData.zScore;
    document.querySelector('.stream-value').textContent = submittedZScoreData.stream;
    document.querySelector('.district-value').textContent = submittedZScoreData.district;
    // subject tags are <span> elements in the new UI
    document.querySelector('.subject1-value').textContent = submittedZScoreData.subject1;
    document.querySelector('.subject2-value').textContent = submittedZScoreData.subject2;
    document.querySelector('.subject3-value').textContent = submittedZScoreData.subject3;

    // Only add event listeners once
    if (!listenersInitialized) {
        setTimeout(() => {
            addButtonEventListeners();
        }, 100);
    }
}

// Function to reset Z-Score card to initial state
function resetZScoreCard() {
    const card = document.querySelector('.z-score-card');

    // Switch from submitted-state back to initial-state
    card.classList.remove('submitted-state');
    card.classList.add('initial-state');

    // Clear stored data and session cache
    submittedZScoreData = null;
    sessionStorage.removeItem('has_calculated_eligibility');

    // Reset the listeners flag so they can be re-added when needed
    listenersInitialized = false;
}

// Function to add event listeners to the submitted card buttons
function addButtonEventListeners() {
    // Prevent adding listeners multiple times
    if (listenersInitialized) {
        console.log('⚠️ Event listeners already initialized, skipping...');
        return;
    }

    console.log('✅ Initializing button event listeners...');

    // Find Eligible Degrees button
    const findDegreesBtn = document.getElementById('findDegreesBtn');
    if (findDegreesBtn) {
        findDegreesBtn.addEventListener('click', async function () {
            console.log('Find Eligible Degrees clicked');

            // Show loading state
            this.disabled = true;
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-small"></span> Finding programs...';

            try {
                // Call the PHP API to find eligible degrees
                const response = await fetch('/UniHelper/api?controller=ZScoreController&action=findEligibleDegrees');
                const result = await response.json();

                if (result.success && result.data) {
                    const data = result.data; // Now this is an array []
                    
                    // Set flag so we auto-fetch if they reload the page
                    sessionStorage.setItem('has_calculated_eligibility', 'true');

                    // Display results
                    if (data.length > 0) {
                        displayEligiblePrograms(data);
                    } else {
                        displayEligiblePrograms([]);
                        showToast(`No eligible programs found for your Z-Score of ${submittedZScoreData.zScore}. Try exploring other streams or check back later for updates.`, 'error');
                    }
                } else {
                    showToast('Error: ' + (result.message || 'Failed to find eligible programs'), 'error');
                }
            } catch (error) {
                console.error('Error finding eligible degrees:', error);
                showToast('Failed to find eligible programs. Please try again.', 'error');
            } finally {
                // Restore button state
                this.disabled = false;
                this.innerHTML = originalText;
            }
        });
    }

    // Change Details button
    const changeDetailsBtn = document.getElementById('changeDetailsBtn');
    if (changeDetailsBtn) {
        changeDetailsBtn.addEventListener('click', function () {
            console.log('Change Details clicked');
            // Show the modal again to edit details
            const modal = document.getElementById('zScoreModal');
            if (modal) {
                modal.style.display = 'block';
                modal.scrollIntoView({ behavior: 'smooth', block: 'start' });

                // Pre-fill form with existing data
                if (submittedZScoreData) {
                    document.getElementById('district').value = submittedZScoreData.district;
                    const streamField = document.getElementById('stream');
                    streamField.value = submittedZScoreData.stream;
                    document.getElementById('subject1').value = submittedZScoreData.subject1;
                    document.getElementById('subject2').value = submittedZScoreData.subject2;
                    document.getElementById('subject3').value = submittedZScoreData.subject3;
                    document.getElementById('zScore').value = submittedZScoreData.zScore || submittedZScoreData.z_score || '';
                    streamField.dispatchEvent(new Event('change'));
                }
            }
        });
    }

    // Remove Z-Score button
    const removeZScoreBtn = document.getElementById('removeZScoreBtn');
    if (removeZScoreBtn) {
        removeZScoreBtn.addEventListener('click', function () {
            console.log('Remove Z-Score clicked');

            // window.confirm is overridden in dashboard.php to return a Promise.
            // Must use .then() — using it synchronously (if confirm(...)) evaluates
            // the Promise object itself, which is always truthy and fires immediately.
            confirm('Are you sure you want to remove your Z-Score?').then(function (confirmed) {
                if (confirmed) {
                    deleteZScoreFromAPI();
                }
            });
        });
    }

    // Mark listeners as initialized
    listenersInitialized = true;
    console.log('✅ Event listeners initialized successfully');
}

// API Functions
async function saveZScoreToAPI(formData) {
    console.log('🚀🚀🚀 API FUNCTION CALLED - saveZScoreToAPI 🚀🚀🚀');
    console.log('🚀 saveZScoreToAPI called with formData:', formData);
    try {
        // Updated to use ZScoreController
        console.log('📡 Sending POST request to /UniHelper/api?controller=ZScoreController&action=saveZScore');
        const response = await fetch('/UniHelper/api?controller=ZScoreController&action=saveZScore', {
            method: 'POST',
            body: formData
        });

        console.log('📡 Response received:', response);
        const result = await response.json();
        console.log('📡 Response data:', result);

        if (result.success) {
            console.log('✅ Z-Score saved/updated successfully');
            const action = submittedZScoreData ? 'updated' : 'saved';
            showToast(`Z-Score ${action} successfully!`, 'success');
            updateZScoreCard(formData);
            document.getElementById('zScoreModal').style.display = 'none';
            document.getElementById('zScoreForm').reset();
        } else {
            console.log('❌ API returned error:', result.message);
            showToast('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('❌ Error saving Z-Score:', error);
        showToast('Error saving Z-Score. Please try again.', 'error');
    }
}

async function updateZScoreToAPI(formData) {
    try {
        console.log('🚀🚀🚀 API FUNCTION CALLED - updateZScoreToAPI 🚀🚀🚀');
        console.log('🚀 updateZScoreToAPI called with formData:', formData);

        // Updated to use ZScoreController
        const response = await fetch('/UniHelper/api?controller=ZScoreController&action=saveZScore', {
            method: 'POST',
            body: formData
        });

        console.log('📡 Response received:', response);
        const result = await response.json();
        console.log('📡 Response data:', result);

        if (result.success) {
            console.log('✅ Z-Score updated successfully');
            showToast('Z-Score updated successfully!', 'success');
            updateZScoreCard(formData);
            document.getElementById('zScoreModal').style.display = 'none';
            document.getElementById('zScoreForm').reset();
        } else {
            console.log('❌ API returned error:', result.message);
            showToast('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('❌ Error updating Z-Score:', error);
        showToast('Error updating Z-Score. Please try again.', 'error');
    }
}

async function loadZScoreFromAPI() {
    try {
        // Updated to use ZScoreController
        const response = await fetch('/UniHelper/api?controller=ZScoreController&action=getZScore');
        const result = await response.json();

        if (result.success && result.data) {
            // User has existing Z-Score, show submitted state
            const card = document.querySelector('.z-score-card');
            card.classList.remove('initial-state');
            card.classList.add('submitted-state');

            // Update the values
            document.querySelector('.z-score-value').textContent = result.data.z_score;
            document.querySelector('.stream-value').textContent = result.data.stream;
            document.querySelector('.district-value').textContent = result.data.district;
            // subject tags are <span> elements in the new UI
            document.querySelector('.subject1-value').textContent = result.data.subject1;
            document.querySelector('.subject2-value').textContent = result.data.subject2;
            document.querySelector('.subject3-value').textContent = result.data.subject3;

            // Store data globally
            submittedZScoreData = result.data;

            // Add button listeners
            setTimeout(() => {
                addButtonEventListeners();

                // Automatically re-fetch programs if user previously calculated them this session
                if (sessionStorage.getItem('has_calculated_eligibility') === 'true') {
                    const findDegreesBtn = document.getElementById('findDegreesBtn');
                    if (findDegreesBtn) {
                        findDegreesBtn.click(); // Programmatically trigger the search to get fresh data
                    }
                }
            }, 100);
        }
    } catch (error) {
        console.error('Error loading Z-Score:', error);
    }
}


async function deleteZScoreFromAPI() {
    try {
        // Updated to use ZScoreController
        const response = await fetch('/UniHelper/api?controller=ZScoreController&action=deleteZScore', {
            method: 'DELETE'
        });

        const result = await response.json();

        if (result.success) {
            showToast('Z-Score removed successfully!', 'success');
            resetZScoreCard();
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error deleting Z-Score:', error);
        showToast('Error removing Z-Score. Please try again.', 'error');
    }
}

// Function to display eligible programs in a modal or section
function displayEligiblePrograms(programs) {
    eligibleProgramsSource = Array.isArray(programs) ? [...programs].sort(compareEligiblePrograms) : [];

    initializeEligibleFilterControls();
    populateEligibleUniversityFilter(eligibleProgramsSource);

    const filtersContainer = document.getElementById('eligibleFilters');
    if (filtersContainer) {
        filtersContainer.hidden = eligibleProgramsSource.length === 0;
    }

    applyEligibleProgramFilters({ scrollToSection: true });
}

function initializeEligibleFilterControls() {
    if (eligibleFilterListenersBound) {
        return;
    }

    const searchInput = document.getElementById('eligibleFilterSearch');
    const universitySelect = document.getElementById('eligibleFilterUniversity');
    const eligibilitySelect = document.getElementById('eligibleFilterEligibility');
    const minCutoffInput = document.getElementById('eligibleFilterMinCutoff');
    const maxCutoffInput = document.getElementById('eligibleFilterMaxCutoff');
    const resetButton = document.getElementById('eligibleFilterReset');

    if (!searchInput || !universitySelect || !eligibilitySelect || !minCutoffInput || !maxCutoffInput || !resetButton) {
        return;
    }

    const applyFilters = function () {
        applyEligibleProgramFilters();
    };

    searchInput.addEventListener('input', applyFilters);
    universitySelect.addEventListener('change', applyFilters);
    eligibilitySelect.addEventListener('change', applyFilters);
    minCutoffInput.addEventListener('input', applyFilters);
    maxCutoffInput.addEventListener('input', applyFilters);

    resetButton.addEventListener('click', function () {
        searchInput.value = '';
        universitySelect.value = '';
        eligibilitySelect.value = '';
        minCutoffInput.value = '';
        maxCutoffInput.value = '';
        applyEligibleProgramFilters();
    });

    eligibleFilterListenersBound = true;
}

function populateEligibleUniversityFilter(programs) {
    const universitySelect = document.getElementById('eligibleFilterUniversity');
    if (!universitySelect) {
        return;
    }

    const previousValue = universitySelect.value;
    const universities = Array.from(new Set(
        (programs || [])
            .map(function (program) {
                return String(program.university || '').trim();
            })
            .filter(Boolean)
    )).sort(function (a, b) {
        return a.localeCompare(b);
    });

    universitySelect.innerHTML = '';

    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'All Universities';
    universitySelect.appendChild(defaultOption);

    universities.forEach(function (university) {
        const option = document.createElement('option');
        option.value = university;
        option.textContent = university;
        universitySelect.appendChild(option);
    });

    if (previousValue && universities.indexOf(previousValue) !== -1) {
        universitySelect.value = previousValue;
    }
}

function applyEligibleProgramFilters(options) {
    const programsList = document.getElementById('programsList');
    const noResults = document.getElementById('noResultsMessage');
    const noResultsTitle = document.getElementById('noResultsTitle');
    const noResultsText = document.getElementById('noResultsText');
    const section = document.getElementById('eligibleProgramsSection');

    if (!programsList || !noResults || !section) {
        return;
    }

    const searchQuery = String((document.getElementById('eligibleFilterSearch') || {}).value || '')
        .trim()
        .toLowerCase();
    const selectedUniversity = String((document.getElementById('eligibleFilterUniversity') || {}).value || '').trim();
    const selectedEligibility = String((document.getElementById('eligibleFilterEligibility') || {}).value || '').trim().toLowerCase();

    let minCutoff = parseFloat((document.getElementById('eligibleFilterMinCutoff') || {}).value);
    let maxCutoff = parseFloat((document.getElementById('eligibleFilterMaxCutoff') || {}).value);

    minCutoff = Number.isFinite(minCutoff) ? minCutoff : null;
    maxCutoff = Number.isFinite(maxCutoff) ? maxCutoff : null;

    if (minCutoff !== null && maxCutoff !== null && minCutoff > maxCutoff) {
        const temporary = minCutoff;
        minCutoff = maxCutoff;
        maxCutoff = temporary;
    }

    eligibleProgramsFiltered = eligibleProgramsSource.filter(function (program) {
        const programName = String(program.name || '').toLowerCase();
        const university = String(program.university || '').trim();
        const universityLower = university.toLowerCase();
        const eligibility = String(program.eligibility || 'noc').toLowerCase();

        if (searchQuery && !programName.includes(searchQuery) && !universityLower.includes(searchQuery)) {
            return false;
        }

        if (selectedUniversity && university !== selectedUniversity) {
            return false;
        }

        if (selectedEligibility && eligibility !== selectedEligibility) {
            return false;
        }

        const cutoffValue = getProgramFilterCutoff(program);

        if (minCutoff !== null && (cutoffValue === null || cutoffValue < minCutoff)) {
            return false;
        }

        if (maxCutoff !== null && (cutoffValue === null || cutoffValue > maxCutoff)) {
            return false;
        }

        return true;
    });

    renderEligibleProgramCards(eligibleProgramsFiltered, programsList);

    if (eligibleProgramsFiltered.length === 0) {
        programsList.style.display = 'none';
        noResults.style.display = 'block';

        if (eligibleProgramsSource.length === 0) {
            if (noResultsTitle) {
                noResultsTitle.textContent = 'No Eligible Programs Found';
            }
            if (noResultsText) {
                noResultsText.textContent = 'No programs match your Z-Score and criteria. Try adjusting your details.';
            }
        } else {
            if (noResultsTitle) {
                noResultsTitle.textContent = 'No Programs Match Filters';
            }
            if (noResultsText) {
                noResultsText.textContent = 'Try widening the cutoff range or selecting a different university.';
            }
        }
    } else {
        noResults.style.display = 'none';
        programsList.style.display = 'grid';
    }

    updateEligibleProgramCount(eligibleProgramsFiltered.length, eligibleProgramsSource.length);

    section.style.display = 'block';

    if (options && options.scrollToSection) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    if (eligibleProgramsFiltered.length > 0) {
        initializeEligibleWishlistStatus();
    }
}

function renderEligibleProgramCards(programs, programsList) {
    programsList.innerHTML = '';
    programsList.classList.add('zscore-programs-grid');

    const streamLabel = formatStreamName(submittedZScoreData && submittedZScoreData.stream);

    programs.forEach(function (program, index) {
        const eligibility = String(program.eligibility || 'noc').toLowerCase();
        const noCutoffHistory = hasNoCutoffHistory(program);
        const badge = noCutoffHistory
            ? { label: 'No Cutoff History', className: 'is-warning' }
            : getEligibilityBadge(eligibility);
        const probabilityValue = noCutoffHistory
            ? 0
            : getProbabilityValue(program.probability_percent, eligibility);
        const probabilityText = noCutoffHistory
            ? 'N/A'
            : (Number.isFinite(Number(program.probability_percent))
                ? zScoreClamp(Number(program.probability_percent), 0, 100).toFixed(1) + '%'
                : (eligibility === 'noc' ? 'Open Entry' : 'N/A'));

        const predictedCutoff = formatCutoffValue(program.predicted);
        const minCutoff = formatCutoffValue(program.min_cutoff);
        const maxCutoff = formatCutoffValue(program.max_cutoff);
        const warningMessage = String(
            program.warning_message ||
            'This program does not have previous cutoff marks for your district. Eligibility cannot be estimated reliably.'
        );
        const cutoffInsight = noCutoffHistory
            ? warningMessage
            : (eligibility === 'noc'
                ? 'No cutoff required for this intake'
                : (predictedCutoff !== 'N/A'
                    ? 'Predicted cutoff for your district: ' + predictedCutoff
                    : 'Lowest known cutoff for your district: ' + minCutoff));
        const cutoffInsightClass = noCutoffHistory
            ? 'zscore-cutoff-insight zscore-cutoff-warning'
            : 'zscore-cutoff-insight';

        const card = document.createElement('div');
        card.className = 'degree-program-card';
        card.setAttribute('data-program-id', String(program.program_id));

        const wishlistAction = `<button class="zscore-program-action wishlist-btn" onclick="handleEligibleWishlistAction(event, ${Number(program.program_id)})" aria-label="Add to wishlist">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
               </button>`;

        card.innerHTML = `
            <div class="zscore-program-headline-row">
                <span class="zscore-rank-pill">#${index + 1}</span>
                <span class="zscore-eligibility-badge ${badge.className}">${badge.label}</span>
            </div>

            <div class="card-header">
                <h3>${zScoreEscapeHtml(program.name)}</h3>
                <p>${zScoreEscapeHtml(program.university || 'Unknown University')}</p>
            </div>

            <p class="${cutoffInsightClass}">${zScoreEscapeHtml(cutoffInsight)}</p>

            <div class="zscore-probability-wrap">
                <div class="zscore-probability-meta">
                    <span>Admission Probability</span>
                    <strong>${zScoreEscapeHtml(probabilityText)}</strong>
                </div>
                <div class="zscore-probability-track">
                    <div class="zscore-probability-fill ${badge.className}" style="width: ${probabilityValue}%"></div>
                </div>
            </div>

            <div class="zscore-stat-grid">
                <div class="zscore-stat-chip">
                    <span class="zscore-stat-label">Predicted</span>
                    <strong class="zscore-stat-value">${zScoreEscapeHtml(predictedCutoff)}</strong>
                </div>
                <div class="zscore-stat-chip">
                    <span class="zscore-stat-label">Minimum</span>
                    <strong class="zscore-stat-value">${zScoreEscapeHtml(minCutoff)}</strong>
                </div>
                <div class="zscore-stat-chip">
                    <span class="zscore-stat-label">Maximum</span>
                    <strong class="zscore-stat-value">${zScoreEscapeHtml(maxCutoff)}</strong>
                </div>
            </div>

            <div class="degree-tags">
                <span class="tag">${zScoreEscapeHtml(streamLabel)} Stream</span>
            </div>

            <div class="card-footer">
                <div class="footer-details">
                    <span>Program match for your profile</span>
                </div>
                <div class="card-actions">${wishlistAction}</div>
            </div>
        `;

        programsList.appendChild(card);
    });
}

async function handleEligibleWishlistAction(event, programId) {
    if (event) {
        event.preventDefault();
    }

    const numericProgramId = Number(programId);
    if (!Number.isFinite(numericProgramId) || numericProgramId <= 0) {
        return;
    }

    // Reuse the shared toggle behavior when degree-programs.js is loaded.
    if (typeof window.toggleWishlist === 'function') {
        window.toggleWishlist(numericProgramId);
        return;
    }

    const clickedButton = event && event.currentTarget ? event.currentTarget : null;
    const isInWishlist = clickedButton ? clickedButton.classList.contains('in-wishlist') : false;

    const endpoint = isInWishlist
        ? '/UniHelper/api?controller=WishlistController&action=removeFromWishlist'
        : '/UniHelper/api?controller=WishlistController&action=addToWishlist';
    const method = isInWishlist ? 'DELETE' : 'POST';

    if (clickedButton) {
        clickedButton.disabled = true;
    }

    try {
        const response = await fetch(endpoint, {
            method,
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ program_id: numericProgramId })
        });

        const result = await response.json();
        const message = String(result && result.message ? result.message : 'Unable to update wishlist').toLowerCase();
        const alreadySaved = message.includes('already in wishlist');
        const alreadyRemoved = message.includes('not found in wishlist');

        if (!result.success && !(alreadySaved || alreadyRemoved)) {
            console.warn('Failed to update wishlist:', result.message || 'Unknown error');
            return;
        }

        if (clickedButton) {
            const nextInWishlist = !isInWishlist;
            setEligibleWishlistButtonState(clickedButton, nextInWishlist);
        }

        if (typeof window.updateWishlistCount === 'function') {
            window.updateWishlistCount();
        }
    } catch (error) {
        console.error('Error updating wishlist:', error);
    } finally {
        if (clickedButton) {
            clickedButton.disabled = false;
        }
    }
}

function getEligibleWishlistHeartSvg(isFilled) {
    if (isFilled) {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>';
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>';
}

function setEligibleWishlistButtonState(button, isInWishlist) {
    if (!button) {
        return;
    }

    button.classList.toggle('in-wishlist', isInWishlist);
    button.setAttribute('aria-label', isInWishlist ? 'Remove from wishlist' : 'Add to wishlist');
    button.innerHTML = getEligibleWishlistHeartSvg(isInWishlist);
}

async function initializeEligibleWishlistStatus() {
    const programsList = document.getElementById('programsList');
    if (!programsList) {
        return;
    }

    const cards = Array.from(programsList.querySelectorAll('.degree-program-card'));
    if (cards.length === 0) {
        return;
    }

    // If shared wishlist helpers are available, reuse them for consistency.
    if (typeof window.checkWishlistStatus === 'function' && typeof window.updateHeartIcon === 'function') {
        await Promise.all(cards.map(async function (card) {
            const programId = Number(card.getAttribute('data-program-id'));
            if (!Number.isFinite(programId) || programId <= 0) {
                return;
            }

            const isInWishlist = await window.checkWishlistStatus(programId);
            window.updateHeartIcon(programId, isInWishlist);
        }));
        return;
    }

    // Fallback for pages where degree-programs.js is not loaded.
    try {
        const response = await fetch('/UniHelper/api?controller=WishlistController&action=getWishlistItems', {
            credentials: 'same-origin'
        });
        const result = await response.json();
        const wishlistItems = (result && result.success && Array.isArray(result.data)) ? result.data : [];
        const wishlistProgramIds = new Set(wishlistItems.map(function (item) {
            return Number(item.program_id);
        }).filter(Number.isFinite));

        cards.forEach(function (card) {
            const programId = Number(card.getAttribute('data-program-id'));
            const button = card.querySelector('.wishlist-btn');
            setEligibleWishlistButtonState(button, wishlistProgramIds.has(programId));
        });
    } catch (error) {
        console.error('Failed to initialize eligible wishlist state:', error);
    }
}

function getProgramFilterCutoff(program) {
    const predicted = Number(program.predicted);
    if (Number.isFinite(predicted)) {
        return predicted;
    }

    const minimum = Number(program.min_cutoff);
    if (Number.isFinite(minimum)) {
        return minimum;
    }

    const maximum = Number(program.max_cutoff);
    return Number.isFinite(maximum) ? maximum : null;
}

function updateEligibleProgramCount(filteredCount, totalCount) {
    const countBadge = document.getElementById('programsCount');
    if (!countBadge) {
        return;
    }

    if (totalCount === 0) {
        countBadge.textContent = '0 programs found';
        return;
    }

    if (filteredCount === totalCount) {
        countBadge.textContent = totalCount + ' program' + (totalCount !== 1 ? 's' : '') + ' found';
        return;
    }

    countBadge.textContent = filteredCount + ' of ' + totalCount + ' programs';
}

// Function to close eligible programs section
function closeEligibleProgramsModal() {
    const section = document.getElementById('eligibleProgramsSection');
    if (section) {
        section.style.display = 'none';
    }
}

function compareEligiblePrograms(a, b) {
    const probabilityDiff = getSortableProbability(b) - getSortableProbability(a);
    if (probabilityDiff !== 0) {
        return probabilityDiff;
    }

    const minCutoffDiff = Number(b.min_cutoff || 0) - Number(a.min_cutoff || 0);
    if (minCutoffDiff !== 0) {
        return minCutoffDiff;
    }

    return String(a.name || '').localeCompare(String(b.name || ''));
}

function getSortableProbability(program) {
    if (hasNoCutoffHistory(program)) {
        return -1;
    }

    return getProbabilityValue(program.probability_percent, program.eligibility);
}

function hasNoCutoffHistory(program) {
    if (!program || typeof program !== 'object') {
        return false;
    }

    if (typeof program.no_cutoff_history !== 'undefined') {
        return Boolean(program.no_cutoff_history);
    }

    const predicted = Number(program.predicted);
    const minimum = Number(program.min_cutoff);
    const maximum = Number(program.max_cutoff);

    return !Number.isFinite(predicted) && !Number.isFinite(minimum) && !Number.isFinite(maximum);
}

function getEligibilityBadge(eligibilityKey) {
    const badgeMap = {
        very_likely: { label: 'Very Likely', className: 'is-very-likely' },
        likely: { label: 'Likely', className: 'is-likely' },
        possible: { label: 'Possible', className: 'is-possible' },
        unlikely: { label: 'Low Chance', className: 'is-unlikely' },
        noc: { label: 'Open Entry', className: 'is-noc' }
    };

    return badgeMap[eligibilityKey] || badgeMap.noc;
}

function getProbabilityValue(value, eligibilityKey) {
    const numeric = Number(value);
    if (Number.isFinite(numeric)) {
        return zScoreClamp(numeric, 0, 100);
    }

    if (String(eligibilityKey || '').toLowerCase() === 'noc') {
        return 100;
    }

    return 0;
}

function formatCutoffValue(value) {
    const numeric = Number(value);
    return Number.isFinite(numeric) ? numeric.toFixed(4) : 'N/A';
}

function formatStreamName(streamValue) {
    if (!streamValue) {
        return 'General';
    }

    return String(streamValue)
        .replace(/-/g, ' ')
        .replace(/\b\w/g, function (letter) {
            return letter.toUpperCase();
        });
}

function zScoreClamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
}

function zScoreEscapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
