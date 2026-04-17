<?php
// peer-learning.php
// Display study sessions with tabs for "My Sessions" and "All Sessions"
// Uses AJAX with infinite scroll to load sessions dynamically
?>

<style>
    body.peer-modal-open {
        overflow: hidden;
    }

    body.peer-modal-open > :not(.session-main-modal) {
        filter: blur(6px);
        transition: filter 0.2s ease;
        pointer-events: none;
    }

    body.peer-modal-open > .session-main-modal {
        filter: none;
        pointer-events: auto;
    }

    /* Peer Learning Component Styles */
    .peer-learning-container,
    .session-main-modal {
        --peer-surface: var(--card);
        --peer-surface-soft: var(--text_background);
        --peer-border: var(--border);
        --peer-border-strong: rgba(0, 170, 255, 0.38);
        --peer-divider: rgba(0, 170, 255, 0.18);
        --peer-tint-soft: rgba(0, 170, 255, 0.14);
        --peer-tint-subtle: rgba(0, 170, 255, 0.07);
        --peer-focus-ring: rgba(0, 170, 255, 0.24);
        --peer-focus-shadow: rgba(0, 170, 255, 0.3);
        --peer-overlay: rgba(0, 0, 0, 0.35);
        --peer-on-primary: #0b1220;
        --peer-danger: #d64545;
        --peer-danger-hover: #bf3a3a;
        --peer-danger-soft: #ef6464;
        --peer-warning: #c9852e;
        --peer-success: #2f855a;
        --peer-form-border: rgba(0, 170, 255, 0.3);
        --peer-picker-filter: invert(1) brightness(1.8) sepia(1) saturate(4) hue-rotate(175deg);
        --peer-form-color-scheme: dark;
    }

    .peer-learning-container {
        width: 100%;
    }

    [data-theme="light"] .peer-learning-container,
    [data-theme="light"] .session-main-modal {
        --peer-surface: #ffffff;
        --peer-surface-soft: #f7f9fd;
        --peer-border-strong: rgba(0, 95, 189, 0.35);
        --peer-divider: rgba(0, 95, 189, 0.18);
        --peer-tint-soft: rgba(0, 95, 189, 0.14);
        --peer-tint-subtle: rgba(0, 95, 189, 0.08);
        --peer-focus-ring: rgba(0, 95, 189, 0.24);
        --peer-focus-shadow: rgba(0, 95, 189, 0.26);
        --peer-overlay: rgba(15, 23, 42, 0.28);
        --peer-on-primary: #0b1220;
        --peer-form-border: rgba(0, 95, 189, 0.28);
        --peer-picker-filter: none;
        --peer-form-color-scheme: light;
    }

    .peer-learning-toolbar {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-bottom: 1rem;
        min-height: 3rem;
    }

    .peer-session-search {
        margin-bottom: 1rem;
    }

    .peer-session-search-form {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        width: 100%;
    }

    .peer-session-search-input {
        flex: 1;
        min-width: 0;
        border-radius: 0.6rem;
        border: 1px solid var(--peer-border-strong);
        background: var(--peer-surface-soft);
        color: var(--foreground);
        padding: 0.7rem 0.9rem;
        font-size: 0.95rem;
    }

    .peer-session-search-input::placeholder {
        color: var(--muted-foreground);
    }

    .peer-session-search-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 2px var(--peer-focus-ring);
    }

    .peer-session-search-btn,
    .peer-session-search-clear-btn {
        border: 1px solid transparent;
        border-radius: 0.55rem;
        padding: 0.65rem 1rem;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
        white-space: nowrap;
    }

    .peer-session-search-btn {
        background: var(--gradient-primary);
        color: var(--peer-on-primary);
    }

    .peer-session-search-btn:hover {
        transform: translateY(-1px);
        box-shadow: var(--glow-primary);
    }

    .peer-session-search-clear-btn {
        background: transparent;
        border-color: var(--peer-border-strong);
        color: var(--primary);
        display: none;
    }

    .peer-session-search-clear-btn:hover {
        background: var(--peer-tint-soft);
    }

    .peer-create-session-btn {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        border: 1px solid var(--primary);
        background: var(--primary);
        color: var(--peer-on-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 0 0 transparent;
        text-decoration: none;
        flex-shrink: 0;
        position: relative;
        overflow: visible;
    }

    .peer-create-session-btn:hover {
        background: var(--primary);
        color: var(--peer-on-primary);
        transform: translateY(-2px);
        box-shadow: var(--glow-primary);
    }

    .peer-create-session-btn svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .peer-create-session-label {
        position: absolute;
        right: calc(100% + 0.65rem);
        top: 50%;
        transform: translateY(-50%) translateX(10px);
        background: var(--peer-tint-soft);
        color: var(--primary);
        border: 1px solid var(--peer-border-strong);
        border-radius: 999px;
        padding: 0.45rem 0.85rem;
        white-space: nowrap;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.2px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.35s ease, transform 0.35s ease;
    }

    .peer-create-session-btn:hover .peer-create-session-label,
    .peer-create-session-btn:focus-visible .peer-create-session-label {
        opacity: 1;
        transform: translateY(-50%) translateX(0);
    }

    .peer-tabs {
        display: flex;
        gap: 0;
        border-bottom: 1px solid var(--border);
        margin-bottom: 2rem;
    }

    .peer-tab {
        padding: 1rem 1.5rem;
        background: transparent;
        border: none;
        color: var(--foreground);
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
        margin-bottom: -1px;
    }

    .peer-tab:hover {
        color: var(--primary);
        background: var(--peer-tint-subtle);
    }

    .peer-tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    .peer-content {
        display: none;
    }

    .peer-content.active {
        display: block;
    }

    /* Session Cards */
    .sessions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .session-card {
        background: var(--peer-surface);
        backdrop-filter: blur(8px);
        border: 1px solid var(--peer-divider);
        border-radius: 1rem;
        padding: 1.5rem;
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .session-card:hover {
        transform: translateY(-5px);
        border-color: var(--primary);
        box-shadow: 0 10px 30px var(--glow-primary);
    }

    .session-card.expired {
        opacity: 0.7;
    }

    .session-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
    }

    .session-audience {
        display: inline-block;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.4rem 0.8rem;
        background: var(--gradient-primary);
        color: var(--peer-on-primary);
        border-radius: 0.35rem;
    }

    .session-status-badges {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .session-expired-badge {
        display: inline-block;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 0.35rem 0.7rem;
        background: #fc8181;
        color: white;
        border-radius: 0.3rem;
    }

    .session-subject {
        display: inline-block;
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--primary);
        text-transform: uppercase;
    }

    .session-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--foreground);
        line-height: 1.4;
    }

    .session-description {
        font-size: 0.9rem;
        color: var(--muted-foreground);
        line-height: 1.6;
        max-height: 75px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .session-meta {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        padding: 1rem 0;
        border-top: 1px solid var(--peer-divider);
        border-bottom: 1px solid var(--peer-divider);
    }

    .session-meta-item {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
    }

    .session-meta-label {
        color: var(--muted-foreground);
        font-weight: 500;
    }

    .session-meta-value {
        color: var(--foreground);
        font-weight: 600;
    }

    .session-datetime {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9rem;
    }

    .session-datetime-value {
        color: var(--foreground);
        font-weight: 600;
    }

    .session-duration {
        color: var(--primary);
        font-weight: 600;
        margin-left: 0.5rem;
    }

    .session-tags {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .session-tag {
        font-size: 0.75rem;
        padding: 0.35rem 0.7rem;
        background: var(--peer-tint-soft);
        color: var(--primary);
        border-radius: 0.3rem;
        border: 1px solid var(--peer-border-strong);
    }

    .session-creator {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.85rem;
        color: var(--muted-foreground);
        padding-top: 0.5rem;
        border-top: 1px solid var(--peer-divider);
    }

    .session-creator-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid var(--peer-border-strong);
        flex-shrink: 0;
        background: var(--peer-tint-soft);
    }

    .session-creator-details {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }

    .session-creator-link,
    .session-creator-name {
        color: var(--foreground);
        font-weight: 600;
        text-decoration: none;
    }

    .session-creator-link:hover {
        text-decoration: underline;
    }

    .session-creator-university {
        color: var(--muted-foreground);
        font-size: 0.8rem;
    }

    .session-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: auto;
    }

    .session-action-btn {
        flex: 1;
        padding: 0.65rem 1rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .session-join-btn {
        background: var(--gradient-primary);
        color: var(--peer-on-primary);
    }

    .session-join-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--glow-primary);
    }

    .session-edit-btn {
        background: var(--peer-tint-soft);
        color: var(--primary);
        border: 1px solid var(--primary);
    }

    .session-edit-btn:hover {
        background: var(--primary);
        color: var(--peer-on-primary);
    }

    .session-delete-btn {
        background: var(--peer-danger);
        color: white;
    }

    .session-delete-btn:hover {
        background: var(--peer-danger-hover);
    }

    .session-actions-all {
        flex-direction: column;
    }

    .session-subscribe-btn {
        background: transparent;
        color: #fc8181;
        border: 1px solid #fc8181;
    }

    .session-subscribe-btn:hover {
        background: rgba(252, 129, 129, 0.12);
    }

    .session-subscribe-btn.subscribed {
        background: var(--peer-danger-soft);
        color: #ffffff;
        border-color: var(--peer-danger-soft);
    }

    .session-subscribe-btn.subscribed:hover {
        background: var(--peer-danger);
    }

    .session-subscribe-btn.pending {
        border-color: var(--peer-warning);
        color: var(--peer-warning);
    }

    .session-subscribe-btn.pending:hover {
        background: rgba(246, 173, 85, 0.12);
    }

    .session-subscribe-list-btn {
        background: transparent;
        color: #f87171;
        border: 1px solid #f87171;
    }

    .session-subscribe-list-btn:hover {
        background: rgba(248, 113, 113, 0.14);
    }

    .session-subscribe-btn:disabled {
        opacity: 0.65;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .subscriber-count {
        display: inline-block;
        margin-top: 0.1rem;
        font-size: 0.82rem;
        color: var(--muted-foreground);
        font-weight: 500;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        background: var(--peer-surface);
        border: 1px dashed var(--peer-border-strong);
        border-radius: 1rem;
    }

    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-state-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--foreground);
        margin-bottom: 0.5rem;
    }

    .empty-state-text {
        color: var(--muted-foreground);
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }

    .empty-state-btn {
        display: inline-block;
        padding: 0.75rem 2rem;
        background: var(--gradient-primary);
        color: var(--peer-on-primary);
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .empty-state-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--glow-primary);
    }

    /* Loading State */
    .loading-spinner {
        display: flex;
        justify-content: center;
        padding: 2rem;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid var(--peer-divider);
        border-top-color: var(--primary);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Load More Button */
    .load-more-btn {
        display: block;
        margin: 2rem auto;
        padding: 0.75rem 2rem;
        background: transparent;
        color: var(--primary);
        border: 1px solid var(--primary);
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .load-more-btn:hover {
        background: var(--peer-tint-soft);
    }

    .session-card {
        cursor: pointer;
    }

    .session-open-hint {
        margin-top: auto;
        font-size: 0.8rem;
        color: var(--muted-foreground);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-top: 1px dashed var(--peer-divider);
        padding-top: 0.75rem;
    }

    /* Main Session View Modal */
    .session-main-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        inset: 0;
        width: 100%;
        height: 100%;
        background: var(--peer-overlay);
        z-index: 10001;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        padding: 1rem;
    }

    .session-main-modal.show {
        display: flex;
    }

    .session-main-modal-content {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 1rem;
        width: min(860px, 96vw);
        max-height: 88vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 22px 64px rgba(0, 0, 0, 0.6);
    }

    .session-main-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.2rem;
        border-bottom: 1px solid var(--peer-divider);
    }

    .session-main-modal-title {
        margin: 0;
        font-size: 1.15rem;
        color: var(--foreground);
    }

    .session-main-modal-close {
        border: none;
        background: transparent;
        color: var(--muted-foreground);
        font-size: 1.25rem;
        cursor: pointer;
        padding: 0.15rem 0.4rem;
        border-radius: 0.4rem;
    }

    .session-main-modal-close:hover {
        color: var(--foreground);
        background: var(--peer-tint-soft);
    }

    .session-main-modal-body {
        padding: 1rem 1.2rem 1.2rem;
        overflow-y: auto;
    }

    .session-main-details {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .session-main-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 0.75rem;
    }

    .session-main-action-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .session-main-divider {
        border: 0;
        border-top: 1px solid var(--peer-divider);
        margin: 0.25rem 0;
    }

    .session-main-subscriber-section-title {
        margin: 0;
        color: var(--foreground);
        font-size: 1rem;
        font-weight: 700;
    }

    .session-main-subscriber-summary {
        color: var(--muted-foreground);
        font-size: 0.86rem;
        margin-top: -0.35rem;
        margin-bottom: 0.2rem;
    }

    .subscriber-list {
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
    }

    .subscriber-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        border: 1px solid var(--peer-divider);
        border-radius: 0.75rem;
        padding: 0.8rem 0.9rem;
        background: var(--peer-surface-soft);
    }

    .subscriber-name {
        color: var(--foreground);
        font-weight: 600;
        font-size: 0.95rem;
    }

    .subscriber-row-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .subscriber-status {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        border: 1px solid transparent;
        padding: 0.28rem 0.7rem;
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .subscriber-status.pending {
        color: #f6ad55;
        border-color: rgba(246, 173, 85, 0.6);
        background: rgba(246, 173, 85, 0.14);
    }

    .subscriber-status.approved {
        color: #48bb78;
        border-color: rgba(72, 187, 120, 0.6);
        background: rgba(72, 187, 120, 0.14);
    }

    .subscriber-status.rejected {
        color: #fc8181;
        border-color: rgba(252, 129, 129, 0.6);
        background: rgba(252, 129, 129, 0.14);
    }

    .subscriber-decision-btn {
        border: none;
        border-radius: 0.45rem;
        padding: 0.42rem 0.8rem;
        color: #fff;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        transition: opacity 0.25s ease;
    }

    .subscriber-decision-btn:hover {
        opacity: 0.9;
    }

    .subscriber-decision-btn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }

    .subscriber-approve-btn {
        background: #2f855a;
    }

    .subscriber-reject-btn {
        background: var(--peer-danger);
    }

    .session-main-subscriber-empty {
        text-align: center;
        color: var(--muted-foreground);
        padding: 1.5rem 0.2rem;
    }

    /* Create/Edit Session Modal */
    .create-session-modal {
        z-index: 10003;
    }

    .create-session-modal .session-main-modal-content {
        width: min(920px, 96vw);
    }

    .create-session-modal .session-main-modal-body {
        padding: 0.85rem 1rem 1rem;
    }

    .create-session-modal .create-session-container {
        max-width: 100%;
        margin: 0;
        background: transparent;
        backdrop-filter: none;
        border: none;
        border-radius: 0;
        padding: 0.4rem 0.2rem 0.2rem;
        box-shadow: none;
    }

    .create-session-modal .session-form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .create-session-modal .session-form-header {
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .create-session-modal .session-form-title {
        font-size: 1.65rem;
        font-weight: 700;
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.45rem;
    }

    .create-session-modal .session-form-subtitle {
        color: var(--muted-foreground);
        font-size: 0.95rem;
    }

    .create-session-modal .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .create-session-modal .form-label {
        font-weight: 500;
        color: var(--foreground);
        font-size: 0.95rem;
    }

    .create-session-modal .form-label.required::after {
        content: ' *';
        color: var(--primary);
    }

    .create-session-modal .form-input,
    .create-session-modal .form-textarea,
    .create-session-modal .form-select {
        padding: 0.75rem 1rem;
        border: 1px solid var(--peer-form-border);
        border-radius: 0.5rem;
        background: var(--key);
        color: var(--text);
        font-family: inherit;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        color-scheme: var(--peer-form-color-scheme);
    }

    .create-session-modal .form-input:focus,
    .create-session-modal .form-textarea:focus,
    .create-session-modal .form-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 10px var(--peer-focus-shadow);
    }

    .create-session-modal input[type="date"],
    .create-session-modal input[type="time"],
    .create-session-modal input[type="number"] {
        color-scheme: var(--peer-form-color-scheme);
    }

    .create-session-modal input[type="date"]::-webkit-calendar-picker-indicator,
    .create-session-modal input[type="time"]::-webkit-calendar-picker-indicator {
        filter: var(--peer-picker-filter);
        cursor: pointer;
        opacity: 1;
    }

    .create-session-modal input[type="number"]::-webkit-inner-spin-button,
    .create-session-modal input[type="number"]::-webkit-outer-spin-button {
        opacity: 1;
        filter: var(--peer-picker-filter);
    }

    .create-session-modal .form-textarea {
        resize: vertical;
        min-height: 120px;
    }

    .create-session-modal .form-select-group {
        position: relative;
    }

    .create-session-modal .form-select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        width: 100%;
        cursor: pointer;
        padding-right: 2.5rem;
    }

    .create-session-modal .form-select-arrow {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: var(--primary);
        font-size: 1rem;
    }

    .create-session-modal .form-row-three {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 1rem;
    }

    .create-session-modal .radio-group {
        display: flex;
        gap: 2rem;
        align-items: center;
        margin-top: 0.5rem;
    }

    .create-session-modal .radio-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .create-session-modal .radio-item input[type="radio"] {
        cursor: pointer;
        width: 18px;
        height: 18px;
        accent-color: var(--primary);
    }

    .create-session-modal .radio-item label {
        cursor: pointer;
        color: var(--foreground);
        font-size: 0.95rem;
        user-select: none;
    }

    .create-session-modal .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 1.75rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border);
    }

    .create-session-modal .btn {
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .create-session-modal .btn-create {
        background: var(--gradient-primary);
        color: var(--peer-on-primary);
        flex: 1;
        max-width: 250px;
    }

    .create-session-modal .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: var(--glow-primary);
    }

    .create-session-modal .btn-create:disabled {
        opacity: 0.65;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .create-session-modal .btn-cancel {
        background: transparent;
        color: var(--foreground);
        border: 1px solid var(--border);
        flex: 1;
        max-width: 250px;
    }

    .create-session-modal .btn-cancel:hover {
        background: var(--peer-tint-soft);
        border-color: var(--primary);
        color: var(--primary);
    }

    .create-session-modal .helper-text {
        font-size: 0.85rem;
        color: var(--muted-foreground);
        margin-top: 0.25rem;
    }

    .create-session-modal .form-error {
        color: var(--peer-danger-soft);
        font-size: 0.85rem;
        margin-top: 0.25rem;
        display: none;
    }

    .create-session-modal .form-error.show {
        display: block;
    }

    .create-session-modal .form-input.error,
    .create-session-modal .form-textarea.error,
    .create-session-modal .form-select.error {
        border-color: var(--peer-danger-soft);
        box-shadow: 0 0 10px rgba(214, 69, 69, 0.24);
    }

    .create-session-modal-error {
        text-align: center;
        color: var(--peer-danger-soft);
        padding: 1.25rem 0.5rem;
    }

    @media (max-width: 768px) {
        .sessions-grid {
            grid-template-columns: 1fr;
        }

        .session-actions {
            flex-direction: column;
        }

        .session-action-btn {
            width: 100%;
        }

        .peer-tabs {
            flex-direction: column;
        }

        .peer-tab {
            padding: 0.75rem 1rem;
        }

        .peer-create-session-label {
            display: none;
        }

        .peer-session-search-form {
            flex-wrap: wrap;
        }

        .peer-session-search-btn,
        .peer-session-search-clear-btn {
            flex: 1;
            min-width: 130px;
        }

        .session-main-modal {
            padding: 0.75rem;
        }

        .create-session-modal .create-session-container {
            padding: 0.1rem;
        }

        .create-session-modal .form-row-three {
            grid-template-columns: 1fr;
        }

        .create-session-modal .radio-group {
            gap: 1rem;
            flex-direction: column;
            align-items: flex-start;
        }

        .create-session-modal .form-actions {
            flex-direction: column;
        }

        .create-session-modal .btn-create,
        .create-session-modal .btn-cancel {
            max-width: 100%;
        }

        .session-main-actions {
            grid-template-columns: 1fr;
        }

        .subscriber-row {
            flex-direction: column;
            align-items: flex-start;
        }

        .subscriber-row-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }
</style>

<div class="peer-learning-container">
    <?php if (!isset($user) || $user->role !== 'role-applicant'): ?>
        <div class="peer-learning-toolbar">
            <a href="/UniHelper/create-session" class="peer-create-session-btn" title="Create Session"
                aria-label="Create Session" data-action="open-create-session-modal" data-session-id="0">
                <span class="peer-create-session-label">Create New Session</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14"></path>
                    <path d="M5 12h14"></path>
                </svg>
            </a>
        </div>
    <?php endif; ?>

    <div class="peer-session-search">
        <form id="peer-session-search-form" class="peer-session-search-form" role="search">
            <input
                id="peer-session-search-input"
                class="peer-session-search-input"
                type="text"
                placeholder="Search sessions by title, subject, description, tags, or creator"
                autocomplete="off"
                aria-label="Search sessions"
            />
            <button type="submit" class="peer-session-search-btn">Search</button>
            <button type="button" id="peer-session-search-clear" class="peer-session-search-clear-btn">Clear</button>
        </form>
    </div>

    <!-- Tabs -->
    <div class="peer-tabs">
        <button class="peer-tab active" data-tab="my-sessions">My Sessions</button>
        <button class="peer-tab" data-tab="all-sessions">All Sessions</button>
    </div>

    <!-- My Sessions Tab -->
    <div class="peer-content active" id="my-sessions">
        <div id="my-sessions-container" class="sessions-grid"></div>
        <button class="load-more-btn" id="my-sessions-load-more" style="display: none;">Load More Sessions</button>
        <div class="loading-spinner" id="my-sessions-loading" style="display: none;">
            <div class="spinner"></div>
        </div>
    </div>

    <!-- All Sessions Tab -->
    <div class="peer-content" id="all-sessions">
        <div id="all-sessions-container" class="sessions-grid"></div>
        <button class="load-more-btn" id="all-sessions-load-more" style="display: none;">Load More Sessions</button>
        <div class="loading-spinner" id="all-sessions-loading" style="display: none;">
            <div class="spinner"></div>
        </div>
    </div>
</div>

<div class="session-main-modal" id="sessionMainModal" aria-hidden="true">
    <div class="session-main-modal-content" role="dialog" aria-modal="true" aria-labelledby="sessionMainModalTitle">
        <div class="session-main-modal-header">
            <h3 class="session-main-modal-title" id="sessionMainModalTitle">Session Details</h3>
            <button type="button" class="session-main-modal-close" data-modal-action="close-session-modal"
                aria-label="Close session view">&times;</button>
        </div>
        <div class="session-main-modal-body" id="sessionMainModalBody"></div>
    </div>
</div>

<div class="session-main-modal create-session-modal" id="createSessionModal" aria-hidden="true">
    <div class="session-main-modal-content" role="dialog" aria-modal="true" aria-labelledby="createSessionModalTitle">
        <div class="session-main-modal-header">
            <h3 class="session-main-modal-title" id="createSessionModalTitle">Create Study Session</h3>
            <button type="button" class="session-main-modal-close" data-action="close-create-session-modal"
                aria-label="Close create session form">&times;</button>
        </div>
        <div class="session-main-modal-body" id="createSessionModalBody"></div>
    </div>
</div>

<script>
    const BASE_URL = '/UniHelper';
    const CURRENT_USER_ID = <?= (int)($user->id ?? 0) ?>;

    let currentTab = 'my-sessions';
    let mySessionsPage = 1;
    let allSessionsPage = 1;
    let activeSessionId = null;
    let sessionForSubscribersId = null;
    let activeCreateSessionId = 0;
    let createSessionSubmitInFlight = false;

    const sessionSearchState = {
        'my-sessions': { query: '', page: 1 },
        'all-sessions': { query: '', page: 1 }
    };

    const sessionCache = new Map();
    const pageParams = getPageParams();
    const sessionMainModalElement = document.getElementById('sessionMainModal');
    const createSessionModalElement = document.getElementById('createSessionModal');
    const createSessionModalTitleElement = document.getElementById('createSessionModalTitle');
    const createSessionModalBodyElement = document.getElementById('createSessionModalBody');
    const sessionSearchForm = document.getElementById('peer-session-search-form');
    const sessionSearchInput = document.getElementById('peer-session-search-input');
    const sessionSearchClearButton = document.getElementById('peer-session-search-clear');

    if (typeof window._peerLearningDeepLinkHandled === 'undefined') {
        window._peerLearningDeepLinkHandled = false;
    }

    if (sessionMainModalElement && sessionMainModalElement.parentElement !== document.body) {
        document.body.appendChild(sessionMainModalElement);
    }

    if (createSessionModalElement && createSessionModalElement.parentElement !== document.body) {
        document.body.appendChild(createSessionModalElement);
    }

    function updateBodyModalState() {
        const hasVisibleModal = document.querySelector('.session-main-modal.show') !== null;
        document.body.classList.toggle('peer-modal-open', hasVisibleModal);
    }

    function getPageParams() {
        const main = document.getElementById('dashboardMain');
        if (!main) {
            return {};
        }

        try {
            return JSON.parse(main.dataset.pageParams || '{}') || {};
        } catch (error) {
            console.warn('Invalid page params for peer-learning:', error);
            return {};
        }
    }

    function normalizeTabName(rawTab) {
        return rawTab === 'all-sessions' ? 'all-sessions' : 'my-sessions';
    }

    function normalizePositiveInt(rawValue) {
        const parsed = Number(rawValue);
        return Number.isInteger(parsed) && parsed > 0 ? parsed : 0;
    }

    function replaceBrowserUrl(url) {
        if (!window.history || typeof window.history.replaceState !== 'function') {
            return;
        }

        window.history.replaceState({}, '', url);
    }

    function buildSessionDeepLinkUrl(sessionId, tabName) {
        const normalizedSessionId = normalizePositiveInt(sessionId);
        if (!normalizedSessionId) {
            return `${BASE_URL}/peer-learning`;
        }

        const normalizedTab = normalizeTabName(tabName);
        return `${BASE_URL}/peer-learning?session_id=${encodeURIComponent(String(normalizedSessionId))}&tab=${encodeURIComponent(normalizedTab)}`;
    }

    function syncUrlToActiveSession(sessionId, tabName) {
        replaceBrowserUrl(buildSessionDeepLinkUrl(sessionId, tabName));
    }

    function resetPeerLearningUrl() {
        replaceBrowserUrl(`${BASE_URL}/peer-learning`);
    }

    function showPeerLearningError(message) {
        const safeMessage = message || 'Something went wrong. Please try again.';
        if (typeof window.showToast === 'function') {
            window.showToast(safeMessage, 'error');
            return;
        }

        alert(safeMessage);
    }

    function normalizeSearchQuery(value) {
        return String(value || '').trim();
    }

    function getTabSearchState(tabName) {
        return tabName === 'all-sessions'
            ? sessionSearchState['all-sessions']
            : sessionSearchState['my-sessions'];
    }

    function isSearchMode(tabName) {
        return getTabSearchState(tabName).query.length >= 2;
    }

    function getTabElements(tabName) {
        if (tabName === 'all-sessions') {
            return {
                container: document.getElementById('all-sessions-container'),
                loading: document.getElementById('all-sessions-loading'),
                loadMoreBtn: document.getElementById('all-sessions-load-more')
            };
        }

        return {
            container: document.getElementById('my-sessions-container'),
            loading: document.getElementById('my-sessions-loading'),
            loadMoreBtn: document.getElementById('my-sessions-load-more')
        };
    }

    function updateSearchControls() {
        if (!sessionSearchInput || !sessionSearchClearButton) {
            return;
        }

        sessionSearchClearButton.style.display = sessionSearchInput.value.trim() !== '' ? 'inline-flex' : 'none';
    }

    function syncSearchUiToCurrentTab() {
        if (!sessionSearchInput) {
            return;
        }

        sessionSearchInput.value = getTabSearchState(currentTab).query;
        updateSearchControls();
    }

    function createSearchEmptyState(tabName, query) {
        const normalizedQuery = normalizeSearchQuery(query);

        if (tabName === 'all-sessions') {
            return createEmptyState(
                'No matching sessions',
                `No sessions in All Sessions matched "${normalizedQuery}".`
            );
        }

        return createEmptyState(
            'No matching sessions',
            `No sessions in My Sessions matched "${normalizedQuery}".`
        );
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escapeAttribute(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function normalizeSubscriptionStatus(rawStatus) {
        const status = String(rawStatus || '').toLowerCase();
        if (status === 'approved' || status === 'pending') {
            return status;
        }
        return 'none';
    }

    function normalizeSubscriberDecisionStatus(rawStatus) {
        const status = String(rawStatus || '').toLowerCase();
        if (status === 'approved' || status === 'pending' || status === 'rejected') {
            return status;
        }
        return 'pending';
    }

    function getAudienceLabel(audience) {
        if (audience === 'my_university') {
            return 'My University';
        }
        if (audience === 'private') {
            return 'Private';
        }
        return 'All Universities';
    }

    function getSubscribeButtonLabel(status) {
        if (status === 'pending') {
            return 'Pending';
        }
        if (status === 'approved') {
            return 'Subscribed';
        }
        return 'Subscribe';
    }

    function formatDecisionStatus(status) {
        if (status === 'approved') {
            return 'Approved';
        }
        if (status === 'rejected') {
            return 'Rejected';
        }
        return 'Pending';
    }

    function getAuthorFullName(session) {
        const firstName = String(session.creator_first_name || '').trim();
        const lastName = String(session.creator_last_name || '').trim();
        const fullName = `${firstName} ${lastName}`.trim();
        return fullName || 'Unknown User';
    }

    function getProfileImageUrl(profilePicturePath) {
        const rawPath = String(profilePicturePath || '').trim();
        if (!rawPath) {
            return `${BASE_URL}/views/assets/default-pfp.png`;
        }

        if (/^https?:\/\//i.test(rawPath)) {
            return rawPath;
        }

        if (rawPath.startsWith('/')) {
            return `${BASE_URL}${rawPath}`;
        }

        return `${BASE_URL}/${rawPath}`;
    }

    function formatSubscriberCount(count) {
        const safeCount = Math.max(0, Number(count || 0));
        return `${safeCount} subscriber${safeCount === 1 ? '' : 's'}`;
    }

    function formatDate(dateStr) {
        if (!dateStr) {
            return '-';
        }

        const date = new Date(`${dateStr}T00:00:00`);
        if (Number.isNaN(date.getTime())) {
            return '-';
        }

        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function createEmptyState(title, text) {
        return `
            <div class="empty-state">
                <div class="empty-state-icon">📚</div>
                <h3 class="empty-state-title">${escapeHtml(title)}</h3>
                <p class="empty-state-text">${escapeHtml(text)}</p>
                <a href="${BASE_URL}/create-session" class="empty-state-btn" data-action="open-create-session-modal" data-session-id="0">Create Session</a>
            </div>
        `;
    }

    function isOwnerSession(session) {
        return Number(session.user_id || 0) === Number(CURRENT_USER_ID || 0);
    }

    function canCurrentUserJoin(session) {
        if (!session || !session.session_link) {
            return false;
        }
        return Number(session.can_join || 0) === 1 || isOwnerSession(session);
    }

    function upsertSessionCache(session) {
        const sessionId = Number(session && session.id ? session.id : 0);
        if (!sessionId) {
            return null;
        }

        const previous = sessionCache.get(sessionId) || {};
        const merged = { ...previous, ...session, id: sessionId };
        sessionCache.set(sessionId, merged);
        return merged;
    }

    function getSessionFromCache(sessionId) {
        const normalizedSessionId = Number(sessionId || 0);
        if (!normalizedSessionId) {
            return null;
        }
        return sessionCache.get(normalizedSessionId) || null;
    }

    function updateSubscribeButtonState(button, status) {
        const normalizedStatus = normalizeSubscriptionStatus(status);
        const subscribed = normalizedStatus !== 'none';

        button.setAttribute('data-status', normalizedStatus);
        button.setAttribute('data-subscribed', subscribed ? '1' : '0');
        button.textContent = getSubscribeButtonLabel(normalizedStatus);

        button.classList.remove('subscribed', 'pending');
        if (normalizedStatus === 'approved') {
            button.classList.add('subscribed');
        }
        if (normalizedStatus === 'pending') {
            button.classList.add('pending');
        }
    }

    function updateSubscriberCountForSession(sessionId, count) {
        if (typeof count === 'undefined' || count === null) {
            return;
        }

        const normalizedSessionId = Number(sessionId || 0);
        const safeCount = Math.max(0, Number(count || 0));
        document.querySelectorAll(`.subscriber-count[data-session-id="${normalizedSessionId}"]`).forEach(element => {
            element.textContent = formatSubscriberCount(safeCount);
        });

        const cachedSession = getSessionFromCache(normalizedSessionId);
        if (cachedSession) {
            cachedSession.sub_count = safeCount;
            upsertSessionCache(cachedSession);
        }
    }

    function createSessionCard(session) {
        const safeSession = upsertSessionCache(session) || session;
        const isExpired = Number(safeSession.is_expired || 0) === 1 || (safeSession.deleted_at && !safeSession.is_deleted);
        const audienceLabel = getAudienceLabel(safeSession.audience);
        const tags = String(safeSession.tags || '')
            .split(',')
            .map(tag => tag.trim())
            .filter(Boolean)
            .map(tag => `<span class="session-tag">${escapeHtml(tag)}</span>`)
            .join('');
        const subscriberCount = Math.max(0, Number(safeSession.sub_count || 0));
        const authorName = getAuthorFullName(safeSession);
        const authorNameHtml = escapeHtml(authorName);
        const authorId = Number(safeSession.user_id || 0);
        const authorLink = authorId ? `${BASE_URL}/view/profile/${authorId}` : '';
        const authorLinkHtml = authorLink
            ? `<a class="session-creator-link" href="${authorLink}" target="_blank" rel="noopener">${authorNameHtml}</a>`
            : `<span class="session-creator-name">${authorNameHtml}</span>`;
        const authorUniversity = escapeHtml(safeSession.creator_university || safeSession.university || 'Unknown University');
        const authorAvatarUrl = escapeAttribute(getProfileImageUrl(safeSession.creator_profile_picture));
        const expiredBadge = isExpired ? '<span class="session-expired-badge">Expired</span>' : '';
        const sessionId = Number(safeSession.id || 0);

        return `
            <div class="session-card ${isExpired ? 'expired' : ''}" data-session-id="${sessionId}" role="button" tabindex="0"
                 aria-label="Open session details">
                <div class="session-header">
                    <div>
                        <span class="session-audience">${escapeHtml(audienceLabel)}</span>
                        <div style="margin-top: 0.5rem;">
                            <span class="session-subject">${escapeHtml(safeSession.subject || 'General')}</span>
                        </div>
                    </div>
                    <div class="session-status-badges">${expiredBadge}</div>
                </div>
                <h3 class="session-title">${escapeHtml(safeSession.title || 'Untitled Session')}</h3>
                <p class="session-description">${escapeHtml(safeSession.description || 'No description available.')}</p>
                <div class="session-meta">
                    <div class="session-meta-item">
                        <span class="session-meta-label">Date:</span>
                        <span class="session-meta-value">${formatDate(safeSession.date)}</span>
                    </div>
                    <div class="session-meta-item">
                        <span class="session-meta-label">Time:</span>
                        <span class="session-meta-value">${escapeHtml(safeSession.time || '-')} <span class="session-duration">[${escapeHtml(String(safeSession.duration || '-'))}h]</span></span>
                    </div>
                </div>
                ${tags ? `<div class="session-tags">${tags}</div>` : ''}
                <div class="session-creator">
                    <img class="session-creator-avatar" src="${authorAvatarUrl}" alt="${escapeAttribute(authorName)}" loading="lazy" />
                    <div class="session-creator-details">
                        ${authorLinkHtml}
                        <span class="session-creator-university">${authorUniversity}</span>
                    </div>
                </div>
                <span class="subscriber-count" data-session-id="${sessionId}">${formatSubscriberCount(subscriberCount)}</span>
                <div class="session-open-hint">Click to open full session view</div>
            </div>
        `;
    }

    function renderMainSessionModal(session) {
        const sessionId = Number(session.id || 0);
        if (!sessionId) {
            return;
        }

        const isOwner = isOwnerSession(session);
        const showPrivateSubscriberList = isOwner && String(session.audience || '') === 'private';
        const canJoin = canCurrentUserJoin(session);
        const audienceLabel = getAudienceLabel(session.audience);
        const authorName = getAuthorFullName(session);
        const authorId = Number(session.user_id || 0);
        const authorLink = authorId ? `${BASE_URL}/view/profile/${authorId}` : '';
        const authorLinkHtml = authorLink
            ? `<a class="session-creator-link" href="${authorLink}" target="_blank" rel="noopener">${escapeHtml(authorName)}</a>`
            : `<span class="session-creator-name">${escapeHtml(authorName)}</span>`;
        const authorUniversity = escapeHtml(session.creator_university || session.university || 'Unknown University');
        const authorAvatarUrl = escapeAttribute(getProfileImageUrl(session.creator_profile_picture));
        const subscriberCount = Math.max(0, Number(session.sub_count || 0));
        const subscriptionStatus = normalizeSubscriptionStatus(session.subscription_status || (Number(session.is_subscribed) === 1 ? 'approved' : 'none'));
        const subscribeBtnClass = subscriptionStatus === 'approved'
            ? 'session-subscribe-btn subscribed'
            : (subscriptionStatus === 'pending' ? 'session-subscribe-btn pending' : 'session-subscribe-btn');
        const tags = String(session.tags || '')
            .split(',')
            .map(tag => tag.trim())
            .filter(Boolean)
            .map(tag => `<span class="session-tag">${escapeHtml(tag)}</span>`)
            .join('');
        const isExpired = Number(session.is_expired || 0) === 1 || (session.deleted_at && !session.is_deleted);

        const actionButtons = [];
        if (isOwner) {
            actionButtons.push(`
                <button type="button" class="session-action-btn session-edit-btn" data-modal-action="edit-session"
                        data-session-id="${sessionId}">Edit</button>
            `);
            actionButtons.push(`
                <button type="button" class="session-action-btn session-delete-btn" data-modal-action="delete-session"
                        data-session-id="${sessionId}">Delete</button>
            `);
            if (showPrivateSubscriberList) {
                actionButtons.push(`
                    <button type="button" class="session-action-btn session-subscribe-list-btn"
                            data-modal-action="refresh-subscriber-list" data-session-id="${sessionId}">Subscribe List</button>
                `);
            }
        }

        if (canJoin) {
            actionButtons.push(`
                <a href="${escapeAttribute(session.session_link)}" target="_blank" rel="noopener"
                   class="session-action-btn session-join-btn session-main-action-link">Join Session</a>
            `);
        }

        if (!isOwner) {
            actionButtons.push(`
                <button
                    type="button"
                    class="session-action-btn ${subscribeBtnClass}"
                    data-session-id="${sessionId}"
                    data-status="${subscriptionStatus}"
                    data-subscribed="${subscriptionStatus !== 'none' ? 1 : 0}"
                >${getSubscribeButtonLabel(subscriptionStatus)}</button>
            `);
        }

        document.getElementById('sessionMainModalTitle').textContent = session.title || 'Session Details';
        document.getElementById('sessionMainModalBody').innerHTML = `
            <div class="session-main-details">
                <div class="session-header">
                    <div>
                        <span class="session-audience">${escapeHtml(audienceLabel)}</span>
                        <div style="margin-top: 0.5rem;">
                            <span class="session-subject">${escapeHtml(session.subject || 'General')}</span>
                        </div>
                    </div>
                    <div class="session-status-badges">
                        ${isExpired ? '<span class="session-expired-badge">Expired</span>' : ''}
                    </div>
                </div>

                <h3 class="session-title">${escapeHtml(session.title || 'Untitled Session')}</h3>
                <p class="session-description" style="max-height:none;-webkit-line-clamp:unset;">${escapeHtml(session.description || 'No description available.')}</p>

                <div class="session-meta">
                    <div class="session-meta-item">
                        <span class="session-meta-label">Date:</span>
                        <span class="session-meta-value">${formatDate(session.date)}</span>
                    </div>
                    <div class="session-meta-item">
                        <span class="session-meta-label">Time:</span>
                        <span class="session-meta-value">${escapeHtml(session.time || '-')} <span class="session-duration">[${escapeHtml(String(session.duration || '-'))}h]</span></span>
                    </div>
                </div>

                ${tags ? `<div class="session-tags">${tags}</div>` : ''}

                <div class="session-creator">
                    <img class="session-creator-avatar" src="${authorAvatarUrl}" alt="${escapeAttribute(authorName)}" loading="lazy" />
                    <div class="session-creator-details">
                        ${authorLinkHtml}
                        <span class="session-creator-university">${authorUniversity}</span>
                    </div>
                </div>

                <span class="subscriber-count" data-session-id="${sessionId}">${formatSubscriberCount(subscriberCount)}</span>

                ${actionButtons.length > 0 ? `<div class="session-main-actions">${actionButtons.join('')}</div>` : ''}

                ${showPrivateSubscriberList ? `
                    <hr class="session-main-divider" />
                    <h4 class="session-main-subscriber-section-title">Subscribe List</h4>
                    <p class="session-main-subscriber-summary">Manage pending and existing private-session subscribers.</p>
                    <div id="sessionMainSubscriberBody"></div>
                ` : ''}
            </div>
        `;

        if (showPrivateSubscriberList) {
            renderSubscriberListLoading();
        }
    }

    function openSessionModal(session) {
        const safeSession = upsertSessionCache(session) || session;
        const sessionId = Number(safeSession.id || 0);
        if (!sessionId) {
            showPeerLearningError('Invalid session ID.');
            return;
        }

        activeSessionId = sessionId;
        sessionForSubscribersId = null;
        renderMainSessionModal(safeSession);

        const modal = document.getElementById('sessionMainModal');
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        updateBodyModalState();
        syncUrlToActiveSession(sessionId, currentTab);

        if (isOwnerSession(safeSession) && String(safeSession.audience || '') === 'private') {
            sessionForSubscribersId = sessionId;
            fetchSubscriberList(sessionId);
        }
    }

    function closeSessionModal() {
        const modal = document.getElementById('sessionMainModal');
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        activeSessionId = null;
        sessionForSubscribersId = null;
        updateBodyModalState();
        resetPeerLearningUrl();
    }

    function showCreateSessionModalLoading() {
        if (!createSessionModalBodyElement) {
            return;
        }

        createSessionModalBodyElement.innerHTML = `
            <div class="loading-spinner" style="padding: 1.8rem 0;">
                <div class="spinner"></div>
            </div>
        `;
    }

    function renderCreateSessionModalError(message) {
        if (!createSessionModalBodyElement) {
            return;
        }

        createSessionModalBodyElement.innerHTML = `
            <div class="create-session-modal-error">${escapeHtml(message || 'Failed to load the session form.')}</div>
        `;
    }

    function openCreateSessionModal(sessionId = 0) {
        const normalizedSessionId = Math.max(0, Number(sessionId || 0));
        activeCreateSessionId = normalizedSessionId;

        if (!createSessionModalElement || !createSessionModalBodyElement) {
            return;
        }

        showCreateSessionModalLoading();
        if (createSessionModalTitleElement) {
            createSessionModalTitleElement.textContent = normalizedSessionId > 0 ? 'Edit Study Session' : 'Create Study Session';
        }
        createSessionModalElement.classList.add('show');
        createSessionModalElement.setAttribute('aria-hidden', 'false');
        updateBodyModalState();

        const query = normalizedSessionId > 0 ? `&session_id=${normalizedSessionId}` : '';
        fetch(`${BASE_URL}/api?controller=SessionController&action=getSessionFormModal${query}`, {
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(result => {
                if (!result.success || !result.data || !result.data.html) {
                    throw new Error(result.message || result.error || 'Failed to load the session form.');
                }

                if (createSessionModalTitleElement) {
                    createSessionModalTitleElement.textContent = result.data.title || (normalizedSessionId > 0 ? 'Edit Study Session' : 'Create Study Session');
                }
                createSessionModalBodyElement.innerHTML = result.data.html;
            })
            .catch(error => {
                console.error('Create-session modal load error:', error);
                renderCreateSessionModalError(error.message || 'Failed to load the session form.');
            });
    }

    function closeCreateSessionModal() {
        if (!createSessionModalElement) {
            return;
        }

        createSessionModalElement.classList.remove('show');
        createSessionModalElement.setAttribute('aria-hidden', 'true');
        if (createSessionModalBodyElement) {
            createSessionModalBodyElement.innerHTML = '';
        }

        createSessionSubmitInFlight = false;
        activeCreateSessionId = 0;
        updateBodyModalState();
    }

    function submitCreateSessionModalForm(formElement) {
        if (!formElement || createSessionSubmitInFlight) {
            return;
        }

        createSessionSubmitInFlight = true;
        const submitButton = formElement.querySelector('.btn-create');
        const previousSubmitText = submitButton ? submitButton.textContent : '';

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';
        }

        const formData = new FormData(formElement);

        fetch(`${BASE_URL}/api?controller=SessionController&action=submitSessionModal`, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(response => response.json())
            .then(result => {
                if (!result.success) {
                    if (result.validation && result.data && result.data.html && createSessionModalBodyElement) {
                        createSessionModalBodyElement.innerHTML = result.data.html;
                        return;
                    }

                    if (result.data && result.data.html && createSessionModalBodyElement) {
                        createSessionModalBodyElement.innerHTML = result.data.html;
                    }

                    throw new Error(result.message || result.error || 'Failed to save session.');
                }

                const payload = result.data || {};
                const returnedSession = payload.session ? upsertSessionCache(payload.session) : null;
                const updatedSessionId = Number(payload.session_id || (returnedSession && returnedSession.id) || 0);

                closeCreateSessionModal();
                loadTabData('my-sessions', 1);

                const allSessionsContainer = document.getElementById('all-sessions-container');
                if (allSessionsContainer && allSessionsContainer.innerHTML) {
                    loadTabData('all-sessions', 1);
                }

                if (updatedSessionId && activeSessionId === updatedSessionId && sessionMainModalElement && sessionMainModalElement.classList.contains('show')) {
                    if (returnedSession) {
                        renderMainSessionModal(returnedSession);
                        if (isOwnerSession(returnedSession) && String(returnedSession.audience || '') === 'private') {
                            sessionForSubscribersId = updatedSessionId;
                            fetchSubscriberList(updatedSessionId);
                        }
                    } else {
                        fetchSessionForView(updatedSessionId)
                            .then(session => {
                                renderMainSessionModal(session);
                                if (isOwnerSession(session) && String(session.audience || '') === 'private') {
                                    sessionForSubscribersId = updatedSessionId;
                                    fetchSubscriberList(updatedSessionId);
                                }
                            })
                            .catch(error => {
                                console.error('Failed to refresh updated session:', error);
                            });
                    }
                }
            })
            .catch(error => {
                console.error('Create-session modal submit error:', error);
                alert(error.message || 'Failed to save session. Please try again.');
            })
            .finally(() => {
                createSessionSubmitInFlight = false;

                if (!createSessionModalBodyElement) {
                    return;
                }

                const nextSubmitButton = createSessionModalBodyElement.querySelector('.btn-create');
                if (nextSubmitButton) {
                    nextSubmitButton.disabled = false;
                    nextSubmitButton.textContent = previousSubmitText || nextSubmitButton.textContent;
                }
            });
    }

    function fetchSessionForView(sessionId) {
        return fetch(`${BASE_URL}/api?controller=SessionController&action=getSessionForView&session_id=${sessionId}`, {
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(result => {
                if (!result.success || !result.data) {
                    throw new Error(result.message || result.error || 'Failed to load session details.');
                }
                return upsertSessionCache(result.data);
            });
    }

    function openSessionModalById(sessionId) {
        const normalizedId = normalizePositiveInt(sessionId);
        if (!normalizedId) {
            return Promise.reject(new Error('Invalid session ID.'));
        }

        const cached = getSessionFromCache(normalizedId);
        if (cached) {
            openSessionModal(cached);
            return Promise.resolve(cached);
        }

        return fetchSessionForView(normalizedId).then(session => {
            openSessionModal(session);
            return session;
        });
    }

    function switchTab(tabName) {
        const normalizedTab = normalizeTabName(tabName);

        document.querySelectorAll('.peer-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        const targetTabButton = document.querySelector(`[data-tab="${normalizedTab}"]`);
        if (targetTabButton) {
            targetTabButton.classList.add('active');
        }

        document.querySelectorAll('.peer-content').forEach(content => {
            content.classList.remove('active');
        });
        const targetTabContent = document.getElementById(normalizedTab);
        if (targetTabContent) {
            targetTabContent.classList.add('active');
        }

        currentTab = normalizedTab;
        syncSearchUiToCurrentTab();

        const targetContainer = normalizedTab === 'all-sessions'
            ? document.getElementById('all-sessions-container')
            : document.getElementById('my-sessions-container');
        const expectedMode = isSearchMode(normalizedTab) ? 'search' : 'default';

        if (!targetContainer || !targetContainer.innerHTML || targetContainer.dataset.mode !== expectedMode) {
            return loadTabData(normalizedTab, 1);
        }

        return Promise.resolve();
    }

    function loadMySessions(page) {
        const container = document.getElementById('my-sessions-container');
        const loading = document.getElementById('my-sessions-loading');
        const loadMoreBtn = document.getElementById('my-sessions-load-more');

        if (page === 1) {
            loading.style.display = 'flex';
        }

        return fetch(`${BASE_URL}/api?controller=SessionController&action=getMyessions&page=${page}`)
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';

                if (!data.success) {
                    throw new Error(data.error || 'Failed to load your sessions');
                }

                if (data.data && data.data.length > 0) {
                    if (page === 1) {
                        container.innerHTML = '';
                        container.dataset.mode = 'default';
                    }

                    data.data.forEach(session => {
                        upsertSessionCache(session);
                        container.insertAdjacentHTML('beforeend', createSessionCard(session));
                    });

                    loadMoreBtn.style.display = data.count >= 10 ? 'block' : 'none';
                    mySessionsPage = page + 1;
                } else if (page === 1) {
                    container.innerHTML = createEmptyState('No sessions yet', 'Create your first study session to get started!');
                    container.dataset.mode = 'default';
                    loadMoreBtn.style.display = 'none';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                console.error('Error loading sessions:', error);
                alert(error.message || 'Failed to load your sessions. Please try again.');
                if (page === 1) {
                    container.innerHTML = '<p style="color: #fc8181; text-align: center;">Failed to load sessions</p>';
                    container.dataset.mode = 'default';
                }
            });
    }

    function loadAllSessions(page) {
        const container = document.getElementById('all-sessions-container');
        const loading = document.getElementById('all-sessions-loading');
        const loadMoreBtn = document.getElementById('all-sessions-load-more');

        if (page === 1) {
            loading.style.display = 'flex';
        }

        return fetch(`${BASE_URL}/api?controller=SessionController&action=getAllSessions&page=${page}`)
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';

                if (!data.success) {
                    throw new Error(data.error || 'Failed to load sessions');
                }

                if (data.data && data.data.length > 0) {
                    if (page === 1) {
                        container.innerHTML = '';
                        container.dataset.mode = 'default';
                    }

                    data.data.forEach(session => {
                        upsertSessionCache(session);
                        container.insertAdjacentHTML('beforeend', createSessionCard(session));
                    });

                    loadMoreBtn.style.display = data.count >= 10 ? 'block' : 'none';
                    allSessionsPage = page + 1;
                } else if (page === 1) {
                    container.innerHTML = createEmptyState('No sessions available', 'Create a new session to get started!');
                    container.dataset.mode = 'default';
                    loadMoreBtn.style.display = 'none';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                console.error('Error loading sessions:', error);
                alert(error.message || 'Failed to load sessions. Please try again.');
                if (page === 1) {
                    container.innerHTML = '<p style="color: #fc8181; text-align: center;">Failed to load sessions</p>';
                    container.dataset.mode = 'default';
                }
            });
    }

    function searchSessions(tabName, page) {
        const normalizedTab = tabName === 'all-sessions' ? 'all-sessions' : 'my-sessions';
        const searchState = getTabSearchState(normalizedTab);
        const query = normalizeSearchQuery(searchState.query);

        if (query.length < 2) {
            return Promise.resolve();
        }

        const { container, loading, loadMoreBtn } = getTabElements(normalizedTab);
        if (!container || !loading || !loadMoreBtn) {
            return Promise.resolve();
        }

        if (page === 1) {
            loading.style.display = 'flex';
        }

        const encodedQuery = encodeURIComponent(query);
        const encodedTab = encodeURIComponent(normalizedTab);

        return fetch(`${BASE_URL}/api?controller=SessionController&action=searchSessions&query=${encodedQuery}&tab=${encodedTab}&page=${page}`)
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';

                if (!data.success) {
                    throw new Error(data.error || 'Failed to search sessions');
                }

                if (data.data && data.data.length > 0) {
                    if (page === 1) {
                        container.innerHTML = '';
                        container.dataset.mode = 'search';
                    }

                    data.data.forEach(session => {
                        upsertSessionCache(session);
                        container.insertAdjacentHTML('beforeend', createSessionCard(session));
                    });

                    loadMoreBtn.style.display = data.count >= 10 ? 'block' : 'none';
                    searchState.page = page + 1;
                } else {
                    if (page === 1) {
                        container.innerHTML = createSearchEmptyState(normalizedTab, query);
                        container.dataset.mode = 'search';
                    }
                    loadMoreBtn.style.display = 'none';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                console.error('Error searching sessions:', error);
                alert(error.message || 'Failed to search sessions. Please try again.');
                if (page === 1) {
                    container.innerHTML = '<p style="color: #fc8181; text-align: center;">Failed to search sessions</p>';
                    container.dataset.mode = 'search';
                }
            });
    }

    function loadTabData(tabName, page) {
        if (isSearchMode(tabName)) {
            return searchSessions(tabName, page);
        }

        if (tabName === 'all-sessions') {
            return loadAllSessions(page);
        }

        return loadMySessions(page);
    }

    function getNextPageForTab(tabName) {
        if (isSearchMode(tabName)) {
            return getTabSearchState(tabName).page;
        }

        return tabName === 'all-sessions' ? allSessionsPage : mySessionsPage;
    }

    function clearSearchForTab(tabName, reload = false) {
        const normalizedTab = tabName === 'all-sessions' ? 'all-sessions' : 'my-sessions';
        const state = getTabSearchState(normalizedTab);
        state.query = '';
        state.page = 1;

        if (normalizedTab === currentTab) {
            syncSearchUiToCurrentTab();
        }

        if (!reload) {
            return Promise.resolve();
        }

        return loadTabData(normalizedTab, 1);
    }

    function submitSearchForCurrentTab() {
        if (!sessionSearchInput) {
            return Promise.resolve();
        }

        const query = normalizeSearchQuery(sessionSearchInput.value);
        if (query === '') {
            return clearSearchForTab(currentTab, true);
        }

        if (query.length < 2) {
            alert('Please enter at least 2 characters to search.');
            return Promise.resolve();
        }

        const state = getTabSearchState(currentTab);
        state.query = query;
        state.page = 1;
        syncSearchUiToCurrentTab();

        return searchSessions(currentTab, 1);
    }

    function editSession(sessionId) {
        const normalizedSessionId = Number(sessionId || 0);
        if (!normalizedSessionId) {
            alert('Invalid session ID.');
            return;
        }

        openCreateSessionModal(normalizedSessionId);
    }

    function renderSubscriberListLoading() {
        const body = document.getElementById('sessionMainSubscriberBody');
        if (!body) {
            return;
        }

        body.innerHTML = `
            <div class="loading-spinner" style="padding: 1.5rem 0;">
                <div class="spinner"></div>
            </div>
        `;
    }

    function renderSubscriberList(list) {
        const body = document.getElementById('sessionMainSubscriberBody');
        if (!body) {
            return;
        }

        if (!Array.isArray(list) || list.length === 0) {
            body.innerHTML = '<div class="session-main-subscriber-empty">No subscribers yet.</div>';
            return;
        }

        const rows = list.map(item => {
            const status = normalizeSubscriberDecisionStatus(item.status);
            const fullName = `${item.first_name || ''} ${item.last_name || ''}`.trim() || 'Unknown User';
            const approveDisabled = status === 'approved' ? 'disabled' : '';
            const rejectDisabled = status === 'rejected' ? 'disabled' : '';

            return `
                <div class="subscriber-row" data-subscriber-id="${item.subscriber_id}">
                    <div class="subscriber-name">${escapeHtml(fullName)}</div>
                    <div class="subscriber-row-actions">
                        <span class="subscriber-status ${status}">${formatDecisionStatus(status)}</span>
                        <button
                            type="button"
                            class="subscriber-decision-btn subscriber-approve-btn"
                            data-action="approve"
                            data-subscriber-id="${item.subscriber_id}"
                            ${approveDisabled}
                        >Approve</button>
                        <button
                            type="button"
                            class="subscriber-decision-btn subscriber-reject-btn"
                            data-action="reject"
                            data-subscriber-id="${item.subscriber_id}"
                            ${rejectDisabled}
                        >Reject</button>
                    </div>
                </div>
            `;
        }).join('');

        body.innerHTML = `<div class="subscriber-list">${rows}</div>`;
    }

    function applySubscriberRowState(row, status) {
        const normalizedStatus = normalizeSubscriberDecisionStatus(status);
        const statusBadge = row.querySelector('.subscriber-status');
        const approveButton = row.querySelector('.subscriber-approve-btn');
        const rejectButton = row.querySelector('.subscriber-reject-btn');

        if (statusBadge) {
            statusBadge.classList.remove('pending', 'approved', 'rejected');
            statusBadge.classList.add(normalizedStatus);
            statusBadge.textContent = formatDecisionStatus(normalizedStatus);
        }

        if (approveButton) {
            approveButton.disabled = normalizedStatus === 'approved';
        }

        if (rejectButton) {
            rejectButton.disabled = normalizedStatus === 'rejected';
        }
    }

    function fetchSubscriberList(sessionId) {
        return fetch(`${BASE_URL}/api?controller=SessionController&action=getSubscriberList&session_id=${sessionId}`, {
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(result => {
                if (!result.success) {
                    throw new Error(result.message || result.error || 'Failed to load subscriber list.');
                }
                renderSubscriberList(result.data || []);
            })
            .catch(error => {
                console.error('Subscriber list error:', error);
                const body = document.getElementById('sessionMainSubscriberBody');
                if (body) {
                    body.innerHTML = `<div class="session-main-subscriber-empty" style="color:#fc8181;">${escapeHtml(error.message || 'Failed to load subscriber list.')}</div>`;
                }
            });
    }

    function sendSubscriberDecision(button) {
        if (!sessionForSubscribersId) {
            return;
        }

        const actionType = button.getAttribute('data-action');
        const subscriberId = Number(button.getAttribute('data-subscriber-id') || 0);
        if (!subscriberId || (actionType !== 'approve' && actionType !== 'reject')) {
            alert('Invalid subscriber action.');
            return;
        }

        const row = button.closest('.subscriber-row');
        if (!row) {
            return;
        }

        const approveButton = row.querySelector('.subscriber-approve-btn');
        const rejectButton = row.querySelector('.subscriber-reject-btn');
        if (approveButton) {
            approveButton.disabled = true;
        }
        if (rejectButton) {
            rejectButton.disabled = true;
        }

        const formData = new FormData();
        formData.append('session_id', String(sessionForSubscribersId));
        formData.append('subscriber_id', String(subscriberId));

        const endpointAction = actionType === 'approve'
            ? 'approveSubscriberAction'
            : 'rejectSubscriberAction';

        fetch(`${BASE_URL}/api?controller=SessionController&action=${endpointAction}`, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(response => response.json())
            .then(result => {
                if (!result.success) {
                    throw new Error(result.message || result.error || 'Failed to update subscriber status.');
                }

                const nextStatus = (result.data && result.data.status) ? result.data.status : (actionType === 'approve' ? 'approved' : 'rejected');
                applySubscriberRowState(row, nextStatus);

                if (result.data && typeof result.data.sub_count !== 'undefined') {
                    updateSubscriberCountForSession(sessionForSubscribersId, result.data.sub_count);
                }
            })
            .catch(error => {
                console.error('Subscriber decision error:', error);
                alert(error.message || 'Failed to update subscriber status.');
                if (approveButton) {
                    approveButton.disabled = false;
                }
                if (rejectButton) {
                    rejectButton.disabled = false;
                }
            });
    }

    function deleteSession(sessionId, triggerButton) {
        const normalizedSessionId = Number(sessionId || 0);
        if (!normalizedSessionId) {
            alert('Invalid session ID.');
            return;
        }

        if (!window.confirm('Are you sure you want to delete this session?')) {
            return;
        }

        if (triggerButton) {
            triggerButton.disabled = true;
            triggerButton.textContent = 'Deleting...';
        }

        fetch(`${BASE_URL}/api?controller=SessionController&action=deleteSession`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id=${normalizedSessionId}`
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Failed to delete session');
                }

                sessionCache.delete(normalizedSessionId);
                closeSessionModal();
                loadTabData('my-sessions', 1);

                if (document.getElementById('all-sessions-container').innerHTML) {
                    loadTabData('all-sessions', 1);
                }
            })
            .catch(error => {
                console.error('Error deleting session:', error);
                alert(error.message || 'Failed to delete session. Please try again.');
            })
            .finally(() => {
                if (triggerButton) {
                    triggerButton.disabled = false;
                    triggerButton.textContent = 'Delete';
                }
            });
    }

    function handleSubscribeAction(subscribeButton) {
        const sessionId = Number(subscribeButton.getAttribute('data-session-id') || 0);
        if (!sessionId) {
            alert('Invalid session ID.');
            return;
        }

        const currentStatus = normalizeSubscriptionStatus(subscribeButton.getAttribute('data-status'));
        const isSubscribed = currentStatus !== 'none';
        const action = isSubscribed ? 'unsubscribeAction' : 'subscribeAction';

        subscribeButton.disabled = true;

        const formData = new FormData();
        formData.append('session_id', String(sessionId));

        fetch(`${BASE_URL}/api?controller=SessionController&action=${action}`, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(response => response.json())
            .then(result => {
                if (!result.success) {
                    throw new Error(result.message || result.error || 'Failed to update subscription.');
                }

                const state = result.data || {};
                const nextStatus = normalizeSubscriptionStatus(state.subscription_status || (isSubscribed ? 'none' : 'approved'));
                updateSubscribeButtonState(subscribeButton, nextStatus);

                const cachedSession = getSessionFromCache(sessionId) || { id: sessionId };
                cachedSession.subscription_status = nextStatus;
                cachedSession.is_subscribed = nextStatus === 'none' ? 0 : 1;

                if (typeof state.can_join !== 'undefined') {
                    cachedSession.can_join = Number(state.can_join || 0);
                }

                if (typeof state.sub_count !== 'undefined') {
                    cachedSession.sub_count = Math.max(0, Number(state.sub_count || 0));
                }

                upsertSessionCache(cachedSession);

                if (typeof state.sub_count !== 'undefined') {
                    updateSubscriberCountForSession(sessionId, state.sub_count);
                }

                if (activeSessionId === sessionId) {
                    openSessionModal(cachedSession);
                }
            })
            .catch(error => {
                console.error('Subscription error:', error);
                alert(error.message || 'Failed to update subscription. Please try again.');
            })
            .finally(() => {
                subscribeButton.disabled = false;
            });
    }

    document.querySelectorAll('.peer-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            switchTab(this.dataset.tab);
        });
    });

    if (sessionSearchForm) {
        sessionSearchForm.addEventListener('submit', function (event) {
            event.preventDefault();
            submitSearchForCurrentTab();
        });
    }

    if (sessionSearchInput) {
        sessionSearchInput.addEventListener('input', function () {
            updateSearchControls();
        });
    }

    if (sessionSearchClearButton) {
        sessionSearchClearButton.addEventListener('click', function () {
            if (sessionSearchInput) {
                sessionSearchInput.value = '';
            }
            updateSearchControls();
            clearSearchForTab(currentTab, true);
        });
    }

    document.getElementById('my-sessions-load-more').addEventListener('click', function () {
        loadTabData('my-sessions', getNextPageForTab('my-sessions'));
    });

    document.getElementById('all-sessions-load-more').addEventListener('click', function () {
        loadTabData('all-sessions', getNextPageForTab('all-sessions'));
    });

    document.addEventListener('submit', function (event) {
        const modalForm = event.target.closest('.js-modal-create-session-form');
        if (!modalForm) {
            return;
        }

        event.preventDefault();
        submitCreateSessionModalForm(modalForm);
    });

    document.addEventListener('click', function (event) {
        const sessionModal = document.getElementById('sessionMainModal');
        const createModal = document.getElementById('createSessionModal');

        if (event.target === createModal) {
            closeCreateSessionModal();
            return;
        }

        if (event.target === sessionModal) {
            closeSessionModal();
            return;
        }

        const createModalCloseButton = event.target.closest('[data-action="close-create-session-modal"]');
        if (createModalCloseButton) {
            closeCreateSessionModal();
            return;
        }

        const createModalOpenTrigger = event.target.closest('[data-action="open-create-session-modal"]');
        if (createModalOpenTrigger) {
            event.preventDefault();
            const sessionId = Number(createModalOpenTrigger.getAttribute('data-session-id') || 0);
            openCreateSessionModal(sessionId);
            return;
        }

        const closeButton = event.target.closest('[data-modal-action="close-session-modal"]');
        if (closeButton) {
            closeSessionModal();
            return;
        }

        const card = event.target.closest('.session-card');
        if (card && !event.target.closest('a, button, input, select, textarea, label')) {
            const sessionId = Number(card.getAttribute('data-session-id') || 0);
            openSessionModalById(sessionId).catch(error => {
                console.error('Failed to open session modal:', error);
                showPeerLearningError(error.message || 'Unable to open this session view.');
            });
            return;
        }

        const modalActionButton = event.target.closest('[data-modal-action]');
        if (modalActionButton) {
            const action = modalActionButton.getAttribute('data-modal-action');
            const sessionId = Number(modalActionButton.getAttribute('data-session-id') || activeSessionId || 0);

            if (action === 'edit-session') {
                editSession(sessionId);
                return;
            }

            if (action === 'delete-session') {
                deleteSession(sessionId, modalActionButton);
                return;
            }

            if (action === 'refresh-subscriber-list') {
                sessionForSubscribersId = sessionId;
                renderSubscriberListLoading();
                fetchSubscriberList(sessionId);
                return;
            }
        }

        const decisionButton = event.target.closest('.subscriber-decision-btn');
        if (decisionButton) {
            sendSubscriberDecision(decisionButton);
            return;
        }

        const subscribeButton = event.target.closest('.session-subscribe-btn');
        if (subscribeButton) {
            handleSubscribeAction(subscribeButton);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (createSessionModalElement && createSessionModalElement.classList.contains('show')) {
                closeCreateSessionModal();
                return;
            }

            if (sessionMainModalElement && sessionMainModalElement.classList.contains('show')) {
                closeSessionModal();
            }
            return;
        }

        const targetCard = event.target && event.target.classList ? event.target : null;
        if (!targetCard || !targetCard.classList.contains('session-card')) {
            return;
        }

        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        event.preventDefault();
        const sessionId = Number(targetCard.getAttribute('data-session-id') || 0);
        openSessionModalById(sessionId).catch(error => {
            console.error('Failed to open session modal:', error);
            showPeerLearningError(error.message || 'Unable to open this session view.');
        });
    });

    window.addEventListener('load', function () {
        syncSearchUiToCurrentTab();
        if (window._peerLearningDeepLinkHandled) {
            return;
        }

        window._peerLearningDeepLinkHandled = true;

        const initialTab = normalizeTabName(pageParams.tab);
        switchTab(initialTab)
            .then(() => {
                const deepLinkSessionId = normalizePositiveInt(pageParams.session_id);
                if (!deepLinkSessionId) {
                    return;
                }

                return openSessionModalById(deepLinkSessionId).catch(error => {
                    console.error('Deep-link session open failed:', error);
                    showPeerLearningError(error.message || 'Unable to open this session from the link.');
                });
            })
            .catch(error => {
                console.error('Peer-learning initialization failed:', error);
                showPeerLearningError('Failed to initialize Peer Learning. Please refresh and try again.');
            });
    });
</script>