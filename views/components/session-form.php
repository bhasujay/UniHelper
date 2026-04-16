<?php
$formData = isset($formData) && is_array($formData) ? $formData : [];
$errors = isset($errors) && is_array($errors) ? $errors : [];

$editingSessionId = isset($editingSessionId)
    ? (int)$editingSessionId
    : (int)($formData['session_id'] ?? 0);

$isEditMode = isset($isEditMode)
    ? (bool)$isEditMode
    : ($editingSessionId > 0);

$formAction = isset($formAction) && is_string($formAction)
    ? $formAction
    : '/UniHelper/api?controller=SessionController&action=' . ($isEditMode ? 'update' : 'store');

$isModalContext = isset($isModalContext) ? (bool)$isModalContext : false;
$formId = $isModalContext ? 'modalCreateSessionForm' : 'createSessionForm';
$formClass = $isModalContext ? 'session-form js-modal-create-session-form' : 'session-form';
?>

<div class="create-session-container">
    <div class="session-form-header">
        <h1 class="session-form-title"><?= $isEditMode ? 'Edit Study Session' : 'Create Study Session' ?></h1>
        <p class="session-form-subtitle"><?= $isEditMode ? 'Update your study session details' : 'Set up a new study session for peer learning' ?></p>
    </div>

    <?php if (isset($errors['form'])): ?>
        <div class="form-error show" style="display:block; margin-bottom: 1rem;"><?= htmlspecialchars($errors['form']) ?></div>
    <?php endif; ?>

    <form class="<?= htmlspecialchars($formClass) ?>" id="<?= htmlspecialchars($formId) ?>" method="POST" action="<?= htmlspecialchars($formAction) ?>">
        <?php if ($isEditMode): ?>
            <input type="hidden" name="session_id" id="session_id" value="<?= (int)$editingSessionId ?>">
        <?php endif; ?>

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
            <?php if (isset($errors['title'])): ?>
                <div class="form-error show"><?= htmlspecialchars($errors['title']) ?></div>
            <?php endif; ?>
        </div>

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
            <?php if (isset($errors['subject'])): ?>
                <div class="form-error show"><?= htmlspecialchars($errors['subject']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="description" class="form-label required">Description</label>
            <textarea
                id="description"
                name="description"
                class="form-textarea"
                placeholder="Provide details about the session, topics to be covered, level of difficulty, etc."
            ><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
            <?php if (isset($errors['description'])): ?>
                <div class="form-error show"><?= htmlspecialchars($errors['description']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row-three">
            <div class="form-group">
                <label for="date" class="form-label required">Date</label>
                <input
                    type="date"
                    id="date"
                    name="date"
                    class="form-input"
                    value="<?= htmlspecialchars($formData['date'] ?? '') ?>"
                >
                <?php if (isset($errors['date'])): ?>
                    <div class="form-error show"><?= htmlspecialchars($errors['date']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="time" class="form-label required">Time</label>
                <input
                    type="time"
                    id="time"
                    name="time"
                    class="form-input"
                    value="<?= htmlspecialchars($formData['time'] ?? '') ?>"
                >
                <?php if (isset($errors['time'])): ?>
                    <div class="form-error show"><?= htmlspecialchars($errors['time']) ?></div>
                <?php endif; ?>
            </div>

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
                <?php if (isset($errors['duration'])): ?>
                    <div class="form-error show"><?= htmlspecialchars($errors['duration']) ?></div>
                <?php endif; ?>
            </div>
        </div>

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
                <div class="radio-item">
                    <input
                        type="radio"
                        id="private"
                        name="audience"
                        value="private"
                        <?= ($formData['audience'] ?? '') === 'private' ? 'checked' : '' ?>
                    >
                    <label for="private">Private</label>
                </div>
            </div>
            <?php if (isset($errors['audience'])): ?>
                <div class="form-error show"><?= htmlspecialchars($errors['audience']) ?></div>
            <?php endif; ?>
        </div>

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

        <div class="form-actions">
            <?php if ($isModalContext): ?>
                <button type="button" class="btn btn-cancel" data-action="close-create-session-modal">Cancel</button>
            <?php else: ?>
                <button type="button" class="btn btn-cancel" onclick="window.history.back()">Cancel</button>
            <?php endif; ?>
            <button type="submit" class="btn btn-create"><?= $isEditMode ? 'Update Session' : 'Create Session' ?></button>
        </div>
    </form>
</div>
