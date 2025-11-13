<?php ?>
<div class="profile-card-container">
    <div class="profile-card">
        <div class="profile-header">
            <h1 class="profile-title">My Profile</h1>
            <a href="profile/edit" class="btn btn-outline">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Edit Profile
            </a>
        </div>

        <div class="profile-card-header">
            <?php if($user->profilePicture): ?>
                <div class="profile-image">
                    <img src="<?= htmlspecialchars($user->profilePicture ? "/unihelper/public/" . $user->profilePicture : '/unihelper/views/assets/default-pfp.png') ?>" alt="Profile Picture">
                </div>
            <?php endif; ?>
            
            <div class="profile-card-name-section">
                <h2 class="profile-card-name"><?= htmlspecialchars($user->firstName . ' ' . $user->lastName) ?></h2>
                <div class="profile-role-badge"><?= htmlspecialchars(substr($user->role, 5)) ?></div>
            </div>

            <div class="profile-public-card">
                <?php if (!empty($user->public)): ?>
                    <div class="public-status public">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" fill="#4caf50"/>
                            <path d="M9 12l2 2l4 -4" stroke="#fff" stroke-width="2" fill="none"/>
                        </svg>
                        <span>Public Account</span>
                    </div>
                <?php else: ?>
                    <div class="public-status private">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" fill="#f44336"/>
                            <path d="M8 12l2 2l4 -4" stroke="#fff" stroke-width="2" fill="none"/>
                        </svg>
                        <span>Private Account</span>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        
        <div class="profile-card-body">
            <div class="profile-info-section">
                <h3>Contact Information</h3>
                <div class="profile-info-items">
                    <div class="profile-info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= htmlspecialchars($user->email) ?></span>
                    </div>
                    <div class="profile-info-item">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?= htmlspecialchars($user->phone) ?></span>
                    </div>
                </div>
            </div>
            
            <?php if($user->role === 'role-applicant'): ?>
            <div class="profile-info-section">
                <h3>Applicant Information</h3>
                <div class="profile-info-items">
                    <div class="profile-info-item">
                        <span class="info-label">A/L Year</span>
                        <span class="info-value"><?= htmlspecialchars($user->alYear ?? 'Not specified') ?></span>
                    </div>
                </div>
            </div>
            <?php elseif($user->role === 'role-undergrad'): ?>
            <div class="profile-info-section">
                <h3>University Information</h3>
                <div class="profile-info-items">
                    <div class="profile-info-item">
                        <span class="info-label">University</span>
                        <span class="info-value">
                            <?php 
                            if (isset($user->University) && is_numeric($user->University)) {
                                $universityModel = new app\models\University();
                                $universities = $universityModel->getAll();
                                foreach ($universities as $uni) {
                                    if ($uni->id == $user->University) {
                                        echo htmlspecialchars($uni->name);
                                        break;
                                    }
                                }
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="profile-info-item">
                        <span class="info-label">Major</span>
                        <span class="info-value">
                            <?php 
                            if (isset($user->major) && is_numeric($user->major)) {
                                $majorModel = new app\models\Major();
                                $majors = $majorModel->getAll();
                                foreach ($majors as $m) {
                                    if ($m->id == $user->major) {
                                        echo htmlspecialchars($m->name);
                                        break;
                                    }
                                }
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php elseif($user->role === 'role-profile'): ?>
            <div class="profile-info-section">
                <h3>University Profile</h3>
                <div class="profile-info-items">
                    <div class="profile-info-item">
                        <span class="info-label">University</span>
                        <span class="info-value">
                            <?php 
                            if (isset($user->University) && is_numeric($user->University)) {
                                $universityModel = new app\models\University();
                                $universities = $universityModel->getAll();
                                foreach ($universities as $uni) {
                                    if ($uni->id == $user->University) {
                                        echo htmlspecialchars($uni->name);
                                        break;
                                    }
                                }
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="profile-info-item">
                        <span class="info-label">Role</span>
                        <span class="info-value"><?= htmlspecialchars($user->profileRole ?? 'Not specified') ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="profile-actions">
                <a href="profile/change-password" class="btn btn-outline">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    Change Password
                </a>
            </div>
        </div>
    </div>
</div>