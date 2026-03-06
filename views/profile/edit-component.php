<link rel="stylesheet" href="/unihelper/views/css/profile.css">

<?php 
require_once dirname(__DIR__, 2) . '/models/University.php';
require_once dirname(__DIR__, 2) . '/models/Major.php';
$universities = (new app\models\University())->getAll();
$majors = (new app\models\Major())->getAll();
?>

<div class="profile-card-container">
    <div class="profile-card">
        <div class="profile-header">
            <h1 class="profile-title">Edit Profile</h1>
            <a href="profile/view" class="btn btn-outline">
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

            <form action="profile/update" method="POST" enctype="multipart/form-data" class="profile-edit-form">
                <!-- Hidden input for remove profile picture flag -->
                <input type="hidden" id="removeProfilePicture" name="removeProfilePicture" value="0">
                
                <!-- Profile Picture Upload -->
                <div class="profile-edit-section">
                    <div class="profile-upload-container">
                        <div class="profile-image-preview">
                            <?php if($user->profilePicture): ?>
                                <img src="<?= htmlspecialchars($user->profilePicture ? "/unihelper/public/" . $user->profilePicture : '/unihelper/views/assets/default-pfp.png') ?>" alt="Profile Picture" id="profileImagePreview">
                            <?php endif; ?>
                        </div>
                        <div class="profile-upload-controls">
                            <label for="profilePicture" class="btn btn-outline upload-btn">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                Upload Photo
                            </label>
                            <input type="file" id="profilePicture" name="profilePicture" accept="image/*" class="profile-file-input" onchange="previewImage(this)">
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

                        <!-- Public/Private Switch -->
                        <div class="profile-public-switch">
                            <label class="switch-label" for="publicSwitch">
                                <span class="switch-text">Account Visibility:</span>
                                <span class="switch-status" id="publicStatus"><?= !empty($user->public) ? 'Public' : 'Private' ?></span>
                            </label>
                            <label class="switch">
                                <input type="checkbox" id="publicSwitch" name="public" value="1" <?= !empty($user->public) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="profile-edit-section">
                    <h3 class="profile-section-title">Personal Information</h3>
                    <div class="profile-edit-grid">
                        <div class="profile-edit-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" name="firstName" value="<?= htmlspecialchars($user->firstName) ?>" required>
                        </div>
                        <div class="profile-edit-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" name="lastName" value="<?= htmlspecialchars($user->lastName) ?>" required>
                        </div>
                        <div class="profile-edit-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user->email) ?>" required>
                        </div>
                        <div class="profile-edit-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user->phone) ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Role-specific Information -->
                <?php if($user->role === 'role-applicant'): ?>
                <div class="profile-edit-section">
                    <h3 class="profile-section-title">Applicant Information</h3>
                    <div class="profile-edit-grid">
                        <div class="profile-edit-group">
                            <label for="alYear">A/L Year</label>
                            <input type="text" id="alYear" name="alYear" value="<?= htmlspecialchars($user->alYear ?? '') ?>">
                        </div>
                    </div>
                </div>
                <?php elseif($user->role === 'role-undergrad'): ?>
                <div class="profile-edit-section">
                    <h3 class="profile-section-title">University Information</h3>
                    <div class="profile-edit-grid">
                        <div class="profile-edit-group">
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
                        <div class="profile-edit-group">
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
                <div class="profile-edit-section">
                    <h3 class="profile-section-title">University Profile</h3>
                    <div class="profile-edit-grid">
                        <div class="profile-edit-group">
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
                        <div class="profile-edit-group">
                            <label for="profileRole">Role</label>
                            <input type="text" id="profileRole" name="profileRole" value="<?= htmlspecialchars($user->profileRole ?? '') ?>">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Form Actions -->
                <div class="profile-edit-actions">
                    <button type="submit" class="text1 btn btn-primary">Save Changes</button>
                    <a href="profile/view" class="btn btn-outline">Cancel</a>
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
                const preview = document.getElementById('profileImagePreview');
                
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
            
            // Reset remove flag when new image is selected
            document.getElementById('removeProfilePicture').value = '0';
        }
    }

    // Remove profile image
    async function removeProfileImage() {
        if (!await confirm('Are you sure you want to remove your profile photo?')) {
            return;
        }

        // Set the remove flag to 1
        document.getElementById('removeProfilePicture').value = '1';

        const fileInput = document.getElementById('profilePicture');
        if (fileInput) {
            fileInput.value = '';
        }

        const preview = document.getElementById('profileImagePreview');
        if (preview) {
            preview.style.filter = 'blur(4px)';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const publicSwitch = document.getElementById('publicSwitch');
        const publicStatus = document.getElementById('publicStatus');
        if (publicSwitch && publicStatus) {
            publicSwitch.addEventListener('change', function() {
                publicStatus.textContent = this.checked ? 'Public' : 'Private';
            });
        }
    });
</script>