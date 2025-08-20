// register.js - Registration form validation for register.html

document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('registerForm');
  const firstName = document.getElementById('firstName');
  const lastName = document.getElementById('lastName');
  const email = document.getElementById('email');
  const phone = document.getElementById('phone');
  const alYear = document.getElementById('alYear');
  const password = document.getElementById('password');
  const confirmPassword = document.getElementById('confirmPassword');
  const profilePicture = document.getElementById('profilePicture');
  const registerBtn = document.getElementById('registerBtn');
  const loadingIcon = document.querySelector('.btn-loading-icon');
  const passwordToggle = document.getElementById('passwordToggle');
  const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');

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
    modalErrorBox.innerHTML = '<div class="modal-error-content"><span id="modalErrorMsg"></span><button id="modalErrorClose" class="modal-error-close">OK</button></div>';
    document.body.appendChild(modalErrorBox);
    document.getElementById('modalErrorClose').onclick = function() {
      modalErrorBox.style.display = 'none';
    };
  }

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

  // Form validation and password hashing
  form.addEventListener('submit', async function(e) {
  let errors = [];
  let errorFields = [];
    // First name
    if (!firstName.value.trim()) {
      errors.push('First name is required.');
      errorFields.push(firstName);
    }
    // Last name
    if (!lastName.value.trim()) {
      errors.push('Last name is required.');
      errorFields.push(lastName);
    }
    // Email
    if (!email.value.trim()) {
      errors.push('Email is required.');
      errorFields.push(email);
    } else if (!/^\S+@\S+\.\S+$/.test(email.value.trim())) {
      errors.push('Please enter a valid email address.');
      errorFields.push(email);
    }
    // Phone (optional, but if present, validate)
    const phoneVal = phone.value.trim();
    if (phoneVal) {
      // Only digits, exactly 10 numbers
      if (!/^\d{10}$/.test(phoneVal)) {
        errors.push('Phone number must be exactly 10 digits and contain only numbers.');
        errorFields.push(phone);
      }
    }
    // A/L Year
    if (!alYear.value) {
      errors.push('Please select your GCE A/L year.');
      errorFields.push(alYear);
    }
    // Password
    const pwd = password.value;
    if (!pwd) {
      errors.push('Password is required.');
      errorFields.push(password);
    } else {
      if (pwd.length < 8) {
        errors.push('Password must be at least 8 characters.');
        errorFields.push(password);
      }
      if (!/[0-9]/.test(pwd)) {
        errors.push('Password must contain at least one number.');
        errorFields.push(password);
      }
      if (!/[!@#$%^&*(),.?":{}|<>]/.test(pwd)) {
        errors.push('Password must contain at least one special character.');
        errorFields.push(password);
      }
    }
    // Confirm password
    if (confirmPassword.value !== pwd) {
      errors.push('Passwords do not match.');
      errorFields.push(confirmPassword);
    }
    // Profile picture (optional, check file type if present)
    if (profilePicture.files.length > 0) {
      const file = profilePicture.files[0];
      if (!file.type.startsWith('image/')) {
        errors.push('Profile picture must be an image file.');
        errorFields.push(profilePicture);
      }
    }
    if (errors.length > 0) {
      e.preventDefault();
      // Show modal error box
      document.getElementById('modalErrorMsg').innerText = errors.join(' ');
      modalErrorBox.style.display = 'flex';
      registerBtn.disabled = false;
      loadingIcon.style.display = 'none';
      // Highlight and shake error fields
      errorFields.forEach(field => {
        field.classList.add('input-error');
        field.classList.add('shake');
        setTimeout(() => field.classList.remove('shake'), 500);
      });
      if (errorFields.length > 0) {
        errorFields[0].focus();
      }
      return false;
    } else {
      modalErrorBox.style.display = 'none';
      registerBtn.disabled = true;
      loadingIcon.style.display = 'inline-block';
      e.preventDefault();
      // Hash password using SHA-256
      const hashBuffer = await window.crypto.subtle.digest('SHA-256', new TextEncoder().encode(pwd));
      const hashArray = Array.from(new Uint8Array(hashBuffer));
      const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
      // Create a hidden input for hashed password
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
      // Submit the form with hashed password
      form.submit();
    }
  });

  // Remove error highlight and hide modal on input
  [firstName, lastName, email, phone, alYear, password, confirmPassword, profilePicture].forEach(input => {
    input.addEventListener('input', () => {
      input.classList.remove('input-error');
      modalErrorBox.style.display = 'none';
      registerBtn.disabled = false;
      loadingIcon.style.display = 'none';
    });
    if (input.type === 'file') {
      input.addEventListener('change', () => {
        input.classList.remove('input-error');
        modalErrorBox.style.display = 'none';
      });
    }
  });


  // Swipe effect for role switch
  const radios = document.querySelectorAll('input[name="userRole"]');
  const slider = document.querySelector('.role-switch-slider');
  radios.forEach((radio, idx) => {
      radio.addEventListener('change', () => {
          slider.style.left = `${idx * 33.33}%`;
      });
  });
  // Initial position
  slider.style.left = "0%";
});
