<?php if (!empty($breadcrumb)): ?>
    <div class="breadcrumb">
        <a href="/browse">Categories</a>
        <?php foreach ($breadcrumb as $i => $crumb): ?>
            <?php $isLast = ($i === count($breadcrumb) - 1); ?>
            <span>
                <a href="/browse/<?= h($crumb['id']) ?>"><?= h($crumb['name']) ?></a><?php if ($isLast && ($canEdit ?? false)): ?><button type="button" onclick="openCategoryEditModal(<?= (int)$crumb['id'] ?>)" class="edit-icon" title="Edit category"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg></button><?php endif; ?>
            </span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>window.currentCategoryId = <?= (int)$category['id'] ?>;</script>

<h2 class="mb-2"><?= h($category['name']) ?></h2>

<?php if ($category['descr']): ?>
    <p class="mb-2"><?= h($category['descr']) ?></p>
<?php endif; ?>

<?php if (!empty($subcategories)): ?>
    <h3 class="mb-1">Subcategories</h3>
    <div class="image-grid mb-2">
        <?php foreach ($subcategories as $sub): ?>
            <div class="card">
                <h4><a href="/browse/<?= h($sub['id']) ?>"><?= h($sub['name']) ?></a></h4>
                <small><?= (int) $sub['total_image_count'] ?> images</small>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($images)): ?>
    <div class="mb-1" style="display: flex; justify-content: flex-end; align-items: center; gap: 1rem;">
        <form method="GET" action="/browse/<?= h($category['id']) ?>" class="flex-center" style="width: fit-content;">
            <label for="sort" class="small" style="white-space: nowrap">Sort by:</label>
            <select id="sort" name="sort" onchange="this.form.submit()" style="width: auto;">
                <option value="date_taken" <?= ($currentSort ?? 'date_taken') === 'date_taken' ? 'selected' : '' ?>>Date Taken</option>
                <option value="date_added" <?= ($currentSort ?? '') === 'date_added' ? 'selected' : '' ?>>Date Added</option>
                <option value="description" <?= ($currentSort ?? '') === 'description' ? 'selected' : '' ?>>Description</option>
            </select>
        </form>
        <?php if ($auth->check()): ?>
            <a href="#" id="toggleSelectMode" class="btn-icon" title="Select images for bulk editing">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                    <polyline points="17 8 19 10 23 6" stroke-width="2.5"></polyline>
                </svg>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($auth->check()): ?>
        <div class="bulk-actions-bar" id="bulkActionsBar" style="display: none;">
            <span id="selectionCount">0 selected</span>
            <button type="button" class="btn btn-sm btn-secondary" id="bulkEditBtn">Edit</button>
            <button type="button" class="btn btn-sm btn-secondary" id="bulkRotateBtn">Rotate</button>
            <button type="button" class="btn btn-sm btn-secondary" id="selectAllBtn">Select All</button>
            <button type="button" class="btn btn-sm btn-secondary" id="clearSelectionBtn">Clear</button>
            <button type="button" class="bulk-actions-close" id="exitSelectMode" title="Exit selection mode">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <form id="bulkRotateForm" method="POST" action="/image/bulk/rotate">
            <?= csrf_field() ?>
            <input type="hidden" name="return_url" value="/browse/<?= h($category['id']) ?>">
        </form>
    <?php endif; ?>

    <div class="image-grid" id="imageGrid">
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
                    <input type="checkbox" name="images[]" value="<?= h($image['id']) ?>"
                           class="image-select-checkbox" form="bulkRotateForm">
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

    <?php if ($auth->check()): ?>
    <script>
    (function() {
        const imageGrid = document.getElementById('imageGrid');
        const actionsBar = document.getElementById('bulkActionsBar');
        const countSpan = document.getElementById('selectionCount');
        const toggleBtn = document.getElementById('toggleSelectMode');
        let selectModeActive = false;

        function getCheckboxes() {
            return document.querySelectorAll('.image-select-checkbox');
        }

        function updateSelectionUI() {
            const selected = document.querySelectorAll('.image-select-checkbox:checked');
            countSpan.textContent = selected.length + ' selected';
            // Show actions bar if in select mode (even with 0 selected)
            actionsBar.style.display = selectModeActive ? 'flex' : 'none';
        }

        function enableSelectMode() {
            selectModeActive = true;
            imageGrid.classList.add('select-mode');
            toggleBtn.style.display = 'none';
            updateSelectionUI();
        }

        function disableSelectMode() {
            selectModeActive = false;
            imageGrid.classList.remove('select-mode');
            toggleBtn.style.display = '';
            getCheckboxes().forEach(cb => cb.checked = false);
            actionsBar.style.display = 'none';
        }

        // Toggle button
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            enableSelectMode();
        });

        // Exit select mode button
        document.getElementById('exitSelectMode').addEventListener('click', disableSelectMode);

        // Checkbox change handler (delegated)
        imageGrid.addEventListener('change', function(e) {
            if (e.target.classList.contains('image-select-checkbox')) {
                updateSelectionUI();
            }
        });

        document.getElementById('bulkEditBtn').addEventListener('click', function(e) {
            e.preventDefault();
            const selected = [...document.querySelectorAll('.image-select-checkbox:checked')];
            if (selected.length === 0) {
                Modal.alert('No Selection', 'Please select at least one image.');
                return;
            }
            const ids = selected.map(cb => cb.value);
            openBulkEditModal(ids);
        });

        document.getElementById('bulkRotateBtn').addEventListener('click', function(e) {
            e.preventDefault();
            const selected = document.querySelectorAll('.image-select-checkbox:checked');
            if (selected.length === 0) {
                Modal.alert('No Selection', 'Please select at least one image.');
                return;
            }
            Modal.confirm('Rotate Images', 'Rotate ' + selected.length + ' image(s) clockwise?', () => {
                document.getElementById('bulkRotateForm').submit();
            });
        });

        document.getElementById('clearSelectionBtn').addEventListener('click', function() {
            getCheckboxes().forEach(cb => cb.checked = false);
            updateSelectionUI();
        });

        document.getElementById('selectAllBtn').addEventListener('click', function() {
            getCheckboxes().forEach(cb => cb.checked = true);
            updateSelectionUI();
        });
    })();
    </script>
    <?php endif; ?>
<?php else: ?>
    <div class="card text-center" style="padding: 3rem 1.5rem;">
        <p style="color: var(--gray-600);">No images in this category.</p>
    </div>
<?php endif; ?>
