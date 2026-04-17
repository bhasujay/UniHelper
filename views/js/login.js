// login.js - Login form validation with OTP verification
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('loginForm');
  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');
  const loginBtn = document.getElementById('loginBtn');
  const loadingIcon = document.querySelector('.btn-loading-icon');
  const passwordToggle = document.getElementById('passwordToggle');

  // Error message element
  let errorBox = document.getElementById('loginError');
  if (!errorBox) {
    errorBox = document.createElement('div');
    errorBox.id = 'loginError';
    errorBox.style.display = 'none';
    errorBox.className = 'auth-error';
    form.insertBefore(errorBox, form.firstChild);
  }

  // Inline SVG icons for eye and eye-slash
  const eyeSVG = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#8e8e8eff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="7" ry="5"/><circle cx="12" cy="12" r="2"/></svg>';
  const eyeSlashSVG = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#8e8e8eff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="7" ry="5"/><circle cx="12" cy="12" r="2"/><line x1="5" y1="5" x2="19" y2="19"/></svg>';

  // Password show/hide toggle
  if (passwordToggle && passwordInput) {
    passwordToggle.innerHTML = eyeSVG;
    passwordToggle.addEventListener('click', function() {
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordToggle.innerHTML = eyeSlashSVG;
      } else {
        passwordInput.type = 'password';
        passwordToggle.innerHTML = eyeSVG;
      }
    });
  }

  // ========== OTP Modal ==========
  let otpModal = document.getElementById('otpModal');
  if (!otpModal) {
    otpModal = document.createElement('div');
    otpModal.id = 'otpModal';
    otpModal.style.display = 'none';
    otpModal.innerHTML = `
      <div class="otp-modal-content">
        <div class="otp-header">
          <div class="otp-icon">🔐</div>
          <h2 class="otp-title">Verify Your Identity</h2>
          <p class="otp-subtitle">We've sent a 6-digit code to <strong id="otpEmailDisplay"></strong></p>
        </div>
        <div class="otp-inputs-container">
          <input type="text" maxlength="1" class="otp-input" data-index="0">
          <input type="text" maxlength="1" class="otp-input" data-index="1">
          <input type="text" maxlength="1" class="otp-input" data-index="2">
          <input type="text" maxlength="1" class="otp-input" data-index="3">
          <input type="text" maxlength="1" class="otp-input" data-index="4">
          <input type="text" maxlength="1" class="otp-input" data-index="5">
        </div>
        <div class="otp-error" id="otpError"></div>
        <div class="otp-loading" id="otpLoading">Verifying...</div>
        <div class="otp-buttons">
          <button type="button" class="otp-btn otp-btn-cancel" id="otpCancelBtn">Cancel</button>
          <button type="button" class="otp-btn otp-btn-verify" id="otpVerifyBtn">Verify</button>
        </div>
        <div class="otp-resend">
          Didn't receive code? <a class="otp-resend-link" id="otpResendLink">Resend</a>
        </div>
      </div>
    `;
    document.body.appendChild(otpModal);

    // OTP Input handling
    const otpInputs = otpModal.querySelectorAll('.otp-input');
    otpInputs.forEach((input, index) => {
      input.addEventListener('input', function(e) {
        const value = e.target.value;
        if (value && index < otpInputs.length - 1) {
          otpInputs[index + 1].focus();
        }
      });
      
      input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !e.target.value && index > 0) {
          otpInputs[index - 1].focus();
        }
      });

      input.addEventListener('paste', function(e) {
        e.preventDefault();
        const pasteData = e.clipboardData.getData('text').slice(0, 6);
        pasteData.split('').forEach((char, i) => {
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
    document.getElementById('otpVerifyBtn').addEventListener('click', function() {
      const otpCode = Array.from(otpInputs).map(input => input.value).join('');
      if (otpCode.length !== 6) {
        showOtpError('Please enter all 6 digits');
        return;
      }
      verifyOtp(otpCode);
    });

    // OTP Cancel Button
    document.getElementById('otpCancelBtn').addEventListener('click', function() {
      closeOtpModal();
      loginBtn.disabled = false;
      loginBtn.querySelector('.btn-text').innerText = 'Sign In';
      loadingIcon.style.display = 'none';
    });

    // OTP Resend Link
    document.getElementById('otpResendLink').addEventListener('click', function() {
      generateOtp();
    });
  }

  function showOtpModal() {
    otpModal.style.display = 'flex';
    document.getElementById('otpEmailDisplay').innerText = emailInput.value.trim();
    const otpInputs = otpModal.querySelectorAll('.otp-input');
    otpInputs.forEach(input => input.value = '');
    otpInputs[0].focus();
    document.getElementById('otpError').style.display = 'none';
  }

  function closeOtpModal() {
    otpModal.style.display = 'none';
  }

  function showOtpError(message, success = false) {
    const errorEl = document.getElementById('otpError');
    if (success) {
      errorEl.classList.remove('otp-error');
      if (!errorEl.classList.contains('otp-success')) {
        errorEl.classList.add('otp-success');
      }
    } else {
      errorEl.classList.remove('otp-success');
      if (!errorEl.classList.contains('otp-error')) {
        errorEl.classList.add('otp-error');
      }
    }
    errorEl.innerText = message;
    errorEl.style.display = 'block';
  }

  function generateOtp() {
    document.getElementById('otpLoading').style.display = 'block';
    document.getElementById('otpVerifyBtn').disabled = true;
    
    fetch(`/unihelper/api?controller=otpController&action=generateOtpAction&email=${encodeURIComponent(emailInput.value.trim())}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      }
    })
    .then(response => response.json())
    .then(data => {
      document.getElementById('otpLoading').style.display = 'none';
      document.getElementById('otpVerifyBtn').disabled = false;
      showOtpError(data.message, data.success);
    })
    .catch(error => {
      document.getElementById('otpLoading').style.display = 'none';
      document.getElementById('otpVerifyBtn').disabled = false;
      showOtpError('Failed to send OTP. Please try again.');
    });
  }

  function verifyOtp(otpCode) {
    document.getElementById('otpLoading').style.display = 'block';
    document.getElementById('otpVerifyBtn').disabled = true;
    document.getElementById('otpError').style.display = 'none';

    const formData = new FormData();
    formData.append('otp', otpCode);

    fetch('/unihelper/api?controller=otpController&action=validateOtpAction', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      document.getElementById('otpLoading').style.display = 'none';
      document.getElementById('otpVerifyBtn').disabled = false;
      
      if (data.success) {
        closeOtpModal();
        submitFormWithHashedPassword();
      } else {
        showOtpError(data.message || 'Invalid OTP. Please try again.');
      }
    })
    .catch(error => {
      document.getElementById('otpLoading').style.display = 'none';
      document.getElementById('otpVerifyBtn').disabled = false;
      showOtpError('Verification failed. Please try again.');
    });
  }

  async function submitFormWithHashedPassword() {
    try {
      const pwd = passwordInput.value;
      const hashBuffer = await window.crypto.subtle.digest('SHA-256', new TextEncoder().encode(pwd));
      const hashArray = Array.from(new Uint8Array(hashBuffer));
      const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
      
      let hashedInput = form.querySelector('input[name="hashed_password"]');
      if (!hashedInput) {
        hashedInput = document.createElement('input');
        hashedInput.type = 'hidden';
        hashedInput.name = 'hashed_password';
        form.appendChild(hashedInput);
      }
      hashedInput.value = hashHex;
      passwordInput.value = '';
      
      form.submit();
    } catch (error) {
      loginBtn.disabled = false;
      loginBtn.querySelector('.btn-text').innerText = 'Sign In';
      loadingIcon.style.display = 'none';
      errorBox.innerText = 'An error occurred during submission. Please try again.';
      errorBox.style.display = 'block';
    }
  }

  // ========== Form Submit Handler ==========
  form.addEventListener('submit', async function(e) {
    e.preventDefault();

    let errors = [];
    const email = emailInput.value.trim();
    const password = passwordInput.value;

    // Basic email validation
    if (!email) {
      errors.push('Email is required.');
    } else if (!/^\S+@\S+\.\S+$/.test(email)) {
      errors.push('Please enter a valid email address.');
    }

    // Password validation
    if (!password) {
      errors.push('Password is required.');
    }

    if (errors.length > 0) {
      errorBox.innerText = errors.join(' ');
      errorBox.style.display = 'block';
      errorBox.className = 'auth-error';
      loginBtn.disabled = false;
      loadingIcon.style.display = 'none';
      return false;
    }

    // All good — temporarily bypass OTP flow
    errorBox.style.display = 'none';
    loginBtn.disabled = true;
    loginBtn.querySelector('.btn-text').innerText = 'Signing In...';
    loadingIcon.style.display = 'inline-block';

    submitFormWithHashedPassword();
    // generateOtp();
    // showOtpModal();
  });

  // Hide error on input
  [emailInput, passwordInput].forEach(input => {
    input.addEventListener('input', () => {
      errorBox.style.display = 'none';
      loginBtn.disabled = false;
      loadingIcon.style.display = 'none';
      if (loginBtn.querySelector('.btn-text').innerText === 'Sending OTP...') {
        loginBtn.querySelector('.btn-text').innerText = 'Sign In';
      }
    });
  });
});
