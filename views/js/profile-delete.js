document.addEventListener('DOMContentLoaded', function () {
  const openBtn = document.getElementById('openDeleteAccountBtn');
  const confirmModal = document.getElementById('deleteAccountConfirmModal');
  const passwordModal = document.getElementById('deleteAccountPasswordModal');
  const otpModal = document.getElementById('deleteAccountOtpModal');

  if (!openBtn || !confirmModal || !passwordModal || !otpModal) {
    return;
  }

  [confirmModal, passwordModal, otpModal].forEach(function (modal) {
    if (modal && modal.parentElement !== document.body) {
      document.body.appendChild(modal);
    }
  });

  const userId = openBtn.getAttribute('data-user-id');
  const userEmailEl = document.getElementById('deleteOtpEmailDisplay');
  const userEmail = userEmailEl ? userEmailEl.innerText : '';

  const confirmCancelBtn = document.getElementById('deleteConfirmCancelBtn');
  const confirmContinueBtn = document.getElementById('deleteConfirmContinueBtn');

  const passwordInput = document.getElementById('deleteAccountPassword');
  const passwordCancelBtn = document.getElementById('deletePasswordCancelBtn');
  const passwordContinueBtn = document.getElementById('deletePasswordContinueBtn');
  const passwordError = document.getElementById('deletePasswordError');
  const passwordLoading = document.getElementById('deletePasswordLoading');

  const otpInputs = otpModal.querySelectorAll('.cp-otp-digit');
  const otpError = document.getElementById('deleteOtpError');
  const otpLoading = document.getElementById('deleteOtpLoading');
  const otpVerifyBtn = document.getElementById('deleteOtpVerifyBtn');
  const otpCancelBtn = document.getElementById('deleteOtpCancelBtn');
  const otpResendLink = document.getElementById('deleteOtpResendLink');

  function show(modal) {
    modal.style.display = 'flex';
  }

  function hide(modal) {
    modal.style.display = 'none';
  }

  function setPasswordMessage(message) {
    if (!passwordError) {
      return;
    }
    passwordError.textContent = message || '';
    passwordError.style.display = message ? 'block' : 'none';
  }

  function setPasswordLoading(message, visible) {
    if (!passwordLoading) {
      return;
    }
    passwordLoading.textContent = message || '';
    passwordLoading.style.display = visible ? 'block' : 'none';
  }

  function setOtpMessage(message, success) {
    if (!otpError) {
      return;
    }
    otpError.className = success
      ? 'cp-otp-message cp-otp-success-msg'
      : 'cp-otp-message cp-otp-error-msg';
    otpError.textContent = message || '';
    otpError.style.display = message ? 'block' : 'none';
  }

  function setOtpLoading(message, visible) {
    if (!otpLoading) {
      return;
    }
    otpLoading.textContent = message || '';
    otpLoading.style.display = visible ? 'block' : 'none';
  }

  function openConfirm() {
    show(confirmModal);
  }

  function openPassword() {
    show(passwordModal);
    if (passwordInput) {
      passwordInput.value = '';
      passwordInput.focus();
    }
    setPasswordMessage('');
    setPasswordLoading('', false);
    if (passwordContinueBtn) {
      passwordContinueBtn.disabled = false;
    }
  }

  function openOtp() {
    show(otpModal);
    otpInputs.forEach(function (input) { input.value = ''; });
    if (otpInputs.length > 0) {
      otpInputs[0].focus();
    }
    setOtpMessage('', false);
    setOtpLoading('', false);
    if (otpVerifyBtn) {
      otpVerifyBtn.disabled = false;
    }
  }

  function closeConfirm() {
    hide(confirmModal);
  }

  function closePassword() {
    hide(passwordModal);
  }

  function closeOtp() {
    hide(otpModal);
  }

  async function verifyPassword() {
    if (!passwordInput || !passwordContinueBtn) {
      return;
    }

    if (!passwordInput.value) {
      setPasswordMessage('Please enter your password.');
      passwordInput.focus();
      return;
    }

    setPasswordMessage('');
    setPasswordLoading('Verifying password...', true);
    passwordContinueBtn.disabled = true;

    try {
      const hash = await sha256(passwordInput.value);
      const formData = new FormData();
      formData.append('hashed_password', hash);

      const response = await fetch('/unihelper/api?controller=AuthController&action=verifyCurrentPasswordAction', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      });

      const data = await response.json();
      if (!data.success) {
        setPasswordMessage(data.message || 'Password is incorrect.');
        passwordContinueBtn.disabled = false;
        setPasswordLoading('', false);
        return;
      }

      setPasswordLoading('Sending OTP...', true);
      sendOtp(true);
    } catch (error) {
      setPasswordMessage('Unable to verify password. Please try again.');
      passwordContinueBtn.disabled = false;
      setPasswordLoading('', false);
    }
  }

  function sendOtp(fromPasswordStep) {
    if (!userEmail) {
      setPasswordMessage('Missing email for OTP delivery.');
      if (passwordContinueBtn) {
        passwordContinueBtn.disabled = false;
      }
      setPasswordLoading('', false);
      return;
    }

    setOtpLoading('Sending OTP...', true);

    fetch('/unihelper/api?controller=otpController&action=generateOtpAction&email=' + encodeURIComponent(userEmail), {
      method: 'GET',
      headers: { 'Content-Type': 'application/json' }
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        setOtpLoading('', false);
        if (data.success) {
          if (fromPasswordStep) {
            closePassword();
            openOtp();
          }
          setOtpMessage(data.message || 'OTP sent successfully.', true);
        } else {
          if (fromPasswordStep) {
            setPasswordMessage(data.message || 'Failed to send OTP.');
            if (passwordContinueBtn) {
              passwordContinueBtn.disabled = false;
            }
            setPasswordLoading('', false);
          } else {
            setOtpMessage(data.message || 'Failed to send OTP.', false);
          }
        }
      })
      .catch(function () {
        setOtpLoading('', false);
        if (fromPasswordStep) {
          setPasswordMessage('Failed to send OTP. Please try again.');
          if (passwordContinueBtn) {
            passwordContinueBtn.disabled = false;
          }
          setPasswordLoading('', false);
        } else {
          setOtpMessage('Failed to send OTP. Please try again.', false);
        }
      });
  }

  function verifyOtp(otpCode) {
    setOtpMessage('', false);
    setOtpLoading('Verifying...', true);
    if (otpVerifyBtn) {
      otpVerifyBtn.disabled = true;
    }

    const formData = new FormData();
    formData.append('otp', otpCode);

    fetch('/unihelper/api?controller=otpController&action=validateOtpAction', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        setOtpLoading('', false);
        if (otpVerifyBtn) {
          otpVerifyBtn.disabled = false;
        }
        if (data.success) {
          closeOtp();
          deleteAccount();
        } else {
          setOtpMessage(data.message || 'Invalid OTP. Please try again.', false);
        }
      })
      .catch(function () {
        setOtpLoading('', false);
        if (otpVerifyBtn) {
          otpVerifyBtn.disabled = false;
        }
        setOtpMessage('Verification failed. Please try again.', false);
      });
  }

  function deleteAccount() {
    if (!userId) {
      return;
    }

    const formData = new FormData();
    formData.append('user_id', userId);

    fetch('/unihelper/api?controller=userManagementController&action=deleteUser', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.success) {
          window.location.href = '/UniHelper/home';
        } else {
          setOtpMessage(data.message || 'Failed to delete account.', false);
        }
      })
      .catch(function () {
        setOtpMessage('Failed to delete account. Please try again.', false);
      });
  }

  function bindOverlayClose(modal, closeFn) {
    modal.addEventListener('click', function (event) {
      if (event.target === modal) {
        closeFn();
      }
    });
  }

  openBtn.addEventListener('click', openConfirm);
  confirmCancelBtn && confirmCancelBtn.addEventListener('click', closeConfirm);
  confirmContinueBtn && confirmContinueBtn.addEventListener('click', function () {
    closeConfirm();
    openPassword();
  });

  passwordCancelBtn && passwordCancelBtn.addEventListener('click', closePassword);
  passwordContinueBtn && passwordContinueBtn.addEventListener('click', verifyPassword);

  const toggleBtn = passwordModal.querySelector('.cp-toggle-btn');
  if (toggleBtn && passwordInput) {
    toggleBtn.addEventListener('click', function () {
      const eyeIcon = toggleBtn.querySelector('.eye-icon');
      const eyeSlashIcon = toggleBtn.querySelector('.eye-slash-icon');
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        if (eyeIcon) { eyeIcon.style.display = 'none'; }
        if (eyeSlashIcon) { eyeSlashIcon.style.display = 'inline'; }
      } else {
        passwordInput.type = 'password';
        if (eyeIcon) { eyeIcon.style.display = 'inline'; }
        if (eyeSlashIcon) { eyeSlashIcon.style.display = 'none'; }
      }
    });
  }

  otpInputs.forEach(function (input, index) {
    input.addEventListener('input', function (event) {
      if (event.target.value && index < otpInputs.length - 1) {
        otpInputs[index + 1].focus();
      }
    });

    input.addEventListener('keydown', function (event) {
      if (event.key === 'Backspace' && !event.target.value && index > 0) {
        otpInputs[index - 1].focus();
      }
    });

    input.addEventListener('paste', function (event) {
      event.preventDefault();
      var pasteData = event.clipboardData.getData('text').slice(0, 6);
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

  otpVerifyBtn && otpVerifyBtn.addEventListener('click', function () {
    var otpCode = Array.from(otpInputs).map(function (input) { return input.value; }).join('');
    if (otpCode.length !== 6) {
      setOtpMessage('Please enter all 6 digits.', false);
      return;
    }
    verifyOtp(otpCode);
  });

  otpCancelBtn && otpCancelBtn.addEventListener('click', closeOtp);
  otpResendLink && otpResendLink.addEventListener('click', function () {
    sendOtp(false);
  });

  bindOverlayClose(confirmModal, closeConfirm);
  bindOverlayClose(passwordModal, closePassword);
  bindOverlayClose(otpModal, closeOtp);

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') {
      return;
    }
    if (otpModal.style.display === 'flex') {
      closeOtp();
    } else if (passwordModal.style.display === 'flex') {
      closePassword();
    } else if (confirmModal.style.display === 'flex') {
      closeConfirm();
    }
  });

  async function sha256(message) {
    var buffer = await window.crypto.subtle.digest('SHA-256', new TextEncoder().encode(message));
    var hashArray = Array.from(new Uint8Array(buffer));
    return hashArray.map(function (b) { return b.toString(16).padStart(2, '0'); }).join('');
  }
});
