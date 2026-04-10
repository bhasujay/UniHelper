<link rel="stylesheet" href="/unihelper/views/css/profile.css">

<div class="profile-card-container">
    <div class="profile-card">
        <div class="profile-header">
            <h1 class="profile-title">Change Password</h1>
            <a href="profile/view" class="btn btn-outline">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Profile
            </a>
        </div>

        <div class="profile-card-body">
            <form id="changePasswordForm" class="profile-edit-form" autocomplete="off">
                <!-- Security Icon Banner -->
                <div class="cp-security-banner">
                    <div class="cp-security-icon">
                        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <div class="cp-security-text">
                        <p class="cp-security-title">Secure Password Update</p>
                        <p class="cp-security-subtitle">Your identity will be verified via email OTP before the change takes effect.</p>
                    </div>
                </div>

                <!-- Current Password Section -->
                <div class="profile-edit-section">
                    <h3 class="profile-section-title">Current Password</h3>
                    <div class="profile-edit-grid">
                        <div class="profile-edit-group">
                            <label for="currentPassword">Current Password</label>
                            <div class="cp-input-wrapper">
                                <input type="password" id="currentPassword" name="currentPassword" required autocomplete="current-password" placeholder="Enter your current password">
                                <button type="button" class="cp-toggle-btn" data-target="currentPassword" aria-label="Toggle password visibility">
                                    <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="7" ry="5"/><circle cx="12" cy="12" r="2"/></svg>
                                    <svg class="eye-slash-icon" style="display:none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="7" ry="5"/><circle cx="12" cy="12" r="2"/><line x1="5" y1="5" x2="19" y2="19"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Password Section -->
                <div class="profile-edit-section">
                    <h3 class="profile-section-title">New Password</h3>
                    <div class="profile-edit-grid">
                        <div class="profile-edit-group">
                            <label for="newPassword">New Password</label>
                            <div class="cp-input-wrapper">
                                <input type="password" id="newPassword" name="newPassword" required autocomplete="new-password" placeholder="Enter new password">
                                <button type="button" class="cp-toggle-btn" data-target="newPassword" aria-label="Toggle password visibility">
                                    <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="7" ry="5"/><circle cx="12" cy="12" r="2"/></svg>
                                    <svg class="eye-slash-icon" style="display:none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="7" ry="5"/><circle cx="12" cy="12" r="2"/><line x1="5" y1="5" x2="19" y2="19"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="profile-edit-group">
                            <label for="confirmNewPassword">Confirm New Password</label>
                            <div class="cp-input-wrapper">
                                <input type="password" id="confirmNewPassword" name="confirmNewPassword" required autocomplete="new-password" placeholder="Re-enter new password">
                                <button type="button" class="cp-toggle-btn" data-target="confirmNewPassword" aria-label="Toggle password visibility">
                                    <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="7" ry="5"/><circle cx="12" cy="12" r="2"/></svg>
                                    <svg class="eye-slash-icon" style="display:none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="7" ry="5"/><circle cx="12" cy="12" r="2"/><line x1="5" y1="5" x2="19" y2="19"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password Requirements -->
                <div class="cp-requirements">
                    <div class="cp-req-item" id="req-length">
                        <span class="cp-req-dot"></span>
                        <span>At least 8 characters</span>
                    </div>
                    <div class="cp-req-item" id="req-number">
                        <span class="cp-req-dot"></span>
                        <span>At least one number</span>
                    </div>
                    <div class="cp-req-item" id="req-special">
                        <span class="cp-req-dot"></span>
                        <span>At least one special character</span>
                    </div>
                    <div class="cp-req-item" id="req-match">
                        <span class="cp-req-dot"></span>
                        <span>Passwords match</span>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="profile-edit-actions">
                    <button type="submit" class="btn btn-primary" id="cpSubmitBtn">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span class="cp-btn-text">Change Password</span>
                    </button>
                    <a href="profile/view" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- OTP Modal — will be moved to document.body by JS so it overlays the entire page -->
<div id="cpOtpModal" class="cp-otp-overlay" style="display:none">
    <div class="cp-otp-modal-content">
        <div class="cp-otp-header">
            <div class="cp-otp-icon-wrapper">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <h2 class="cp-otp-title">Verify Your Identity</h2>
            <p class="cp-otp-subtitle">We've sent a 6-digit code to <strong id="cpOtpEmailDisplay"><?= htmlspecialchars($user->email) ?></strong></p>
        </div>
        <div class="cp-otp-inputs">
            <input type="text" maxlength="1" class="cp-otp-digit" data-index="0">
            <input type="text" maxlength="1" class="cp-otp-digit" data-index="1">
            <input type="text" maxlength="1" class="cp-otp-digit" data-index="2">
            <input type="text" maxlength="1" class="cp-otp-digit" data-index="3">
            <input type="text" maxlength="1" class="cp-otp-digit" data-index="4">
            <input type="text" maxlength="1" class="cp-otp-digit" data-index="5">
        </div>
        <div class="cp-otp-message cp-otp-error-msg" id="cpOtpError"></div>
        <div class="cp-otp-message cp-otp-loading-msg" id="cpOtpLoading">Verifying...</div>
        <div class="cp-otp-actions">
            <button type="button" class="btn btn-outline cp-otp-cancel-btn" id="cpOtpCancelBtn">Cancel</button>
            <button type="button" class="btn btn-primary cp-otp-verify-btn" id="cpOtpVerifyBtn">
                <span>Verify & Update</span>
            </button>
        </div>
        <div class="cp-otp-resend">
            Didn't receive code? <a class="cp-otp-resend-link" id="cpOtpResendLink">Resend</a>
        </div>
    </div>
</div>

<script src="/unihelper/views/js/change-password.js"></script>
