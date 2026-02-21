<h2 class="mb-2">Edit Image</h2>

<div class="image-edit-layout">
    <div class="image-edit-main">
        <div class="image-preview">
            <img src="/image/<?= h($image['id']) ?>/show" alt="<?= h($image['descr']) ?>" id="preview-image">
            <div class="rotate-buttons mt-1">
                <form method="POST" action="/image/<?= h($image['id']) ?>/rotate" class="inline">
                    <?= csrf_field() ?>
                    <?php if ($returnCategory ?? null): ?>
                        <input type="hidden" name="return_category" value="<?= h($returnCategory) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="direction" value="cw">
                    <button type="submit" class="btn btn-secondary btn-sm" title="Rotate">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="image-edit-sidebar">
        <div class="card">
            <h3 class="mb-1">Details</h3>
            <form method="POST" action="/image/<?= h($image['id']) ?>/edit">
                <?= csrf_field() ?>
                <?php if ($returnCategory ?? null): ?>
                    <input type="hidden" name="return_category" value="<?= h($returnCategory) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" value="<?= h($image['descr']) ?>">
                </div>

                <div class="form-group">
                    <label for="date_taken">Date Taken</label>
                    <input type="date" id="date_taken" name="date_taken" value="<?= h($image['date_taken']) ?>">
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">-- None --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= h($cat['id']) ?>" <?= in_array($cat['id'], $imageCategoryIds) ? 'selected' : '' ?>>
                                <?= h($cat['indent'] . $cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="people">Tag People</label>
                    <select id="people" name="people[]" multiple size="5">
                        <?php foreach ($users as $u):
                            $displayName = trim(($u['fname'] ?? '') . ' ' . ($u['lname'] ?? '')) ?: $u['username'];
                        ?>
                            <option value="<?= h($u['id']) ?>" <?= in_array($u['id'], $taggedPeopleIds) ? 'selected' : '' ?>>
                                <?= h($displayName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Hold Ctrl/Cmd to select multiple people</small>
                </div>

                <div class="form-group">
                    <label for="access">Access Level</label>
                    <select id="access" name="access">
                        <option value="0" <?= (int)$image['access'] === 0 ? 'selected' : '' ?>>Public</option>
                        <option value="1" <?= (int)$image['access'] === 1 ? 'selected' : '' ?>>Registered Users</option>
                        <option value="5" <?= (int)$image['access'] >= 5 ? 'selected' : '' ?>>Admin Only</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="private" value="1" <?= $image['private'] ? 'checked' : '' ?>>
                        Private (only visible to owner)
                    </label>
                </div>

                <div class="btn-group">
                    <div class="btn-group-left">
                        <button type="submit" class="btn">Save</button>
                        <a href="/image/<?= h($image['id']) ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                    <div class="btn-group-right">
                        <button type="submit" form="delete-form" class="btn btn-text-danger">Delete</button>
                    </div>
                </div>
            </form>

            <form id="delete-form" method="POST" action="/image/<?= h($image['id']) ?>/delete"
                      data-confirm="Are you sure you want to delete this image? This cannot be undone."
                      data-confirm-title="Delete Image"
                      data-confirm-danger>
                <?= csrf_field() ?>
                <?php if ($returnCategory ?? null): ?>
                    <input type="hidden" name="return_category" value="<?= h($returnCategory) ?>">
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
