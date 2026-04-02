// z-score-modal.js - Z-Score Modal functionality

document.addEventListener('DOMContentLoaded', function () {
    // Load existing Z-Score data when page loads
    loadZScoreFromAPI();

    const startBtn = document.getElementById('startZScoreBtn');
    const modal = document.getElementById('zScoreModal');
    const closeBtn = document.getElementById('closeModal');
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
        streamSelect.addEventListener('change', function () {
            const selectedStream = this.value;
            const subjects = streamSubjects[selectedStream];

            if (subjects) {
                document.getElementById('subject1').value = subjects.subject1;
                document.getElementById('subject2').value = subjects.subject2;
                document.getElementById('subject3').value = subjects.subject3;

                // Make subject fields read-only
                document.getElementById('subject1').readOnly = true;
                document.getElementById('subject2').readOnly = true;
                document.getElementById('subject3').readOnly = true;
            } else {
                document.getElementById('subject1').value = '';
                document.getElementById('subject2').value = '';
                document.getElementById('subject3').value = '';

                // Make subject fields editable for "Other" streams
                document.getElementById('subject1').readOnly = false;
                document.getElementById('subject2').readOnly = false;
                document.getElementById('subject3').readOnly = false;
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
                alert('Please fill in all fields');
                return;
            }

            if (parseFloat(zScore) < 0 || parseFloat(zScore) > 3.0) {
                alert('Z-Score must be between 0 and 3.0');
                return;
            }

            // Validate Z-Score decimal places (max 4 decimal places)
            const decimalPlaces = (zScore.toString().split('.')[1] || '').length;
            if (decimalPlaces > 4) {
                alert('Z-Score can have maximum 4 decimal places');
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

    // Update the values in submitted content
    document.querySelector('.z-score-value').textContent = submittedZScoreData.zScore;
    document.querySelector('.stream-value').textContent = submittedZScoreData.stream;
    document.querySelector('.district-value').textContent = submittedZScoreData.district;
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

    // Clear stored data
    submittedZScoreData = null;

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
                    const data = result.data;

                    // Display results
                    if (data.total_eligible > 0) {
                        displayEligiblePrograms(data);
                    } else {
                        alert(`No eligible programs found for your Z-Score of ${data.user_zscore}.\n\nTry exploring other streams or check back later for updates.`);
                    }
                } else {
                    alert('Error: ' + (result.message || 'Failed to find eligible programs'));
                }
            } catch (error) {
                console.error('Error finding eligible degrees:', error);
                alert('Failed to find eligible programs. Please try again.');
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
                    document.getElementById('stream').value = submittedZScoreData.stream;
                    document.getElementById('subject1').value = submittedZScoreData.subject1;
                    document.getElementById('subject2').value = submittedZScoreData.subject2;
                    document.getElementById('subject3').value = submittedZScoreData.subject3;
                    document.getElementById('zScore').value = submittedZScoreData.z_score;
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
            alert(`Z-Score ${action} successfully!`);
            updateZScoreCard(formData);
            document.getElementById('zScoreModal').style.display = 'none';
            document.getElementById('zScoreForm').reset();
        } else {
            console.log('❌ API returned error:', result.message);
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('❌ Error saving Z-Score:', error);
        alert('Error saving Z-Score. Please try again.');
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
            alert('Z-Score updated successfully!');
            updateZScoreCard(formData);
            document.getElementById('zScoreModal').style.display = 'none';
            document.getElementById('zScoreForm').reset();
        } else {
            console.log('❌ API returned error:', result.message);
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('❌ Error updating Z-Score:', error);
        alert('Error updating Z-Score. Please try again.');
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
            document.querySelector('.subject1-value').textContent = result.data.subject1;
            document.querySelector('.subject2-value').textContent = result.data.subject2;
            document.querySelector('.subject3-value').textContent = result.data.subject3;

            // Store data globally
            submittedZScoreData = result.data;

            // Add button listeners
            setTimeout(() => {
                addButtonEventListeners();
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
            alert('Z-Score removed successfully!');
            resetZScoreCard();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error deleting Z-Score:', error);
        alert('Error removing Z-Score. Please try again.');
    }
}

// Function to display eligible programs in a modal or section
function displayEligiblePrograms(data) {
    const { user_zscore, user_stream, user_district, eligible_programs, total_eligible } = data;

    // Populate summary section
    document.getElementById('resultZScore').textContent = user_zscore;
    document.getElementById('resultStream').textContent = user_stream;
    document.getElementById('resultDistrict').textContent = user_district || 'Any';
    document.getElementById('resultTotal').textContent = total_eligible;

    // Get programs container
    const programsList = document.getElementById('programsList');
    const noResults = document.getElementById('noResultsMessage');

    // Clear previous results
    programsList.innerHTML = '';

    if (eligible_programs.length === 0) {
        programsList.style.display = 'none';
        noResults.style.display = 'block';
    } else {
        programsList.style.display = 'flex';
        noResults.style.display = 'none';

        // Create program cards using the same structure as degree programs search
        eligible_programs.forEach(program => {
            const card = document.createElement('div');
            card.className = 'degree-program-card';
            card.setAttribute('data-program-id', program.unicode); // Use unicode as ID since we don't have program_id

            card.innerHTML = `
                <div class="card-header">
                    <h3>${program.name}</h3>
                    <p>${program.university}</p>
                </div>
                
                <div class="card-body">
                    <p class="faculty-name">${program.stream.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())}</p>
                    
                    <div class="degree-metrics">
                        <div class="cutoff-info">Cutoff Z-Score: <strong style="color: #007bff;">${program.cutoff_zscore}</strong></div>
                        <div class="unicode-info">Unicode: <strong>${program.unicode}</strong></div>
                    </div>
                    
                    <div class="degree-tags">
                        <span class="tag">${program.stream.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())} Stream</span>
                        ${program.district_specific ? '<span class="tag" style="background: #28a745;">📍 District Specific</span>' : ''}
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="footer-details">
                        <span>Stream: <strong>${program.stream.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())}</strong></span>
                        <span>Unicode: <strong>${program.unicode}</strong></span>
                    </div>
                    <div class="card-actions">
                        <button class="icon-btn wishlist-btn" onclick="addEligibleToWishlist('${program.unicode}', '${program.name.replace(/'/g, "\\'")}', '${program.university.replace(/'/g, "\\'")}')" aria-label="Add to Wishlist">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                        </button>
                        <button class="icon-btn" aria-label="View Details">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
            `;

            programsList.appendChild(card);
        });
    }

    // Show section (not modal)
    const section = document.getElementById('eligibleProgramsSection');
    section.style.display = 'block';

    // Scroll to results
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Function to close eligible programs section
function closeEligibleProgramsModal() {
    const section = document.getElementById('eligibleProgramsSection');
    if (section) {
        section.style.display = 'none';
    }
}
