<?php
use app\models\WishlistModel;

$items = [];
if (isset($_SESSION['user_id'])) {
    $model = new WishlistModel();
    $items = $model->getUserWishlist($_SESSION['user_id']);
}
?>

<div class="degree-programs-page">
    <div class="search-results">
        <?php if (empty($items)): ?>
            <div class="no-search-message">
                <div class="no-search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </div>
                <h3>Your wishlist is empty</h3>
                <p>Browse degree programs and click the heart to add them to your wishlist.</p>
            </div>
        <?php else: ?>
            <?php foreach ($items as $program): ?>
                <div class="degree-program-card" data-program-id="<?= htmlspecialchars($program['program_id']) ?>">
                    <div class="card-header">
                        <h3><?= htmlspecialchars($program['program_name']) ?></h3>
                        <p><?= htmlspecialchars($program['university_name']) ?></p>
                    </div>

                    <div class="card-body">
                        <p class="faculty-name"><?= htmlspecialchars($program['major_name']) ?></p>

                        <div class="degree-metrics">
                            <div class="cutoff-info">Stream: <strong><?= htmlspecialchars($program['stream']) ?></strong></div>
                            <div class="unicode-info">Unicode: <strong><?= htmlspecialchars($program['unicode'] ?? '') ?></strong></div>
                        </div>

                        <div class="degree-tags">
                            <span class="tag"><?= htmlspecialchars($program['stream']) ?> Stream</span>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="footer-details">
                            <span>Major: <strong><?= htmlspecialchars($program['major_name']) ?></strong></span>
                            <span>Unicode: <strong><?= htmlspecialchars($program['unicode'] ?? '') ?></strong></span>
                        </div>
                        <div class="card-actions">
                            <button class="icon-btn wishlist-btn in-wishlist" onclick="toggleWishlist(<?= (int)$program['program_id'] ?>)" aria-label="Remove from Wishlist">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                            </button>
                            <button class="icon-btn" aria-label="View Details">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
