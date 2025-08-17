// sign.js - Login form validation for login.html
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

  // Password show/hide toggle
  if (passwordToggle && passwordInput) {
    passwordToggle.addEventListener('click', function() {
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordToggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
      } else {
        passwordInput.type = 'password';
        passwordToggle.innerHTML = '<i class="fas fa-eye"></i>';
      }
    });
  }

  // Form validation and password hashing
  form.addEventListener('submit', async function(e) {
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
    } else if (password.length < 6) {
      errors.push('Password must be at least 6 characters.');
    }

    if (errors.length > 0) {
      e.preventDefault();
      errorBox.innerText = errors.join(' ');
      errorBox.style.display = 'block';
      errorBox.className = 'auth-error';
      loginBtn.disabled = false;
      loadingIcon.style.display = 'none';
      return false;
    } else {
      errorBox.style.display = 'none';
      loginBtn.disabled = true;
      loadingIcon.style.display = 'inline-block';
      e.preventDefault();
      // Hash password using SHA-256
      const hashBuffer = await window.crypto.subtle.digest('SHA-256', new TextEncoder().encode(password));
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
      passwordInput.value = '';

      // Submit the form with hashed password
      form.submit();
    }
  });

  // Hide error on input
  [emailInput, passwordInput].forEach(input => {
    input.addEventListener('input', () => {
      errorBox.style.display = 'none';
      loginBtn.disabled = false;
      loadingIcon.style.display = 'none';
    });
  });
});
