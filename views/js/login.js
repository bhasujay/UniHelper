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

  // Inline SVG icons for eye and eye-slash
  // Simple standard eye and eye-slash SVGs
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
