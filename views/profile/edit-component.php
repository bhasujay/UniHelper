<div class="profile-container">
    <div class="profile-card">
        <div class="profile-header">
            <h1 class="profile-title">Edit Profile</h1>
            <a href="profile" class="btn btn-outline">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Profile
            </a>
        </div>

        <div class="profile-card-body">
            <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if(isset($success)): ?>
                <div class="alert alert-success">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <form action="profile/update" method="POST" enctype="multipart/form-data" class="profile-form">
                <!-- Profile Picture Upload -->
                <div class="form-section">
                    <h3 class="section-title">Profile Picture</h3>
                    <div class="profile-upload-container">
                        <div class="profile-image-preview">
                            <?php if($user->profilePicture): ?>
                                <img src="<?= htmlspecialchars($user->profilePicture) ?>" alt="Profile Picture" id="profileImagePreview">
                            <?php else: ?>
                                <div class="profile-image-placeholder" id="profilePlaceholder">
                                    <?= strtoupper(substr($user->firstName, 0, 1) . substr($user->lastName, 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="upload-controls">
                            <label for="profilePicture" class="btn btn-outline upload-btn">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                Upload Photo
                            </label>
                            <input type="file" id="profilePicture" name="profilePicture" accept="image/*" class="file-input" onchange="previewImage(this)">
                            <?php if($user->profilePicture): ?>
                                <button type="button" class="btn btn-outline" onclick="removeProfileImage()">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    Remove Photo
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="form-section">
                    <h3 class="section-title">Personal Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" name="firstName" value="<?= htmlspecialchars($user->firstName) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" name="lastName" value="<?= htmlspecialchars($user->lastName) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user->email) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user->phone) ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Role-specific Information -->
                <?php if($user->role === 'role-applicant'): ?>
                <div class="form-section">
                    <h3 class="section-title">Applicant Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="alYear">A/L Year</label>
                            <input type="text" id="alYear" name="alYear" value="<?= htmlspecialchars($user->alYear ?? '') ?>">
                        </div>
                    </div>
                </div>
                <?php elseif($user->role === 'role-undergrad'): ?>
                <div class="form-section">
                    <h3 class="section-title">University Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="undergradUniversity">University</label>
                            <select id="undergradUniversity" name="undergradUniversity">
                                <option value="">Select University</option>
                                <?php foreach($universities as $university): ?>
                                    <option value="<?= $university->id ?>" <?= ($user->University == $university->id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($university->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="major">Major</label>
                            <select id="major" name="major">
                                <option value="">Select Major</option>
                                <?php foreach($majors as $majorItem): ?>
                                    <option value="<?= $majorItem->id ?>" <?= ($user->major == $majorItem->id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($majorItem->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php elseif($user->role === 'role-profile'): ?>
                <div class="form-section">
                    <h3 class="section-title">University Profile</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="profileUniversity">University</label>
                            <select id="profileUniversity" name="profileUniversity">
                                <option value="">Select University</option>
                                <?php foreach($universities as $university): ?>
                                    <option value="<?= $university->id ?>" <?= ($user->University == $university->id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($university->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="profileRole">Role</label>
                            <input type="text" id="profileRole" name="profileRole" value="<?= htmlspecialchars($user->profileRole ?? '') ?>">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="profile" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Image preview functionality
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const placeholder = document.getElementById('profilePlaceholder');
                const preview = document.getElementById('profileImagePreview');
                
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
                
                if (!preview) {
                    const newPreview = document.createElement('img');
                    newPreview.id = 'profileImagePreview';
                    newPreview.alt = 'Profile Picture';
                    newPreview.src = e.target.result;
                    document.querySelector('.profile-image-preview').appendChild(newPreview);
                } else {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Remove profile image
    function removeProfileImage() {
        const preview = document.getElementById('profileImagePreview');
        const placeholder = document.getElementById('profilePlaceholder');
        
        if (preview) {
            preview.style.display = 'none';
        }
        
        if (placeholder) {
            placeholder.style.display = 'flex';
        } else {
            const initials = '<?= strtoupper(substr($user->firstName, 0, 1) . substr($user->lastName, 0, 1)) ?>';
            const newPlaceholder = document.createElement('div');
            newPlaceholder.id = 'profilePlaceholder';
            newPlaceholder.className = 'profile-image-placeholder';
            newPlaceholder.textContent = initials;
            document.querySelector('.profile-image-preview').appendChild(newPlaceholder);
        }
        
        // Add a hidden input to indicate image removal
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'removeProfilePicture';
        hiddenInput.value = '1';
        document.querySelector('form').appendChild(hiddenInput);
    }
</script>