<?php

// This component is view-only. Data is expected from the controller layer.
$universities = (isset($universities) && is_iterable($universities)) ? $universities : [];
$majors = (isset($majors) && is_iterable($majors)) ? $majors : [];
$degrees = (isset($degrees) && is_iterable($degrees)) ? $degrees : [];
?>

<div class="admin-content-grid">
    <!-- Left Side: Form to add new degree programs -->
    <div class="admin-form-container">
        <div class="admin-form-header">
            <h2 class="admin-form-title">Add New Degree Program</h2>
        </div>
        
        <!-- Replace or update the existing form tag with this one -->
        <form id="addDegreeForm" action="/unihelper/api?controller=ProgramController&action=addDegreeProgram" method="POST">
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

            <div class="admin-form-group">
                <label for="pathDescription" class="admin-form-label">Entry Path Description</label>
                <input
                    type="text"
                    id="pathDescription"
                    name="pathDescription"
                    class="admin-form-input"
                    value="Default Entry Path"
                    placeholder="e.g., Physical Science Standard Path"
                >
            </div>

            <div class="admin-form-group">
                <label for="subjectRequirements" class="admin-form-label">Subject Requirements</label>
                <textarea
                    id="subjectRequirements"
                    name="subjectRequirements"
                    class="admin-form-textarea"
                    placeholder="One subject per line. Use Subject|Grade (e.g., Combined Mathematics|S)"
                ></textarea>
                <small class="admin-form-help">Format: one subject per line as Subject|MinimumGrade. Example: Physics|C</small>
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

                        <?php if (!empty($degree->subject_requirements)): ?>
                            <div class="degree-card-requirements">
                                <span class="degree-card-item-label">Subject Requirements</span>
                                <ul class="degree-card-requirements-list">
                                    <?php foreach ($degree->subject_requirements as $requirement): ?>
                                        <li>
                                            <?= htmlspecialchars($requirement['subject_name']) ?>
                                            <span>(Min: <?= htmlspecialchars($requirement['min_grade']) ?>)</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

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

<script src="views/js/degree-programs-management.js"></script>