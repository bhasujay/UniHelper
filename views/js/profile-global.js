async function handleConnection(action) {
    const container = document.getElementById('globalProfileContainer');
    if (!container) return;

    const targetId = container.getAttribute('data-target-id');
    if (!targetId) return;

    let actionEndpoint = '';
    switch (action) {
        case 'request':
            actionEndpoint = 'requestConnection';
            break;
        case 'accept':
            actionEndpoint = 'acceptConnection';
            break;
        case 'reject':
            actionEndpoint = 'rejectConnection';
            break;
        case 'cancel':
            if (!await confirm('Are you sure you want to cancel this friend request?')) return;
            actionEndpoint = 'cancelConnection';
            break;
        case 'remove':
            if (!await confirm('Are you sure you want to remove this friend?')) return;
            actionEndpoint = 'removeConnection';
            break;
        default:
            return;
    }

    const formData = new FormData();
    formData.append('friend_id', targetId);

    fetch(`/unihelper/api?controller=connectionController&action=${actionEndpoint}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Simplified heavily to rely on SSR rendering properly handling new status
            window.location.reload();
        } else {
            showToast(data.message || 'An error occurred.', 'error');
        }
    })
    .catch(error => {
        console.error('Connection action error:', error);
        showToast('Failed to process request.', 'error');
    });
}

function showToast(message, type = 'info') {
    if (typeof window.showToastNotification === 'function') {
        window.showToastNotification(message, type);
        return;
    }
    const toast = document.createElement('div');
    toast.style.position = 'fixed';
    toast.style.bottom = '20px';
    toast.style.right = '20px';
    toast.style.padding = '1rem 2rem';
    toast.style.background = type === 'error' ? '#ef4444' : '#10b981';
    toast.style.color = 'white';
    toast.style.borderRadius = '8px';
    toast.style.zIndex = '9999';
    toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    toast.innerText = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.remove(); }, 3000);
}

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('globalProfileContainer');
    const modal = document.getElementById('profileReportModal');
    const openBtn = document.getElementById('openProfileReportBtn');
    const closeBtn = document.getElementById('closeProfileReportBtn');
    const cancelBtn = document.getElementById('cancelProfileReportBtn');
    const form = document.getElementById('profileReportForm');
    const submitBtn = document.getElementById('submitProfileReportBtn');
    const detailsGroup = document.getElementById('profileReportDetailsGroup');
    const detailsField = document.getElementById('profileReportDetails');

    if (!container || !modal || !form || !openBtn) {
        return;
    }

    // Keep modal at body level to avoid stacking/clipping issues.
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    const targetId = container.getAttribute('data-target-id');

    const openModal = () => {
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = () => {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        form.reset();
        if (detailsGroup) {
            detailsGroup.style.display = 'none';
        }
    };

    openBtn.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.style.display === 'flex') {
            closeModal();
        }
    });

    form.querySelectorAll('input[name="profile_report_reason"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            if (!detailsGroup) {
                return;
            }
            detailsGroup.style.display = radio.value === 'other' && radio.checked ? 'flex' : 'none';
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const reasonEl = form.querySelector('input[name="profile_report_reason"]:checked');
        if (!reasonEl) {
            showToast('Please select a reason.', 'error');
            return;
        }

        const reason = String(reasonEl.value || '').trim();
        const details = String(detailsField?.value || '').trim();

        if (reason === 'other' && details.length === 0) {
            showToast('Please provide details for "Other".', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('reported_user_id', String(targetId || ''));
        formData.append('reason', reason);
        if (details.length > 0) {
            formData.append('details', details);
        }

        const originalText = submitBtn ? submitBtn.textContent : 'Submit Report';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
        }

        try {
            const response = await fetch('/unihelper/api?controller=userManagementController&action=submitProfileReport', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Failed to submit report.');
            }

            showToast(payload.message || 'Report submitted successfully.', 'success');
            closeModal();
        } catch (error) {
            showToast(error.message || 'Failed to submit report.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }
    });
});
