<?php
/**
 * Session form component — used inside the create/edit modal.
 * 
 * Available variables from the including context:
 *   $formData         — array of field values   (empty for new session)
 *   $errors           — array of field => error  (empty on first load)
 *   $majors           — array of [ id, name ]   (from getMajors())
 *   $isEditMode       — bool
 *   $editingSessionId — int
 *   $isModalContext   — bool
 *   $formId           — string
 *   $formClass        — string
 *   $formAction       — string
 */

$formData   = $formData   ?? [];
$errors     = $errors     ?? [];
$majors     = $majors     ?? [];
$isEditMode = $isEditMode ?? false;
$editingSessionId = $editingSessionId ?? 0;
$formId     = $formId     ?? 'sessionForm';
$formClass  = $formClass  ?? 'session-form';
$formAction = $formAction ?? '#';

// Decompose scheduled_at into date + time for inputs
$scheduledAt     = $formData['scheduled_at'] ?? '';
$scheduledDate   = '';
$scheduledTime   = '';
if (!empty($scheduledAt)) {
    $dt = new \DateTime($scheduledAt);
    $scheduledDate = $dt->format('Y-m-d');
    $scheduledTime = $dt->format('H:i');
}
?>

<form id="<?= htmlspecialchars($formId) ?>"
      class="<?= htmlspecialchars($formClass) ?>"
      method="post"
      action="<?= htmlspecialchars($formAction) ?>"
      onsubmit="return false;">

    <?php if ($isEditMode && $editingSessionId > 0): ?>
        <input type="hidden" name="session_id" value="<?= (int)$editingSessionId ?>">
    <?php endif; ?>

    <div class="session-form-grid">
        
        <!-- ========================================== -->
        <!-- LEFT COLUMN: Title, Description, Audience  -->
        <!-- ========================================== -->
        <div class="session-form-col">
            
            <!-- Title -->
            <div class="session-form-group <?= isset($errors['title']) ? 'has-error' : '' ?>">
                <label for="sf-title">Title <span class="required">*</span></label>
                <input type="text" id="sf-title" name="title" maxlength="255" required
                       value="<?= htmlspecialchars($formData['title'] ?? '') ?>"
                       placeholder="e.g. Data Structures Study Session">
                <?php if (isset($errors['title'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['title']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="session-form-group <?= isset($errors['description']) ? 'has-error' : '' ?>">
                <label for="sf-desc">Description <span class="required">*</span></label>
                <textarea id="sf-desc" name="description" rows="5" required
                          placeholder="What will you cover in this session?"><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                <?php if (isset($errors['description'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['description']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Audience -->
            <div class="session-form-group <?= isset($errors['audience']) ? 'has-error' : '' ?>">
                <label>Audience <span class="required">*</span></label>
                <div class="session-audience-radios">
                    <?php
                    $audiences = [
                        'public'          => ['label' => 'Public',          'desc' => 'Open to everyone', 'icon' => '🌐'],
                        'university_only' => ['label' => 'University',      'desc' => 'Same university students', 'icon' => '🏫'],
                        'private'         => ['label' => 'Private',         'desc' => 'Requires your approval', 'icon' => '🔒'],
                    ];
                    $currentAudience = $formData['audience'] ?? 'public';
                    foreach ($audiences as $val => $meta):
                    ?>
                        <label class="audience-option <?= $currentAudience === $val ? 'selected' : '' ?>">
                            <input type="radio" name="audience" value="<?= $val ?>"
                                   <?= $currentAudience === $val ? 'checked' : '' ?> required>
                            <span class="audience-icon"><?= $meta['icon'] ?></span>
                            <span class="audience-label"><?= $meta['label'] ?></span>
                            <span class="audience-desc"><?= $meta['desc'] ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['audience'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['audience']) ?></span>
                <?php endif; ?>
            </div>

        </div> <!-- /LEFT COLUMN -->

        <!-- ========================================== -->
        <!-- RIGHT COLUMN: Major, Date/Time, More       -->
        <!-- ========================================== -->
        <div class="session-form-col">
            
            <!-- Major (dropdown from DB) -->
            <div class="session-form-group">
                <label for="sf-major">Subject / Major</label>
                <select id="sf-major" name="major_id">
                    <option value="">— Select a major —</option>
                    <?php foreach ($majors as $major): ?>
                        <option value="<?= (int)$major['id'] ?>"
                            <?= ((int)($formData['major_id'] ?? 0) === (int)$major['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($major['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date & Time row -->
            <div class="session-form-row">
                <div class="session-form-group <?= isset($errors['date']) ? 'has-error' : '' ?>">
                    <label for="sf-date">Date <span class="required">*</span></label>
                    <input type="date" id="sf-date" name="date" required
                           value="<?= htmlspecialchars($scheduledDate) ?>"
                           min="<?= date('Y-m-d') ?>">
                    <?php if (isset($errors['date'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['date']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="session-form-group <?= isset($errors['time']) ? 'has-error' : '' ?>">
                    <label for="sf-time">Time <span class="required">*</span></label>
                    <input type="time" id="sf-time" name="time" required
                           value="<?= htmlspecialchars($scheduledTime) ?>">
                    <?php if (isset($errors['time'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['time']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Duration -->
            <div class="session-form-group <?= isset($errors['duration_minutes']) ? 'has-error' : '' ?>">
                <label for="sf-duration">Duration <span class="required">*</span></label>
                <select id="sf-duration" name="duration_minutes" required>
                    <?php
                    $durOptions = [15 => '15 min', 30 => '30 min', 45 => '45 min', 60 => '1 hour', 90 => '1.5 hours', 120 => '2 hours', 180 => '3 hours', 240 => '4 hours'];
                    $currentDuration = (int)($formData['duration_minutes'] ?? 60);
                    foreach ($durOptions as $val => $label):
                    ?>
                        <option value="<?= $val ?>" <?= $currentDuration === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['duration_minutes'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['duration_minutes']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Session Link -->
            <div class="session-form-group">
                <label for="sf-link">Session Link <span class="hint">(Zoom, Meet, etc.)</span></label>
                <input type="url" id="sf-link" name="session_link"
                       value="<?= htmlspecialchars($formData['session_link'] ?? '') ?>"
                       placeholder="https://meet.google.com/abc-defg-hij">
            </div>

            <!-- Tags -->
            <div class="session-form-group">
                <label for="sf-tags">Tags <span class="hint">(comma-separated)</span></label>
                <input type="text" id="sf-tags" name="tags"
                       value="<?= htmlspecialchars($formData['tags'] ?? '') ?>"
                       placeholder="e.g. algorithms, midterm-prep">
            </div>

        </div> <!-- /RIGHT COLUMN -->

    </div> <!-- /session-form-grid -->

    <!-- Actions -->
    <div class="session-form-actions">
        <button type="button" class="btn btn-outline js-cancel-session-form">Cancel</button>
        <button type="submit" class="btn btn-primary js-submit-session-form">
            <?= $isEditMode ? 'Save Changes' : 'Create Session' ?>
        </button>
    </div>
</form>
