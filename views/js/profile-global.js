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
