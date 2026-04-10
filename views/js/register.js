// register.js - Registration form validation for register.php

document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('registerForm');
  const firstName = document.getElementById('firstName');
  const lastName = document.getElementById('lastName');
  const email = document.getElementById('email');
  const phone = document.getElementById('phone');
  const alYear = document.getElementById('alYear');
  const undergradUniversity = document.getElementById('undergradUniversity');
  const major = document.getElementById('major');
  const profileUniversity = document.getElementById('profileUniversity');
  const role = document.getElementById('role');
  const password = document.getElementById('password');
  const confirmPassword = document.getElementById('confirmPassword');
  const profilePicture = document.getElementById('profilePicture');
  const passwordToggle = document.getElementById('passwordToggle');
  const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');

  // Role switch elements
  const roleSlider = document.querySelector('.role-switch-slider');
  const roleLabels = document.querySelectorAll('.role-switch-label');
  const roleRadios = document.querySelectorAll('input[name="userRole"]');
  const roleSpecificSections = document.querySelectorAll('.role-specific');

  // Inline SVG icons for eye and eye-slash
  const eyeSVG = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#8e8e8eff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="7" ry="5"/><circle cx="12" cy="12" r="2"/></svg>';
  const eyeSlashSVG = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#8e8e8eff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="7" ry="5"/><circle cx="12" cy="12" r="2"/><line x1="5" y1="5" x2="19" y2="19"/></svg>';

  // Modal error box
  let modalErrorBox = document.getElementById('modalErrorBox');
  if (!modalErrorBox) {
    modalErrorBox = document.createElement('div');
    modalErrorBox.id = 'modalErrorBox';
    modalErrorBox.style.display = 'none';
    modalErrorBox.className = 'modal-error-box';
    modalErrorBox.innerHTML = '<div class="modal-error-content"><span id="modalErrorMsg"></span><br><button id="modalErrorClose" class="modal-error-close">OK</button></div>';
    document.body.appendChild(modalErrorBox);
    document.getElementById('modalErrorClose').onclick = function() {
      modalErrorBox.style.display = 'none';
    };
  }

  // OTP Modal
  let otpModal = document.getElementById('otpModal');
  if (!otpModal) {
    otpModal = document.createElement('div');
    otpModal.id = 'otpModal';
    otpModal.style.display = 'none';
    otpModal.innerHTML = `
      <div class="otp-modal-content">
        <div class="otp-header">
          <div class="otp-icon">📧</div>
          <h2 class="otp-title">Verify Your Email</h2>
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
      nextBtn.disabled = false;
      nextBtn.querySelector('.btn-text').innerText = 'Submit';
    });

    // OTP Resend Link
    document.getElementById('otpResendLink').addEventListener('click', function() {
      generateOtp();
    });
  }

  function showOtpModal() {
    otpModal.style.display = 'flex';
    document.getElementById('otpEmailDisplay').innerText = email.value;
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
    
    fetch(`/unihelper/api?controller=otpController&action=generateOtpAction&email=${encodeURIComponent(email.value)}`, {
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
      const pwd = password.value;
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
      
      password.value = '';
      confirmPassword.value = '';
      form.submit();
    } catch (error) {
      nextBtn.disabled = false;
      nextBtn.querySelector('.btn-text').innerText = 'Submit';
      document.getElementById('modalErrorMsg').innerHTML = 'An error occurred during submission. Please try again.';
      modalErrorBox.style.display = 'flex';
    }
  }

  // Role switch functionality
  function updateRoleSwitch(selectedRole) {
    let sliderPosition = 0;
    
    // Calculate slider position based on selected role
    if (selectedRole === 'role-applicant') {
      sliderPosition = 0;
    } else if (selectedRole === 'role-undergrad') {
      sliderPosition = 33.33;
    } else if (selectedRole === 'role-profile') {
      sliderPosition = 66.66;
    }
    
    // Move the slider
    roleSlider.style.left = sliderPosition + '%';
    
    // Update radio button selection
    roleRadios.forEach(radio => {
      radio.checked = radio.value === selectedRole;
    });
    
    // Explicitly handle each role type's sections
    const applicantSections = document.querySelectorAll('.role-applicant');
    const undergradSections = document.querySelectorAll('.role-undergrad');
    const profileSections = document.querySelectorAll('.role-profile');
    
    // First, hide all sections and remove required attributes
    [applicantSections, undergradSections, profileSections].forEach(sections => {
      sections.forEach(section => {
        section.style.display = 'none';
        const inputs = section.querySelectorAll('input, select');
        inputs.forEach(input => {
          input.required = false;
          // Don't clear values here to preserve user input
        });
      });
    });
    
    // Then, show the selected role's sections and make fields required
    if (selectedRole === 'role-applicant') {
      applicantSections.forEach(section => {
        section.style.display = 'block';
        const inputs = section.querySelectorAll('input, select');
        inputs.forEach(input => input.required = true);
      });
    } else if (selectedRole === 'role-undergrad') {
      undergradSections.forEach(section => {
        section.style.display = 'block';
        const inputs = section.querySelectorAll('input, select');
        inputs.forEach(input => input.required = true);
      });
    } else if (selectedRole === 'role-profile') {
      profileSections.forEach(section => {
        section.style.display = 'block';
        const inputs = section.querySelectorAll('input, select');
        inputs.forEach(input => input.required = true);
      });
    }
  }

  // Add click event listeners to role labels - FIXED
  roleLabels.forEach((label) => {
    label.addEventListener('click', function() {
      // Get the role value from the 'for' attribute which matches the radio button ID
      const radioId = label.getAttribute('for');
      const radioButton = document.getElementById(radioId);
      if (radioButton) {
        const roleValue = radioButton.value;
        updateRoleSwitch(roleValue);
      }
    });
  });

  // Also add event listeners to the radio buttons themselves
  roleRadios.forEach(radio => {
    radio.addEventListener('change', function() {
      if (this.checked) {
        updateRoleSwitch(this.value);
      }
    });
  });

  // Initialize role switch - make sure the first radio is checked
  document.getElementById('role-applicant').checked = true;
  updateRoleSwitch('role-applicant');

  // Password show/hide toggle
  if (passwordToggle && password) {
    passwordToggle.innerHTML = eyeSVG;
    passwordToggle.addEventListener('click', function() {
      if (password.type === 'password') {
        password.type = 'text';
        passwordToggle.innerHTML = eyeSlashSVG;
      } else {
        password.type = 'password';
        passwordToggle.innerHTML = eyeSVG;
      }
    });
  }
  if (confirmPasswordToggle && confirmPassword) {
    confirmPasswordToggle.innerHTML = eyeSVG;
    confirmPasswordToggle.addEventListener('click', function() {
      if (confirmPassword.type === 'password') {
        confirmPassword.type = 'text';
        confirmPasswordToggle.innerHTML = eyeSlashSVG;
      } else {
        confirmPassword.type = 'password';
        confirmPasswordToggle.innerHTML = eyeSVG;
      }
    });
  }

  // Multi-step logic
  const sections = [
    document.getElementById('section1'),
    document.getElementById('section2'),
    document.getElementById('section3')
  ];
  let currentStep = 0;
  const nextBtn = document.getElementById('nextBtn');
  const prevBtn = document.getElementById('prevBtn');

  function showStep(step) {
    sections.forEach((sec, idx) => sec.style.display = idx === step ? 'block' : 'none');
    
    // Button visibility logic
    if (step === 0) {
      prevBtn.style.display = 'none';
      nextBtn.style.display = 'block';
      nextBtn.querySelector('.btn-text').innerText = 'Next';
      nextBtn.type = 'button';
    } else if (step === 1) {
      prevBtn.style.display = 'block';
      nextBtn.style.display = 'block';
      nextBtn.querySelector('.btn-text').innerText = 'Next';
      nextBtn.type = 'button';
    } else if (step === 2) {
      prevBtn.style.display = 'block';
      nextBtn.style.display = 'block';
      nextBtn.querySelector('.btn-text').innerText = 'Submit';
      nextBtn.type = 'button';
    }
  }

  // ========== Toast Notification System ==========
  let toastContainer = document.getElementById('toastContainer');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'toastContainer';
    toastContainer.className = 'toast-container';
    document.body.appendChild(toastContainer);
  }

  function showToast(message, type = 'error', duration = 5000) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    const typeText = type === 'success' ? 'Success' : 'Error';
    toast.innerHTML = `
      <div>
        <div class="toast-type">${typeText}</div>
        <div class="toast-message">${message}</div>
      </div>
      <button class="toast-close" aria-label="Close">&times;</button>
    `;
    toastContainer.appendChild(toast);
    toast.querySelector('.toast-close').addEventListener('click', () => removeToast(toast));
    setTimeout(() => removeToast(toast), duration);
  }

  function removeToast(toast) {
    if (!toast.parentNode) return;
    toast.classList.add('hiding');
    setTimeout(() => toast.remove(), 300);
  }

  // ========== Uniqueness Flags ==========
  let emailExists = false;
  let phoneExists = false;

  async function checkFieldExists(field, value) {
    try {
      const response = await fetch(
        `/unihelper/api?controller=AuthController&action=checkExistsAction&field=${encodeURIComponent(field)}&value=${encodeURIComponent(value)}`
      );
      return await response.json();
    } catch (err) {
      return { exists: false };
    }
  }

  // Check email on blur
  email.addEventListener('blur', async function() {
    const val = email.value.trim();
    if (!val || !/^\S+@\S+\.\S+$/.test(val)) return; // skip if empty or invalid format
    const result = await checkFieldExists('email', val);
    emailExists = result.exists;
    if (result.exists) {
      showToast(result.message || 'This email is already registered.');
      email.classList.add('input-error');
      email.classList.add('shake');
      setTimeout(() => email.classList.remove('shake'), 500);
    }
  });

  // Check phone on blur
  phone.addEventListener('blur', async function() {
    const val = phone.value.trim();
    if (!val || !/^\d{10}$/.test(val)) return; // skip if empty or invalid format
    const result = await checkFieldExists('phone', val);
    phoneExists = result.exists;
    if (result.exists) {
      showToast(result.message || 'This phone number is already registered.');
      phone.classList.add('input-error');
      phone.classList.add('shake');
      setTimeout(() => phone.classList.remove('shake'), 500);
    }
  });

  // Clear uniqueness flags when user edits the field
  email.addEventListener('input', function() {
    emailExists = false;
    email.classList.remove('input-error');
  });

  phone.addEventListener('input', function() {
    phoneExists = false;
    phone.classList.remove('input-error');
  });

  // Section validation (async for step 0 uniqueness re-check)
  async function validateStep(step) {
    let errors = [];
    let errorFields = [];
    
    if (step === 0) {
      // Validate section 1
      if (!firstName.value.trim()) { errors.push('First name is required.'); errorFields.push(firstName);}
      if (!lastName.value.trim()) { errors.push('Last name is required.'); errorFields.push(lastName);}
      if (!email.value.trim()) { errors.push('Email is required.'); errorFields.push(email);}
      else if (!/^\S+@\S+\.\S+$/.test(email.value.trim())) { errors.push('Please enter a valid email address.'); errorFields.push(email);}
      if (!phone.value.trim()) { errors.push('Phone number is required.'); errorFields.push(phone);}
      else if (!/^\d{10}$/.test(phone.value.trim())) { errors.push('Phone number must be exactly 10 digits.'); errorFields.push(phone);}

      // If format validations passed, do a final uniqueness check before proceeding
      if (errors.length === 0) {
        nextBtn.disabled = true;
        nextBtn.querySelector('.btn-text').innerText = 'Checking...';

        const [emailCheck, phoneCheck] = await Promise.all([
          checkFieldExists('email', email.value.trim()),
          checkFieldExists('phone', phone.value.trim())
        ]);

        nextBtn.disabled = false;
        nextBtn.querySelector('.btn-text').innerText = currentStep === sections.length - 1 ? 'Submit' : 'Next';

        emailExists = emailCheck.exists;
        phoneExists = phoneCheck.exists;

        if (emailCheck.exists) {
          showToast(emailCheck.message || 'This email is already registered.');
          errorFields.push(email);
        }
        if (phoneCheck.exists) {
          showToast(phoneCheck.message || 'This phone number is already registered.');
          errorFields.push(phone);
        }

        // If any uniqueness errors, highlight and block
        if (errorFields.length > 0) {
          errorFields.forEach(field => {
            field.classList.add('input-error');
            field.classList.add('shake');
            setTimeout(() => field.classList.remove('shake'), 500);
          });
          errorFields[0].focus();
          return false;
        }
      }
    }
    
    if (step === 1) {
      // Validate section 2 based on selected role
      const selectedRoleElement = document.querySelector('input[name="userRole"]:checked');
      if (!selectedRoleElement) {
        errors.push('Please select a role.');
        return false;
      }
      
      const selectedRole = selectedRoleElement.value;
      
      if (selectedRole === 'role-applicant') {
        if (!alYear.value) { errors.push('Please select your GCE A/L year.'); errorFields.push(alYear);}
      } else if (selectedRole === 'role-undergrad') {
        if (!undergradUniversity.value) { errors.push('Please select your university.'); errorFields.push(undergradUniversity);}
        if (!major.value) { errors.push('Please select your major.'); errorFields.push(major);}
      } else if (selectedRole === 'role-profile') {
        if (!profileUniversity.value) { errors.push('Please select your university.'); errorFields.push(profileUniversity);}
        if (!role.value.trim()) { errors.push('Role is required.'); errorFields.push(role);}
      }
    }
    
    if (step === 2) {
      // Validate section 3 (password, confirm, etc)
      const pwd = password.value;
      if (!pwd) { errors.push('Password is required.'); errorFields.push(password);}
      else {
        if (pwd.length < 8) { errors.push('Password must be at least 8 characters.'); errorFields.push(password);}
        if (!/[0-9]/.test(pwd)) { errors.push('Password must contain at least one number.'); errorFields.push(password);}
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(pwd)) { errors.push('Password must contain at least one special character.'); errorFields.push(password);}
      }
      if (!confirmPassword.value) { errors.push('Please confirm your password.'); errorFields.push(confirmPassword);}
      else if (confirmPassword.value !== pwd) { errors.push('Passwords do not match.'); errorFields.push(confirmPassword);}
      
      if (profilePicture.files.length > 0) {
        const file = profilePicture.files[0];
        if (!file.type.startsWith('image/')) { errors.push('Profile picture must be an image file.'); errorFields.push(profilePicture);}
      }
    }
    
    if (errors.length > 0) {
      document.getElementById('modalErrorMsg').innerHTML = errors.join('<br>');
      modalErrorBox.style.display = 'flex';
      errorFields.forEach(field => {
        field.classList.add('input-error');
        field.classList.add('shake');
        setTimeout(() => field.classList.remove('shake'), 500);
      });
      if (errorFields.length > 0) errorFields[0].focus();
      return false;
    }
    return true;
  }

  // Next button click event
  nextBtn.addEventListener('click', async function(e) {
    e.preventDefault();
    
    if (currentStep < sections.length - 1) {
      if (await validateStep(currentStep)) {
        currentStep++;
        showStep(currentStep);
      }
    } else {
      // Final submit
      if (await validateStep(currentStep)) {
        nextBtn.disabled = true;
        nextBtn.querySelector('.btn-text').innerText = 'Sending OTP...';
        
        generateOtp();
        showOtpModal();
      }
    }
  });

  // Previous button click event
  prevBtn.addEventListener('click', function() {
    if (currentStep > 0) {
      currentStep--;
      showStep(currentStep);
    }
  });

  // Remove error highlight and hide modal on input
  [firstName, lastName, alYear, undergradUniversity, major, profileUniversity, role, password, confirmPassword, profilePicture].forEach(input => {
    if (input) {
      input.addEventListener('input', () => {
        input.classList.remove('input-error');
        modalErrorBox.style.display = 'none';
        nextBtn.disabled = false;
        if (nextBtn.querySelector('.btn-text').innerText === 'Submitting...' || nextBtn.querySelector('.btn-text').innerText === 'Sending OTP...' || nextBtn.querySelector('.btn-text').innerText === 'Checking...') {
          nextBtn.querySelector('.btn-text').innerText = currentStep === sections.length - 1 ? 'Submit' : 'Next';
        }
      });
      if (input.type === 'file') {
        input.addEventListener('change', () => {
          input.classList.remove('input-error');
          modalErrorBox.style.display = 'none';
        });
      }
    }
  });

  // Initial setup
  showStep(currentStep);
});
