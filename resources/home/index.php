<?php if (!empty($images)): ?>
    <h2 class="mb-1">Recent Images</h2>
    <div class="image-grid">
        <?php foreach ($images as $image):
            $photographer = trim(($image['fname'] ?? '') . ' ' . ($image['lname'] ?? ''));
            $canEditImage = ($currentUserId && ((int)$image['owner'] === $currentUserId || $isAdmin));
        ?>
            <div class="image-card"
                 data-image-id="<?= h($image['id']) ?>"
                 data-date-taken="<?= h($image['date_taken'] ?? '') ?>"
                 data-photographer="<?= h($photographer) ?>"
                 data-camera="<?= h($image['camera'] ?? '') ?>"
                 data-can-edit="<?= $canEditImage ? '1' : '0' ?>">
                <?php if ($canEditImage): ?>
                    <button type="button" class="image-card-edit" onclick="openImageEditModal(<?= (int)$image['id'] ?>)" title="Edit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                <?php endif; ?>
                <a href="/image/<?= h($image['id']) ?>">
                    <img src="/image/<?= h($image['id']) ?>/show?size=thumb" alt="<?= h($image['descr']) ?>" loading="lazy">
                </a>
                <div class="caption">
                    <a href="/image/<?= h($image['id']) ?>"><?= h($image['descr']) ?></a>
                    <div class="caption-meta">
                        <small class="caption-left"><?= $photographer ? h($photographer) : '' ?></small>
                        <small class="caption-right"><?= $image['date_taken'] ? format_date($image['date_taken']) : '' ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <?php if ($auth->check()): ?>
            <p>No images yet. <a href="#" onclick="openUploadModal(); return false;">Upload one!</a></p>
        <?php else: ?>
            <p>No images yet.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>
