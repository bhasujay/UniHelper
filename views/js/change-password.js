// change-password.js - Change password form with OTP verification

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('changePasswordForm');
  const currentPassword = document.getElementById('currentPassword');
  const newPassword = document.getElementById('newPassword');
  const confirmNewPassword = document.getElementById('confirmNewPassword');
  const submitBtn = document.getElementById('cpSubmitBtn');
  const btnText = submitBtn.querySelector('.cp-btn-text');

  // Requirements indicators
  const reqLength = document.getElementById('req-length');
  const reqNumber = document.getElementById('req-number');
  const reqSpecial = document.getElementById('req-special');
  const reqMatch = document.getElementById('req-match');

  // Move OTP modal to document.body so it overlays the entire page
  const otpModal = document.getElementById('cpOtpModal');
  document.body.appendChild(otpModal);

  const otpInputs = otpModal.querySelectorAll('.cp-otp-digit');
  const otpError = document.getElementById('cpOtpError');
  const otpLoading = document.getElementById('cpOtpLoading');
  const otpVerifyBtn = document.getElementById('cpOtpVerifyBtn');
  const otpCancelBtn = document.getElementById('cpOtpCancelBtn');
  const otpResendLink = document.getElementById('cpOtpResendLink');

  // User email from the page (set by PHP)
  const userEmail = document.getElementById('cpOtpEmailDisplay').innerText;

  // ========== Toast Notification System ==========
  let toastContainer = document.getElementById('toastContainer');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'toastContainer';
    toastContainer.className = 'cp-toast-container';
    document.body.appendChild(toastContainer);
  }

  function showToast(message, type) {
    type = type || 'error';
    const toast = document.createElement('div');
    toast.className = 'cp-toast cp-toast-' + type;
    const typeText = type === 'success' ? 'Success' : 'Error';
    const icon = type === 'success'
      ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
      : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    toast.innerHTML =
      '<div class="cp-toast-icon">' + icon + '</div>' +
      '<div class="cp-toast-body">' +
        '<div class="cp-toast-type">' + typeText + '</div>' +
        '<div class="cp-toast-message">' + message + '</div>' +
      '</div>' +
      '<button class="cp-toast-close" aria-label="Close">&times;</button>';
    toastContainer.appendChild(toast);
    toast.querySelector('.cp-toast-close').addEventListener('click', function() { removeToast(toast); });
    setTimeout(function() { removeToast(toast); }, 5000);
  }

  function removeToast(toast) {
    if (!toast.parentNode) return;
    toast.classList.add('cp-toast-hiding');
    setTimeout(function() { toast.remove(); }, 300);
  }

  // ========== Password Toggle ==========
  document.querySelectorAll('.cp-toggle-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const targetId = btn.getAttribute('data-target');
      const input = document.getElementById(targetId);
      const eyeIcon = btn.querySelector('.eye-icon');
      const eyeSlashIcon = btn.querySelector('.eye-slash-icon');

      if (input.type === 'password') {
        input.type = 'text';
        eyeIcon.style.display = 'none';
        eyeSlashIcon.style.display = 'inline';
      } else {
        input.type = 'password';
        eyeIcon.style.display = 'inline';
        eyeSlashIcon.style.display = 'none';
      }
    });
  });

  // ========== Live Validation ==========
  function updateRequirement(el, met) {
    const dot = el.querySelector('.cp-req-dot');
    if (met) {
      el.classList.add('met');
      el.classList.remove('unmet');
    } else {
      el.classList.remove('met');
      el.classList.add('unmet');
    }
  }

  function validatePassword() {
    const pwd = newPassword.value;
    const conf = confirmNewPassword.value;

    updateRequirement(reqLength, pwd.length >= 8);
    updateRequirement(reqNumber, /\d/.test(pwd));
    updateRequirement(reqSpecial, /[^A-Za-z0-9]/.test(pwd));
    updateRequirement(reqMatch, pwd.length > 0 && pwd === conf);
  }

  newPassword.addEventListener('input', validatePassword);
  confirmNewPassword.addEventListener('input', validatePassword);

  // ========== Form Submit ==========
  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    // Client-side validation
    if (!currentPassword.value) {
      showToast('Please enter your current password.', 'error');
      currentPassword.focus();
      return;
    }

    const pwd = newPassword.value;
    if (pwd.length < 8) {
      showToast('Password must be at least 8 characters.', 'error');
      newPassword.focus();
      return;
    }
    if (!/\d/.test(pwd)) {
      showToast('Password must contain at least one number.', 'error');
      newPassword.focus();
      return;
    }
    if (!/[^A-Za-z0-9]/.test(pwd)) {
      showToast('Password must contain at least one special character.', 'error');
      newPassword.focus();
      return;
    }
    if (pwd !== confirmNewPassword.value) {
      showToast('Passwords do not match.', 'error');
      confirmNewPassword.focus();
      return;
    }

    // Disable submit
    submitBtn.disabled = true;
    btnText.textContent = 'Verifying...';

    try {
      // SHA-256 hash the current password
      const currentHash = await sha256(currentPassword.value);

      // Verify current password via API
      const formData = new FormData();
      formData.append('hashed_password', currentHash);

      const response = await fetch('/unihelper/api?controller=AuthController&action=verifyCurrentPasswordAction', {
        method: 'POST',
        body: formData
      });
      const data = await response.json();

      if (data.success) {
        // Current password correct — send OTP
        btnText.textContent = 'Sending OTP...';
        generateOtp();
      } else {
        showToast(data.message || 'Current password is incorrect.', 'error');
        submitBtn.disabled = false;
        btnText.textContent = 'Change Password';
      }
    } catch (err) {
      showToast('An error occurred. Please try again.', 'error');
      submitBtn.disabled = false;
      btnText.textContent = 'Change Password';
    }
  });

  // ========== OTP Functions ==========
  function openOtpModal() {
    otpModal.style.display = 'flex';
    otpInputs.forEach(function (input) { input.value = ''; });
    otpInputs[0].focus();
    otpError.style.display = 'none';
    otpLoading.style.display = 'none';
    otpVerifyBtn.disabled = false;
  }

  function closeOtpModal() {
    otpModal.style.display = 'none';
    submitBtn.disabled = false;
    btnText.textContent = 'Change Password';
  }

  function showOtpError(message, success) {
    if (success) {
      otpError.className = 'cp-otp-message cp-otp-success-msg';
    } else {
      otpError.className = 'cp-otp-message cp-otp-error-msg';
    }
    otpError.innerText = message;
    otpError.style.display = 'block';
  }

  function generateOtp() {
    otpLoading.style.display = 'block';

    fetch('/unihelper/api?controller=otpController&action=generateOtpAction&email=' + encodeURIComponent(userEmail), {
      method: 'GET',
      headers: { 'Content-Type': 'application/json' }
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      otpLoading.style.display = 'none';
      if (data.success) {
        openOtpModal();
        showOtpError(data.message, true);
      } else {
        showToast(data.message || 'Failed to send OTP.', 'error');
        submitBtn.disabled = false;
        btnText.textContent = 'Change Password';
      }
    })
    .catch(function () {
      otpLoading.style.display = 'none';
      showToast('Failed to send OTP. Please try again.', 'error');
      submitBtn.disabled = false;
      btnText.textContent = 'Change Password';
    });
  }

  function verifyOtp(otpCode) {
    otpLoading.style.display = 'block';
    otpVerifyBtn.disabled = true;
    otpError.style.display = 'none';

    const formData = new FormData();
    formData.append('otp', otpCode);

    fetch('/unihelper/api?controller=otpController&action=validateOtpAction', {
      method: 'POST',
      body: formData
    })
    .then(function (response) { return response.json(); })
    .then(async function (data) {
      otpLoading.style.display = 'none';
      otpVerifyBtn.disabled = false;

      if (data.success) {
        closeOtpModal();
        // OTP verified — now change the password
        await submitNewPassword();
      } else {
        showOtpError(data.message || 'Invalid OTP. Please try again.', false);
      }
    })
    .catch(function () {
      otpLoading.style.display = 'none';
      otpVerifyBtn.disabled = false;
      showOtpError('Verification failed. Please try again.', false);
    });
  }

  async function submitNewPassword() {
    try {
      submitBtn.disabled = true;
      btnText.textContent = 'Updating...';

      const newHash = await sha256(newPassword.value);

      const formData = new FormData();
      formData.append('new_hashed_password', newHash);

      const response = await fetch('/unihelper/api?controller=AuthController&action=changePasswordAction', {
        method: 'POST',
        body: formData
      });
      const data = await response.json();

      if (data.success) {
        showToast('Password changed successfully!', 'success');
        btnText.textContent = 'Done!';
        // Redirect to profile view after short delay
        setTimeout(function () {
          window.location.href = '/unihelper/profile/view';
        }, 1500);
      } else {
        showToast(data.message || 'Failed to change password.', 'error');
        submitBtn.disabled = false;
        btnText.textContent = 'Change Password';
      }
    } catch (err) {
      showToast('An error occurred while updating password.', 'error');
      submitBtn.disabled = false;
      btnText.textContent = 'Change Password';
    }
  }

  // ========== OTP Input Handling ==========
  otpInputs.forEach(function (input, index) {
    input.addEventListener('input', function (e) {
      if (e.target.value && index < otpInputs.length - 1) {
        otpInputs[index + 1].focus();
      }
    });

    input.addEventListener('keydown', function (e) {
      if (e.key === 'Backspace' && !e.target.value && index > 0) {
        otpInputs[index - 1].focus();
      }
    });

    input.addEventListener('paste', function (e) {
      e.preventDefault();
      var pasteData = e.clipboardData.getData('text').slice(0, 6);
      pasteData.split('').forEach(function (char, i) {
        if (otpInputs[i]) {
          otpInputs[i].value = char;
        }
      });
      if (pasteData.length === 6) {
        otpInputs[5].focus();
      }
    });
  });

  // OTP Verify Button
  otpVerifyBtn.addEventListener('click', function () {
    var otpCode = Array.from(otpInputs).map(function (input) { return input.value; }).join('');
    if (otpCode.length !== 6) {
      showOtpError('Please enter all 6 digits', false);
      return;
    }
    verifyOtp(otpCode);
  });

  // OTP Cancel Button
  otpCancelBtn.addEventListener('click', function () {
    closeOtpModal();
  });

  // OTP Resend Link
  otpResendLink.addEventListener('click', function () {
    generateOtp();
  });

  // ========== SHA-256 Helper ==========
  async function sha256(message) {
    var buffer = await window.crypto.subtle.digest('SHA-256', new TextEncoder().encode(message));
    var hashArray = Array.from(new Uint8Array(buffer));
    return hashArray.map(function (b) { return b.toString(16).padStart(2, '0'); }).join('');
  }
});
