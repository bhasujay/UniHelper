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

  // Error message element
  let errorBox = document.getElementById('registerError');
  if (!errorBox) {
    errorBox = document.createElement('div');
    errorBox.id = 'registerError';
    errorBox.style.display = 'none';
    errorBox.className = 'auth-error';
    form.insertBefore(errorBox, form.firstChild);
  }

  // Password show/hide toggle
  if (passwordToggle && password) {
    passwordToggle.addEventListener('click', function() {
      if (password.type === 'password') {
        password.type = 'text';
        passwordToggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
      } else {
        password.type = 'password';
        passwordToggle.innerHTML = '<i class="fas fa-eye"></i>';
      }
    });
  }
  if (confirmPasswordToggle && confirmPassword) {
    confirmPasswordToggle.addEventListener('click', function() {
      if (confirmPassword.type === 'password') {
        confirmPassword.type = 'text';
        confirmPasswordToggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
      } else {
        confirmPassword.type = 'password';
        confirmPasswordToggle.innerHTML = '<i class="fas fa-eye"></i>';
      }
    });
  }

  // Form validation and password hashing
  form.addEventListener('submit', async function(e) {
    let errors = [];
    // First name
    if (!firstName.value.trim()) {
      errors.push('First name is required.');
    }
    // Last name
    if (!lastName.value.trim()) {
      errors.push('Last name is required.');
    }
    // Email
    if (!email.value.trim()) {
      errors.push('Email is required.');
    } else if (!/^\S+@\S+\.\S+$/.test(email.value.trim())) {
      errors.push('Please enter a valid email address.');
    }
    // Phone (optional, but if present, validate)
    if (phone.value.trim() && !/^\+?\d{10,15}$/.test(phone.value.trim())) {
      errors.push('Please enter a valid phone number.');
    }
    // A/L Year
    if (!alYear.value) {
      errors.push('Please select your GCE A/L year.');
    }
    // Password
    const pwd = password.value;
    if (!pwd) {
      errors.push('Password is required.');
    } else {
      if (pwd.length < 8) {
        errors.push('Password must be at least 8 characters.');
      }
      if (!/[0-9]/.test(pwd)) {
        errors.push('Password must contain at least one number.');
      }
      if (!/[!@#$%^&*(),.?":{}|<>]/.test(pwd)) {
        errors.push('Password must contain at least one special character.');
      }
    }
    // Confirm password
    if (confirmPassword.value !== pwd) {
      errors.push('Passwords do not match.');
    }
    // Profile picture (optional, check file type if present)
    if (profilePicture.files.length > 0) {
      const file = profilePicture.files[0];
      if (!file.type.startsWith('image/')) {
        errors.push('Profile picture must be an image file.');
      }
    }
    if (errors.length > 0) {
      e.preventDefault();
      errorBox.innerText = errors.join(' ');
      errorBox.style.display = 'block';
      errorBox.className = 'auth-error';
      registerBtn.disabled = false;
      loadingIcon.style.display = 'none';
      return false;
    } else {
      errorBox.style.display = 'none';
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

  // Hide error on input
  [firstName, lastName, email, phone, alYear, password, confirmPassword].forEach(input => {
    input.addEventListener('input', () => {
      errorBox.style.display = 'none';
      registerBtn.disabled = false;
      loadingIcon.style.display = 'none';
    });
  });
});
