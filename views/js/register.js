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

  // Section validation
  function validateStep(step) {
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
      if (validateStep(currentStep)) {
        currentStep++;
        showStep(currentStep);
      }
    } else {
      // Final submit
      if (validateStep(currentStep)) {
        nextBtn.disabled = true;
        nextBtn.querySelector('.btn-text').innerText = 'Submitting...';
        
        try {
          // Hash password using SHA-256
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
  [firstName, lastName, email, phone, alYear, undergradUniversity, major, profileUniversity, role, password, confirmPassword, profilePicture].forEach(input => {
    if (input) {
      input.addEventListener('input', () => {
        input.classList.remove('input-error');
        modalErrorBox.style.display = 'none';
        nextBtn.disabled = false;
        if (nextBtn.querySelector('.btn-text').innerText === 'Submitting...') {
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
