document.addEventListener('DOMContentLoaded', function() {
    // Create button element (label included but hidden via CSS)
    const btn = document.createElement('button');
    btn.className = 'qa-sticky-btn';
    btn.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span class="qa-sticky-label">Ask a Question</span>';
    
    // Create modal (unchanged)
    const modal = document.createElement('div');
    modal.className = 'qa-modal';
    modal.style.display = 'none';
    modal.innerHTML = `
        <div class="qa-modal-content">
            <button class="qa-modal-close" aria-label="Close">&times;</button>
        </div>
    `;
    
    document.body.appendChild(btn);
    document.body.appendChild(modal);
    
    let isExpanded = false;
    
    // hover: toggle a class — label reveal is handled by CSS transitions
    btn.addEventListener('mouseenter', function() {
        if (!isExpanded) btn.classList.add('hover');
    });
    btn.addEventListener('mouseleave', function() {
        if (!isExpanded) btn.classList.remove('hover');
    });
    
    // click: toggle expanded state and modal visibility
    btn.addEventListener('click', function() {
        isExpanded = !isExpanded;
        if (isExpanded) {
            btn.classList.add('expanded');
            modal.style.display = 'flex';
        } else {
            btn.classList.remove('expanded');
            modal.style.display = 'none';
        }
    });
    
    // close modal button — also collapse the toggle
    modal.querySelector('.qa-modal-close').addEventListener('click', function() {
        modal.style.display = 'none';
        isExpanded = false;
        btn.classList.remove('expanded');
        btn.classList.remove('hover');
    });
    
    // Clean up
    window.addEventListener('beforeunload', function() {
        if (btn.parentElement) btn.remove();
        if (modal.parentElement) modal.remove();
    });
});