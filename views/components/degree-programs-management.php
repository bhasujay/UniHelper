<?php

// Initialize models
$majorModel = new \app\models\Major();
$universityModel = new \app\models\University();
$degreeModel = new \app\models\DegreeProgram();

// Fetch all universities and majors
$universities = $universityModel->getAll();
$majors = $majorModel->getAll();
$degrees = $degreeModel->getAllDegrees();
?>

<div class="admin-content-grid">
    <!-- Left Side: Form to add new degree programs -->
    <div class="admin-form-container">
        <div class="admin-form-header">
            <h2 class="admin-form-title">Add New Degree Program</h2>
        </div>
        
        <!-- Replace or update the existing form tag with this one -->
        <form id="addDegreeForm" action="/unihelper/dashboard/admin/degreemanage/add" method="POST">
            <div class="admin-form-group">
                <label for="university" class="admin-form-label">University</label>
                <select id="university" name="university" class="admin-form-select" required>
                    <option value="">Select University</option>
                    <?php foreach ($universities as $university): ?>
                        <option value="<?= $university->id ?>"><?= htmlspecialchars($university->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="admin-form-group">
                <label for="major" class="admin-form-label">Major</label>
                <select id="major" name="major" class="admin-form-select" required>
                    <option value="">Select Major</option>
                    <?php foreach ($majors as $major): ?>
                        <option value="<?= $major->id ?>"><?= htmlspecialchars($major->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="admin-form-group">
                <label for="degreeName" class="admin-form-label">Degree Name</label>
                <input type="text" id="degreeName" name="degreeName" class="admin-form-input" placeholder="Name" required>
            </div>
            
            <!-- Three fields side by side -->
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="stream" class="admin-form-label">Stream</label>
                    <select id="stream" name="stream" class="admin-form-select" required>
                        <option value="">Select Stream</option>
                        <option value="physical-science">Physical Science</option>
                        <option value="biological-science">Biological Science</option>
                        <option value="technology">Technology</option>
                        <option value="commerce">Commerce</option>
                        <option value="arts">Arts</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="admin-form-group">
                    <label for="unicode" class="admin-form-label">Unicode</label>
                    <input type="text" id="unicode" name="unicode" class="admin-form-input" placeholder="123A" pattern="[0-9]{3}[A-Z]{1}" title="Three digits followed by a capital letter" required>
                </div>
                
                <div class="admin-form-group">
                    <label for="duration" class="admin-form-label">Duration (Years)</label>
                    <input type="number" id="duration" name="duration" min="1" max="7" class="admin-form-input" placeholder="4" required>
                </div>
            </div>
            
            <div class="admin-form-group">
                <label for="description" class="admin-form-label">Description</label>
                <textarea id="description" name="description" class="admin-form-textarea" placeholder="Brief description of the degree program..." required></textarea>
            </div>
            
            <button type="submit" class="admin-form-button">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Publish Degree Program
            </button>
        </form>
    </div>
    
    <!-- Right Side: List of degree programs -->
    <!-- Right Side: List of degree programs with same height as form -->
    <div class="admin-list-container">
        <div class="admin-list-header">
            <h2 class="admin-list-title">Existing Degree Programs</h2>
        </div>
        
        <div class="admin-search-container">
            <input type="text" class="admin-search-input" placeholder="Search degrees...">
            <svg class="admin-search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <div class="admin-search-options">
                <label class="admin-search-option">
                    <input type="radio" name="searchIndex" value="name" checked>
                    <span>Name</span>
                </label>
                <label class="admin-search-option">
                    <input type="radio" name="searchIndex" value="unicode">
                    <span>Unicode</span>
                </label>
            </div>
        </div>
        
        <div class="admin-list-content">
            <?php if (!empty($degrees)): ?>
                <?php foreach ($degrees as $degree): ?>
                    <!-- Added data-degree-id to the entire card as well -->
                    <div class="degree-card" data-degree-id="<?= $degree->id ?>">
                        <!-- Add a hidden input with the ID -->
                        <input type="hidden" name="degree_id" value="<?= $degree->id ?>">
                        
                        <div class="degree-card-header">
                            <h3 class="degree-card-title"><?= htmlspecialchars($degree->name) ?></h3>
                            <span class="degree-card-code"><?= htmlspecialchars($degree->unicode) ?></span>
                        </div>
                        <div class="degree-card-info">
                            <div class="degree-card-item">
                                <span class="degree-card-item-label">University</span>
                                <span class="degree-card-item-value"><?= htmlspecialchars($degree->university) ?></span>
                            </div>
                            <div class="degree-card-item">
                                <span class="degree-card-item-label">Stream</span>
                                <span class="degree-card-item-value"><?= htmlspecialchars($degree->stream) ?></span>
                            </div>
                            <div class="degree-card-item">
                                <span class="degree-card-item-label">Duration</span>
                                <span class="degree-card-item-value"><?= htmlspecialchars($degree->duration) ?> Years</span>
                            </div>
                            <div class="degree-card-item">
                                <span class="degree-card-item-label">Status</span>
                                <span class="status-badge status-<?= htmlspecialchars(strtolower($degree->status)) ?>"><?= htmlspecialchars($degree->status) ?></span>
                            </div>
                        </div>
                        <div class="degree-card-description">
                            <?= htmlspecialchars($degree->description) ?>
                        </div>
                        <div class="degree-card-actions">
                            <button class="degree-card-button" title="Edit" data-degree-id="<?= $degree->id ?>">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="degree-card-button delete-button" title="Delete" data-degree-id="<?= $degree->id ?>">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📚</div>
                    <p class="empty-state-message">No degree programs found. Add your first program using the form.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add this script at the end of your file, before the closing </div> tag -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the search input element
    const searchInput = document.querySelector('.admin-search-input');
    
    // Get all degree cards
    const degreeCards = document.querySelectorAll('.degree-card');
    
    // Get search index radio buttons
    const searchOptions = document.querySelectorAll('input[name="searchIndex"]');
    
    // Get empty state element or create one if it doesn't exist
    const emptyState = document.querySelector('.empty-state') || createEmptyState();
    
    // Add event listener for input changes
    searchInput.addEventListener('input', filterCards);
    
    // Add event listeners for radio button changes
    searchOptions.forEach(option => {
        option.addEventListener('change', filterCards);
    });
    
    // Filter function that handles the actual search
    function filterCards() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const searchIndex = document.querySelector('input[name="searchIndex"]:checked').value;
        let matchFound = false;
        
        // Loop through all degree cards
        degreeCards.forEach(card => {
            // Get the searchable content based on selected index
            let contentToSearch;
            
            if (searchIndex === 'name') {
                contentToSearch = card.querySelector('.degree-card-title').textContent.toLowerCase();
            } else if (searchIndex === 'unicode') {
                contentToSearch = card.querySelector('.degree-card-code').textContent.toLowerCase();
            }
            
            // Check if the search term is in the selected field
            if (contentToSearch.includes(searchTerm)) {
                card.style.display = 'block';
                matchFound = true;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show/hide empty state message
        if (!matchFound && degreeCards.length > 0) {
            if (searchTerm === '') {
                emptyState.style.display = 'none'; // Show all cards if search is empty
                degreeCards.forEach(card => card.style.display = 'block');
            } else {
                emptyState.querySelector('.empty-state-message').textContent = 'No matching degree programs found.';
                document.querySelector('.admin-list-content').appendChild(emptyState);
                emptyState.style.display = 'flex';
            }
        } else {
            emptyState.style.display = 'none';
        }
    }
    
    // Helper function to create empty state if it doesn't exist
    function createEmptyState() {
        const emptyState = document.createElement('div');
        emptyState.className = 'empty-state';
        emptyState.innerHTML = `
            <div class="empty-state-icon">🔍</div>
            <p class="empty-state-message">No matching degree programs found.</p>
        `;
        return emptyState;
    }
    
    // Add delete functionality to delete buttons
    document.querySelectorAll('.delete-button').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const degreeId = this.getAttribute('data-degree-id');
            const degreeName = this.closest('.degree-card').querySelector('.degree-card-title').textContent;
            
            // Show confirmation dialog
            if (await confirm(`Are you sure you want to delete the degree program "${degreeName}"? This action cannot be undone.`)) {
                // Send request to delete endpoint
                window.location.href = `/unihelper/dashboard/admin/degreemanage/remove/${degreeId}`;
            }
        });
    });
    
    // Add edit functionality to edit buttons
    document.querySelectorAll('.degree-card-button:not(.delete-button)').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const degreeId = this.getAttribute('data-degree-id');
            const degreeCard = this.closest('.degree-card');
            
            // Hide all other cards
            document.querySelectorAll('.degree-card').forEach(card => {
                if (card !== degreeCard) {
                    card.style.display = 'none';
                }
            });
            
            // Get degree data from the card to fill the form
            const degreeName = degreeCard.querySelector('.degree-card-title').textContent.trim();
            const unicode = degreeCard.querySelector('.degree-card-code').textContent.trim();
            const university = degreeCard.querySelector('.degree-card-item:nth-child(1) .degree-card-item-value').textContent.trim();
            const stream = degreeCard.querySelector('.degree-card-item:nth-child(2) .degree-card-item-value').textContent.trim();
            const duration = degreeCard.querySelector('.degree-card-item:nth-child(3) .degree-card-item-value').textContent.trim().replace(' Years', '');
            const description = degreeCard.querySelector('.degree-card-description').textContent.trim();
            
            // Fetch the complete degree data to get IDs
            fetch(`/unihelper/dashboard/admin/degreemanage/get/${degreeId}`)
                .then(response => response.json())
                .then(data => {
                    // Fill form with degree data
                    document.getElementById('degreeName').value = degreeName;
                    document.getElementById('unicode').value = unicode;
                    document.getElementById('duration').value = duration;
                    document.getElementById('description').value = description;
                    
                    // Set dropdowns
                    document.getElementById('university').value = data.university_id;
                    document.getElementById('major').value = data.major_id;
                    
                    // REPLACE THIS SECTION - Directly set the stream value from the card
                    document.getElementById('stream').value = stream;
                    
                    // Change form action and button text
                    const form = document.getElementById('addDegreeForm');
                    form.action = `/unihelper/dashboard/admin/degreemanage/update/${degreeId}`;
                    
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = `
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Edit Degree Program
                    `;
                    
                    // Add a cancel button
                    if (!document.querySelector('.cancel-edit')) {
                        const cancelBtn = document.createElement('button');
                        cancelBtn.type = 'button';
                        cancelBtn.className = 'admin-form-button cancel-edit';
                        cancelBtn.style.marginRight = '10px';
                        cancelBtn.innerHTML = 'Cancel';
                        cancelBtn.onclick = function() {
                            // Reset form
                            form.reset();
                            form.action = '/unihelper/dashboard/admin/degreemanage/add';
                            submitBtn.innerHTML = `
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                Publish Degree Program
                            `;
                            
                            // Show all cards again
                            document.querySelectorAll('.degree-card').forEach(card => {
                                card.style.display = 'block';
                            });
                            
                            // Remove cancel button
                            cancelBtn.remove();
                        };
                        
                        submitBtn.before(cancelBtn);
                    }
                })
                .catch(error => console.error('Error fetching degree data:', error));
        });
    });
});
</script>