<h2 class="mb-2">Edit <?= count($images) ?> Image<?= count($images) !== 1 ? 's' : '' ?></h2>

<div class="bulk-edit-layout">
    <div class="bulk-edit-images">
        <div class="bulk-edit-grid">
            <?php foreach ($images as $image): ?>
                <div class="bulk-edit-thumb">
                    <img src="/image/<?= h($image['id']) ?>/show?size=thumb" alt="<?= h($image['descr']) ?>" loading="lazy">
                    <div class="thumb-caption"><?= h($image['descr']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bulk-edit-sidebar">
        <div class="card">
            <h3 class="mb-1">Bulk Edit</h3>
            <p class="text-muted small mb-2">Leave fields blank to keep existing values.</p>

            <form method="POST" action="/image/bulk/edit">
                <?= csrf_field() ?>
                <input type="hidden" name="return_url" value="<?= h($returnUrl ?? '/') ?>">
                <?php foreach ($images as $image): ?>
                    <input type="hidden" name="image_ids[]" value="<?= h($image['id']) ?>">
                <?php endforeach; ?>

                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" placeholder="Leave blank to keep existing">
                </div>

                <div class="form-group">
                    <label for="date_taken">Date Taken</label>
                    <input type="date" id="date_taken" name="date_taken">
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">-- No change --</option>
                        <option value="0">-- Remove from category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= h($cat['id']) ?>">
                                <?= h($cat['indent'] . $cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="photographer">Photographer</label>
                    <select id="photographer" name="photographer">
                        <option value="">-- No change --</option>
                        <?php foreach ($users as $u):
                            $displayName = trim(($u['fname'] ?? '') . ' ' . ($u['lname'] ?? '')) ?: $u['username'];
                        ?>
                            <option value="<?= h($u['id']) ?>">
                                <?= h($displayName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="access">Access Level</label>
                    <select id="access" name="access">
                        <option value="">-- No change --</option>
                        <option value="0">Public</option>
                        <option value="1">Registered Users</option>
                        <option value="5">Admin Only</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Private</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="private" value="" checked> No change
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="private" value="1"> Yes (owner only)
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="private" value="0"> No (public)
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_people">Add People Tags</label>
                    <select id="add_people" name="add_people[]" multiple size="4">
                        <?php foreach ($users as $u):
                            $displayName = trim(($u['fname'] ?? '') . ' ' . ($u['lname'] ?? '')) ?: $u['username'];
                        ?>
                            <option value="<?= h($u['id']) ?>">
                                <?= h($displayName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Hold Ctrl/Cmd to select multiple</small>
                </div>

                <div class="form-group">
                    <label for="remove_people">Remove People Tags</label>
                    <select id="remove_people" name="remove_people[]" multiple size="4">
                        <?php foreach ($users as $u):
                            $displayName = trim(($u['fname'] ?? '') . ' ' . ($u['lname'] ?? '')) ?: $u['username'];
                        ?>
                            <option value="<?= h($u['id']) ?>">
                                <?= h($displayName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Hold Ctrl/Cmd to select multiple</small>
                </div>

                <div class="btn-group">
                    <div class="btn-group-left">
                        <button type="submit" class="btn">Save</button>
                        <a href="<?= h($returnUrl ?? '/') ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                    <div class="btn-group-right">
                        <button type="submit" form="bulkDeleteForm" class="btn btn-text-danger">Delete</button>
                    </div>
                </div>
            </form>

            <form method="POST" action="/image/bulk/delete" id="bulkDeleteForm"
                      data-confirm="Are you sure you want to delete <?= count($images) ?> image(s)? This cannot be undone."
                      data-confirm-title="Delete Images"
                      data-confirm-danger>
                <?= csrf_field() ?>
                <input type="hidden" name="return_url" value="<?= h($returnUrl ?? '/') ?>">
                <?php foreach ($images as $image): ?>
                    <input type="hidden" name="image_ids[]" value="<?= h($image['id']) ?>">
                <?php endforeach; ?>
            </form>
        </div>
    </div>
</div>
