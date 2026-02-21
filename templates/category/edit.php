<div class="card card-lg">
    <h2 class="mb-2">Edit Category</h2>

    <form method="POST" action="/category/<?= h($category['id']) ?>/edit">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="name">Category Name</label>
            <input type="text" id="name" name="name" value="<?= h($category['name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="descr">Description</label>
            <textarea id="descr" name="descr" rows="3"><?= h($category['descr']) ?></textarea>
        </div>

        <div class="form-group">
            <label>
                <input type="radio" name="public" value="y" <?= $category['public'] === 'y' ? 'checked' : '' ?>> Public
            </label>
            <label class="ml-2">
                <input type="radio" name="public" value="n" <?= $category['public'] === 'n' ? 'checked' : '' ?>> Private
            </label>
        </div>

        <div class="form-group">
            <label for="sort_by">Default Sort Order</label>
            <select id="sort_by" name="sort_by">
                <option value="" <?= empty($category['sort_by']) ? 'selected' : '' ?>>Date Taken (default)</option>
                <option value="date_taken" <?= ($category['sort_by'] ?? '') === 'date_taken' ? 'selected' : '' ?>>Date Taken</option>
                <option value="date_added" <?= ($category['sort_by'] ?? '') === 'date_added' ? 'selected' : '' ?>>Date Added</option>
                <option value="description" <?= ($category['sort_by'] ?? '') === 'description' ? 'selected' : '' ?>>Description</option>
            </select>
        </div>

        <div class="btn-group">
            <div class="btn-group-left">
                <button type="submit" class="btn">Save</button>
                <a href="/browse/<?= h($category['id']) ?>" class="btn btn-secondary">Cancel</a>
            </div>
            <div class="btn-group-right">
                <button type="button" class="btn btn-text-danger" id="delete-btn">Delete</button>
            </div>
        </div>
    </form>

    <form id="delete-form" method="POST" action="/category/<?= h($category['id']) ?>/delete">
        <?= csrf_field() ?>
    </form>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="delete-modal">
    <div class="modal">
        <div class="modal-header">
            <h3>Delete Category</h3>
            <button type="button" class="modal-close" id="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong id="modal-category-name"></strong>?</p>
            <div id="modal-stats" class="modal-stats">
                <div id="modal-loading">Loading...</div>
                <ul id="modal-stats-list" style="display: none;">
                    <li id="stat-subcategories"></li>
                    <li id="stat-images"></li>
                </ul>
            </div>
            <p class="text-muted small">Items will be moved to trash and can be restored by an admin.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="modal-cancel">Cancel</button>
            <button type="button" class="btn btn-danger" id="modal-confirm">Delete</button>
        </div>
    </div>
</div>

<script>
    const categoryId = <?= (int) $category['id'] ?>;
    const categoryName = <?= json_encode($category['name']) ?>;
    const modal = document.getElementById('delete-modal');
    const deleteBtn = document.getElementById('delete-btn');
    const modalClose = document.getElementById('modal-close');
    const modalCancel = document.getElementById('modal-cancel');
    const modalConfirm = document.getElementById('modal-confirm');
    const deleteForm = document.getElementById('delete-form');

    function openModal() {
        modal.classList.add('active');
        document.getElementById('modal-category-name').textContent = categoryName;
        document.getElementById('modal-loading').style.display = 'block';
        document.getElementById('modal-stats-list').style.display = 'none';

        // Fetch deletion stats
        fetch(`/category/${categoryId}/deletion-stats`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('modal-loading').style.display = 'none';
                document.getElementById('modal-stats-list').style.display = 'block';

                const subText = data.subcategories === 0
                    ? 'No subcategories'
                    : data.subcategories === 1
                        ? '1 subcategory will be deleted'
                        : `${data.subcategories} subcategories will be deleted`;

                const imgText = data.images === 0
                    ? 'No images will be deleted'
                    : data.images === 1
                        ? '1 image will be moved to trash'
                        : `${data.images} images will be moved to trash`;

                document.getElementById('stat-subcategories').textContent = subText;
                document.getElementById('stat-images').textContent = imgText;
            })
            .catch(() => {
                document.getElementById('modal-loading').textContent = 'Failed to load details';
            });
    }

    function closeModal() {
        modal.classList.remove('active');
    }

    deleteBtn.addEventListener('click', openModal);
    modalClose.addEventListener('click', closeModal);
    modalCancel.addEventListener('click', closeModal);

    modalConfirm.addEventListener('click', () => {
        deleteForm.submit();
    });

    // Close on overlay click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
</script>
