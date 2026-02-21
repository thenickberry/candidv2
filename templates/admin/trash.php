<div class="card">
    <div class="flex-between mb-2">
        <h2>Trash</h2>
        <?php if (!empty($deletedCategories) || !empty($deletedImages)): ?>
            <form method="POST" action="/admin/trash/empty" class="inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger"
                        data-confirm="Permanently delete ALL items in trash? This cannot be undone."
                        data-confirm-title="Empty Trash"
                        data-confirm-danger>
                    Empty Trash
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($deletedCategories) && empty($deletedImages)): ?>
        <p class="text-muted">Trash is empty.</p>
    <?php else: ?>
        <!-- Tab navigation -->
        <div class="trash-tabs mb-2">
            <button type="button" class="trash-tab active" data-tab="categories">
                Categories (<?= count($deletedCategories) ?>)
            </button>
            <button type="button" class="trash-tab" data-tab="images">
                Images (<?= count($deletedImages) ?>)
            </button>
        </div>

        <!-- Categories tab -->
        <div class="trash-panel active" id="panel-categories">
            <?php if (empty($deletedCategories)): ?>
                <p class="text-muted">No deleted categories.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 30px;">
                                <input type="checkbox" id="selectAllCategories" form="trashForm" title="Select all">
                            </th>
                            <th>Name</th>
                            <th class="hide-mobile">Owner</th>
                            <th class="hide-mobile">Deleted</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deletedCategories as $category): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="categories[]" value="<?= h($category['id']) ?>" class="category-checkbox" form="trashForm">
                                </td>
                                <td><?= h($category['name']) ?></td>
                                <td class="hide-mobile">
                                    <?= h(trim(($category['fname'] ?? '') . ' ' . ($category['lname'] ?? '')) ?: ($category['username'] ?? 'Unknown')) ?>
                                </td>
                                <td class="hide-mobile small text-muted">
                                    <?= date('M j, Y g:ia', strtotime($category['deleted_at'])) ?>
                                    <?php if (!empty($category['deleted_by_fname'])): ?>
                                        <br>by <?= h($category['deleted_by_fname'] . ' ' . $category['deleted_by_lname']) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center action-icons">
                                    <form method="POST" action="/admin/trash/categories/<?= h($category['id']) ?>/restore" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="action-icon" title="Restore">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="1 4 1 10 7 10"></polyline>
                                                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                            </svg>
                                        </button>
                                    </form>
                                    <form method="POST" action="/admin/trash/categories/<?= h($category['id']) ?>/purge" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit"
                                                    data-confirm="Permanently delete this category?"
                                                    data-confirm-title="Delete Category"
                                                    data-confirm-danger
                                                    class="action-icon action-icon-danger" title="Delete permanently">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Images tab -->
        <div class="trash-panel" id="panel-images">
            <?php if (empty($deletedImages)): ?>
                <p class="text-muted">No deleted images.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 30px;">
                                <input type="checkbox" id="selectAllImages" form="trashForm" title="Select all">
                            </th>
                            <th style="width: 60px;">Thumb</th>
                            <th>Description</th>
                            <th class="hide-mobile">Owner</th>
                            <th class="hide-mobile">Deleted</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deletedImages as $image): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="images[]" value="<?= h($image['id']) ?>" class="image-checkbox" form="trashForm">
                                </td>
                                <td>
                                    <img src="/image/<?= h($image['id']) ?>/show?size=thumb" alt="" loading="lazy" style="max-width: 50px; max-height: 50px; object-fit: cover;">
                                </td>
                                <td><?= h($image['descr'] ?: '(No description)') ?></td>
                                <td class="hide-mobile">
                                    <?= h(trim(($image['fname'] ?? '') . ' ' . ($image['lname'] ?? '')) ?: ($image['username'] ?? 'Unknown')) ?>
                                </td>
                                <td class="hide-mobile small text-muted">
                                    <?= date('M j, Y g:ia', strtotime($image['deleted_at'])) ?>
                                    <?php if (!empty($image['deleted_by_fname'])): ?>
                                        <br>by <?= h($image['deleted_by_fname'] . ' ' . $image['deleted_by_lname']) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center action-icons">
                                    <form method="POST" action="/admin/trash/images/<?= h($image['id']) ?>/restore" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="action-icon" title="Restore">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="1 4 1 10 7 10"></polyline>
                                                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                            </svg>
                                        </button>
                                    </form>
                                    <form method="POST" action="/admin/trash/images/<?= h($image['id']) ?>/purge" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit"
                                                    data-confirm="Permanently delete this image?"
                                                    data-confirm-title="Delete Image"
                                                    data-confirm-danger
                                                    class="action-icon action-icon-danger" title="Delete permanently">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Bulk actions form (defined separately, checkboxes use form attribute) -->
        <?php if (!empty($deletedCategories) || !empty($deletedImages)): ?>
            <form method="POST" action="/admin/trash/bulk" id="trashForm">
                <?= csrf_field() ?>
                <div class="bulk-actions mt-2">
                    <span class="text-muted small">With selected:</span>
                    <button type="submit" name="action" value="restore" class="btn btn-secondary">Restore</button>
                    <button type="submit" name="action" value="purge" class="btn btn-danger"
                            data-confirm="Permanently delete selected items?"
                            data-confirm-title="Delete Items"
                            data-confirm-danger>Delete Permanently</button>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
    .trash-tabs {
        display: flex;
        gap: 0;
        border-bottom: 2px solid var(--gray-200);
    }

    .trash-tab {
        background: none;
        border: none;
        padding: 0.75rem 1.5rem;
        cursor: pointer;
        color: var(--gray-600);
        font-weight: 500;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: color 0.2s, border-color 0.2s;
    }

    .trash-tab:hover {
        color: var(--gray-800);
    }

    .trash-tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    .trash-panel {
        display: none;
    }

    .trash-panel.active {
        display: block;
    }

    .bulk-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding-top: 1rem;
        border-top: 1px solid var(--gray-200);
    }

    .action-icon:hover {
        background: none;
    }
</style>

<script>
    // Tab switching
    document.querySelectorAll('.trash-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.trash-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.trash-panel').forEach(p => p.classList.remove('active'));

            tab.classList.add('active');
            document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
        });
    });

    // Select all categories
    document.getElementById('selectAllCategories')?.addEventListener('change', function() {
        document.querySelectorAll('.category-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
    });

    // Select all images
    document.getElementById('selectAllImages')?.addEventListener('change', function() {
        document.querySelectorAll('.image-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
    });
</script>
