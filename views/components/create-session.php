<?php
// create-session.php
// Display form for creating a new study session
// Variables passed: $user, $userData, $errors (if form submitted with errors), $formData (retained data)
?>
    <link rel="stylesheet" href="/unihelper/views/css/style.css">
    <link rel="stylesheet" href="/unihelper/views/css/dashboard.css">
<style>
    /* Create Session Form Styles */
    .create-session-container {
        max-width: 800px;
        margin: 0 auto;
        background: rgba(8, 8, 8, 0.5);
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
        border: 1px solid rgba(164, 109, 255, 0.2);
        border-radius: 0.5rem;
        background: rgba(8, 8, 8, 0.7);
        color: var(--text);
        font-family: inherit;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .form-input:focus,
    .form-textarea:focus,
    .form-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 10px rgba(0, 170, 255, 0.3);
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

<div class="create-session-container">
    <div class="session-form-header">
        <h1 class="session-form-title">Create Study Session</h1>
        <p class="session-form-subtitle">Set up a new study session for peer learning</p>
    </div>

    <form class="session-form" id="createSessionForm" method="POST" action="/UniHelper/api?controller=SessionController&action=store">
        <!-- Title Field -->
        <div class="form-group">
            <label for="title" class="form-label required">Session Title</label>
            <input 
                type="text" 
                id="title" 
                name="title" 
                class="form-input" 
                placeholder="e.g., Data Structures Study Group"
                maxlength="255"
                value="<?= htmlspecialchars($formData['title'] ?? '') ?>"
            >
            <?php if (isset($errors) && isset($errors['title'])): ?>
                <div class="form-error show"><?= htmlspecialchars($errors['title']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Subject Field -->
        <div class="form-group">
            <label for="subject" class="form-label required">Subject</label>
            <div class="form-select-group">
                <select id="subject" name="subject" class="form-select">
                    <option value="" disabled <?= empty($formData['subject'] ?? '') ? 'selected' : '' ?>>Select Subject</option>
                    <option value="Computer Science" <?= ($formData['subject'] ?? '') === 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                    <option value="Mathematics" <?= ($formData['subject'] ?? '') === 'Mathematics' ? 'selected' : '' ?>>Mathematics</option>
                    <option value="Physical Science" <?= ($formData['subject'] ?? '') === 'Physical Science' ? 'selected' : '' ?>>Physical Science</option>
                </select>
                <span class="form-select-arrow">▼</span>
            </div>
            <?php if (isset($errors) && isset($errors['subject'])): ?>
                <div class="form-error show"><?= htmlspecialchars($errors['subject']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Description Field -->
        <div class="form-group">
            <label for="description" class="form-label required">Description</label>
            <textarea 
                id="description" 
                name="description" 
                class="form-textarea" 
                placeholder="Provide details about the session, topics to be covered, level of difficulty, etc."
            ><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
            <?php if (isset($errors) && isset($errors['description'])): ?>
                <div class="form-error show"><?= htmlspecialchars($errors['description']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Date, Time, Duration Row -->
        <div class="form-row-three">
            <!-- Date Field -->
            <div class="form-group">
                <label for="date" class="form-label required">Date</label>
                <input 
                    type="date" 
                    id="date" 
                    name="date" 
                    class="form-input"
                    value="<?= htmlspecialchars($formData['date'] ?? '') ?>"
                >
                <?php if (isset($errors) && isset($errors['date'])): ?>
                    <div class="form-error show"><?= htmlspecialchars($errors['date']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Time Field -->
            <div class="form-group">
                <label for="time" class="form-label required">Time</label>
                <input 
                    type="time" 
                    id="time" 
                    name="time" 
                    class="form-input"
                    value="<?= htmlspecialchars($formData['time'] ?? '') ?>"
                >
                <?php if (isset($errors) && isset($errors['time'])): ?>
                    <div class="form-error show"><?= htmlspecialchars($errors['time']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Duration Field -->
            <div class="form-group">
                <label for="duration" class="form-label required">Duration (hrs)</label>
                <input 
                    type="number" 
                    id="duration" 
                    name="duration" 
                    class="form-input" 
                    placeholder="e.g., 2"
                    min="0.5"
                    step="0.5"
                    value="<?= htmlspecialchars($formData['duration'] ?? '') ?>"
                >
                <?php if (isset($errors) && isset($errors['duration'])): ?>
                    <div class="form-error show"><?= htmlspecialchars($errors['duration']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Session Link Field -->
        <div class="form-group">
            <label for="sessionLink" class="form-label">Session Link</label>
            <input 
                type="url" 
                id="sessionLink" 
                name="sessionLink" 
                class="form-input" 
                placeholder="e.g., https://meet.google.com/xyz or https://zoom.us/j/123456"
                value="<?= htmlspecialchars($formData['sessionLink'] ?? '') ?>"
            >
            <p class="helper-text">Link to video conference (Zoom, Google Meet, etc.)</p>
        </div>

        <!-- Audience Field -->
        <div class="form-group">
            <label class="form-label required">Audience</label>
            <div class="radio-group">
                <div class="radio-item">
                    <input 
                        type="radio" 
                        id="my-university" 
                        name="audience" 
                        value="my_university"
                        <?= ($formData['audience'] ?? '') === 'my_university' ? 'checked' : '' ?>
                    >
                    <label for="my-university">My University</label>
                </div>
                <div class="radio-item">
                    <input 
                        type="radio" 
                        id="all-universities" 
                        name="audience" 
                        value="all_universities"
                        <?= ($formData['audience'] ?? '') === 'all_universities' ? 'checked' : '' ?>
                    >
                    <label for="all-universities">All Universities</label>
                </div>
            </div>
            <?php if (isset($errors) && isset($errors['audience'])): ?>
                <div class="form-error show"><?= htmlspecialchars($errors['audience']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Tags Field -->
        <div class="form-group">
            <label for="tags" class="form-label">Tags</label>
            <input 
                type="text" 
                id="tags" 
                name="tags" 
                class="form-input" 
                placeholder="e.g., exam prep, beginner friendly, advanced (separate with commas)"
                value="<?= htmlspecialchars($formData['tags'] ?? '') ?>"
            >
            <p class="helper-text">Add tags to help others find your session</p>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="button" class="btn btn-cancel" onclick="window.history.back()">Cancel</button>
            <button type="submit" class="btn btn-create">Create Session</button>
        </div>
    </form>
</div>

<script>
    // Form Validation and Error Handling
    const createSessionForm = document.getElementById('createSessionForm');

    // Function to add error class to input and show error message
    function showFieldError(fieldName, errorMessage) {
        const field = document.getElementById(fieldName);
        const errorElement = field.nextElementSibling;
        
        if (field) {
            field.classList.add('error');
            if (errorElement && errorElement.classList.contains('form-error')) {
                errorElement.classList.add('show');
            }
        }
    }

    // Function to remove error class from input and hide error message
    function clearFieldError(fieldName) {
        const field = document.getElementById(fieldName);
        
        if (field) {
            field.classList.remove('error');
            const errorElement = field.nextElementSibling;
            if (errorElement && errorElement.classList.contains('form-error')) {
                errorElement.classList.remove('show');
            }
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
</script>