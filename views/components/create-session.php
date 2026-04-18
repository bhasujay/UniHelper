<?php
// create-session.php
// Display form for creating a new study session
// Variables passed: $user, $userData, $errors (if form submitted with errors), $formData (retained data)

$formData = isset($formData) && is_array($formData) ? $formData : [];
$errors = isset($errors) && is_array($errors) ? $errors : [];
$isModalContext = false;

$editingSessionId = isset($editingSessionId)
    ? (int)$editingSessionId
    : (int)($formData['session_id'] ?? ($_GET['session_id'] ?? 0));
$isEditMode = isset($isEditMode)
    ? (bool)$isEditMode
    : ($editingSessionId > 0);
$formAction = '/UniHelper/api?controller=SessionController&action=' . ($isEditMode ? 'update' : 'store');
?>
    <link rel="stylesheet" href="/UniHelper/views/css/style.css">
    <link rel="stylesheet" href="/UniHelper/views/css/dashboard.css">
<style>
    /* Create Session Form Styles */
    .create-session-container {
        max-width: 800px;
        margin: 0 auto;
        background: var(--card); /* #404040 — matches dashboard card tone */
        backdrop-filter: blur(8px);
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 2.5rem;
        box-shadow: 0 4px 20px var(--glow-primary);
    }

    .session-form-header {
        margin-bottom: 2rem;
        text-align: center;
    }

    .session-form-title {
        font-size: 1.75rem;
        font-weight: 700;
        background: var(--gradient-primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.5rem;
    }

    .session-form-subtitle {
        color: var(--muted-foreground);
        font-size: 0.95rem;
    }

    .session-form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .form-label {
        font-weight: 500;
        color: var(--foreground);
        font-size: 0.95rem;
    }

    .form-label.required::after {
        content: ' *';
        color: var(--primary);
    }

    .form-input,
    .form-textarea,
    .form-select {
        padding: 0.75rem 1rem;
        border: 1px solid rgba(0, 170, 255, 0.25);
        border-radius: 0.5rem;
        background: var(--key); /* #303030 — matches dashboard key bg */
        color: var(--text);
        font-family: inherit;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        color-scheme: dark; /* ensures browser-native date/time/number widgets render visibly */
    }

    .form-input:focus,
    .form-textarea:focus,
    .form-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 10px rgba(0, 170, 255, 0.3);
    }

    /* Make native calendar, clock and spinner icons clearly visible */
    input[type="date"],
    input[type="time"],
    input[type="number"] {
        color-scheme: dark;
    }

    /* Webkit: tint the inner date/time picker icon to the accent colour */
    input[type="date"]::-webkit-calendar-picker-indicator,
    input[type="time"]::-webkit-calendar-picker-indicator {
        filter: invert(1) brightness(1.8) sepia(1) saturate(4) hue-rotate(175deg);
        cursor: pointer;
        opacity: 1;
    }

    /* Webkit: tint the number spinner arrows */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        opacity: 1;
        filter: invert(1) brightness(1.8) sepia(1) saturate(4) hue-rotate(175deg);
    }

    .form-textarea {
        resize: vertical;
        min-height: 120px;
    }

    .form-select-group {
        position: relative;
    }

    .form-select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        width: 100%;
        cursor: pointer;
        padding-right: 2.5rem;
    }

    .form-select-arrow {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: var(--primary);
        font-size: 1rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .form-row-three {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 1rem;
    }

    .form-error {
        color: #fc8181;
        font-size: 0.85rem;
        margin-top: 0.25rem;
        display: none;
    }

    .form-error.show {
        display: block;
    }

    .form-input.error,
    .form-textarea.error,
    .form-select.error {
        border-color: #fc8181;
        box-shadow: 0 0 10px rgba(252, 129, 129, 0.2);
    }

    .radio-group {
        display: flex;
        gap: 2rem;
        align-items: center;
        margin-top: 0.5rem;
    }

    .radio-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .radio-item input[type="radio"] {
        cursor: pointer;
        width: 18px;
        height: 18px;
        accent-color: var(--primary);
    }

    .radio-item label {
        cursor: pointer;
        color: var(--foreground);
        font-size: 0.95rem;
        user-select: none;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border);
    }

    .btn {
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .btn-create {
        background: var(--gradient-primary);
        color: rgb(0, 0, 0);
        flex: 1;
        max-width: 250px;
    }

    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: var(--glow-primary);
    }

    .btn-create:active {
        transform: translateY(0);
    }

    .btn-cancel {
        background: transparent;
        color: var(--foreground);
        border: 1px solid var(--border);
        flex: 1;
        max-width: 250px;
    }

    .btn-cancel:hover {
        background: rgba(164, 109, 255, 0.1);
        border-color: var(--primary);
        color: var(--primary);
    }

    .helper-text {
        font-size: 0.85rem;
        color: var(--muted-foreground);
        margin-top: 0.25rem;
    }

    @media (max-width: 768px) {
        .create-session-container {
            padding: 1.5rem;
        }

        .form-row-three {
            grid-template-columns: 1fr;
        }

        .radio-group {
            gap: 1rem;
            flex-direction: column;
            align-items: flex-start;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn-create,
        .btn-cancel {
            max-width: 100%;
        }
    }
</style>

<?php include __DIR__ . '/session-form.php'; ?>

<script>
    const BASE_URL = '/UniHelper';
    const IS_EDIT_MODE = <?= $isEditMode ? 'true' : 'false' ?>;
    const EDIT_SESSION_ID = <?= (int)$editingSessionId ?>;
    const HAS_SERVER_FORM_DATA = <?= !empty($formData) ? 'true' : 'false' ?>;

    // Form Validation and Error Handling
    const createSessionForm = document.getElementById('createSessionForm');

    // Function to add error class to input and show error message
    function showFieldError(fieldName, errorMessage) {
        const field = document.getElementById(fieldName);
        if (!field) {
            return;
        }

        const errorElement = field.nextElementSibling;
        
        field.classList.add('error');
        if (errorElement && errorElement.classList.contains('form-error')) {
            errorElement.classList.add('show');
        }
    }

    // Function to remove error class from input and hide error message
    function clearFieldError(fieldName) {
        const field = document.getElementById(fieldName);
        if (!field) {
            return;
        }
        
        field.classList.remove('error');
        const errorElement = field.nextElementSibling;
        if (errorElement && errorElement.classList.contains('form-error')) {
            errorElement.classList.remove('show');
        }
    }

    // Client-side validation on form submission
    createSessionForm.addEventListener('submit', function(e) {
        // Clear all previous errors
        ['title', 'subject', 'description', 'date', 'time', 'duration', 'audience'].forEach(field => {
            clearFieldError(field);
        });

        let hasErrors = false;
        const today = new Date().toISOString().split('T')[0];

        // Validate Title
        const title = document.getElementById('title').value.trim();
        if (!title) {
            showFieldError('title', 'Session title is required.');
            hasErrors = true;
        }

        // Validate Subject
        const subject = document.getElementById('subject').value;
        if (!subject) {
            showFieldError('subject', 'Subject is required.');
            hasErrors = true;
        }

        // Validate Description
        const description = document.getElementById('description').value.trim();
        if (!description) {
            showFieldError('description', 'Description is required.');
            hasErrors = true;
        }

        // Validate Date
        const date = document.getElementById('date').value;
        if (!date) {
            showFieldError('date', 'Date is required.');
            hasErrors = true;
        } else if (date < today) {
            showFieldError('date', 'Date must be today or in the future.');
            hasErrors = true;
        }

        // Validate Time
        const time = document.getElementById('time').value;
        if (!time) {
            showFieldError('time', 'Time is required.');
            hasErrors = true;
        }

        // Validate Duration
        const duration = document.getElementById('duration').value;
        if (!duration) {
            showFieldError('duration', 'Duration is required.');
            hasErrors = true;
        } else if (isNaN(duration) || parseFloat(duration) <= 0) {
            showFieldError('duration', 'Duration must be a positive number.');
            hasErrors = true;
        }

        // Validate Audience
        const audience = document.querySelector('input[name="audience"]:checked');
        if (!audience) {
            showFieldError('my-university', 'Please select a valid audience option.');
            hasErrors = true;
        }

        // If there are errors, prevent form submission
        if (hasErrors) {
            e.preventDefault();
        }
    });

    // Clear error when user starts typing/selecting
    ['title', 'subject', 'description', 'date', 'time', 'duration'].forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (field) {
            field.addEventListener('input', function() {
                clearFieldError(fieldName);
            });
            field.addEventListener('change', function() {
                clearFieldError(fieldName);
            });
        }
    });

    // Clear audience error when user selects an option
    document.querySelectorAll('input[name="audience"]').forEach(radio => {
        radio.addEventListener('change', function() {
            clearFieldError('my-university');
        });
    });

    function populateEditForm(session) {
        document.getElementById('title').value = session.title || '';
        document.getElementById('subject').value = session.subject || '';
        document.getElementById('description').value = session.description || '';
        document.getElementById('date').value = session.date || '';
        document.getElementById('time').value = session.time || '';
        document.getElementById('duration').value = session.duration || '';
        document.getElementById('sessionLink').value = session.session_link || '';
        document.getElementById('tags').value = session.tags || '';

        const audienceRadio = document.querySelector(`input[name="audience"][value="${session.audience}"]`);
        if (audienceRadio) {
            audienceRadio.checked = true;
        }
    }

    if (IS_EDIT_MODE && EDIT_SESSION_ID && !HAS_SERVER_FORM_DATA) {
        fetch(`${BASE_URL}/api?controller=SessionController&action=getSessionForEdit&id=${EDIT_SESSION_ID}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success || !data.data) {
                    throw new Error(data.error || 'Failed to load session for editing.');
                }
                populateEditForm(data.data);
            })
            .catch(error => {
                alert(error.message || 'Failed to load session details.');
                window.location.href = `${BASE_URL}/peer-learning`;
            });
    }
</script>