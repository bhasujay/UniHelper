<div class="dashboard-header">
    <div>
        <h1 class="dashboard-title">Degree Programs Management</h1>
        <p class="dashboard-subtitle">Add, edit and manage degree programs</p>
    </div>
</div>

<div class="admin-content-grid">
    <!-- Left Side: More compact form to add new degree programs -->
    <div class="admin-form-container">
        <div class="admin-form-header">
            <h2 class="admin-form-title">Add New Degree Program</h2>
        </div>
        
        <form id="addDegreeForm">
            <div class="admin-form-group">
                <label for="university" class="admin-form-label">University</label>
                <select id="university" name="university" class="admin-form-select" required>
                    <option value="">Select University</option>
                    <option value="1">University of Colombo</option>
                    <option value="2">University of Peradeniya</option>
                    <option value="3">University of Sri Jayewardenepura</option>
                    <option value="4">University of Kelaniya</option>
                    <option value="5">University of Moratuwa</option>
                </select>
            </div>
            
            <div class="admin-form-group">
                <label for="major" class="admin-form-label">Major</label>
                <select id="major" name="major" class="admin-form-select" required>
                    <option value="">Select Major</option>
                    <option value="1">Computer Science</option>
                    <option value="2">Engineering</option>
                    <option value="3">Medicine</option>
                    <option value="4">Business Administration</option>
                    <option value="5">Law</option>
                </select>
            </div>
            
            <div class="admin-form-group">
                <label for="degreeName" class="admin-form-label">Degree Name</label>
                <input type="text" id="degreeName" name="degreeName" class="admin-form-input" placeholder="Bachelor of Science in Computer Science" required>
            </div>
            
            <div class="admin-form-group">
                <label for="stream" class="admin-form-label">Stream</label>
                <input type="text" id="stream" name="stream" class="admin-form-input" placeholder="Technology/Physical Science/Bio Science" required>
            </div>
            
            <div class="admin-form-group">
                <label for="unicode" class="admin-form-label">Unicode</label>
                <input type="text" id="unicode" name="unicode" class="admin-form-input" placeholder="123A" pattern="[0-9]{3}[A-Z]{1}" title="Three digits followed by a capital letter" required>
            </div>
            
            <div class="admin-form-group">
                <label for="description" class="admin-form-label">Description</label>
                <textarea id="description" name="description" class="admin-form-textarea" placeholder="Brief description of the degree program..." required></textarea>
            </div>
            
            <div class="admin-form-group">
                <label for="duration" class="admin-form-label">Duration (Years)</label>
                <input type="number" id="duration" name="duration" min="1" max="7" class="admin-form-input" placeholder="4" required>
            </div>
            
            <button type="submit" class="admin-form-button">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Publish Degree Program
            </button>
        </form>
    </div>
    
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
        </div>
        
        <div class="admin-list-content">
            <?php if (!empty($degrees)): ?>
                <?php foreach ($degrees as $degree): ?>
                    <div class="degree-card">
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