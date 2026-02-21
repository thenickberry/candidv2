<h2 class="mb-2">Upload Images</h2>

<div class="card card-lg">
    <form method="POST" action="/image/add" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="images">Image Files</label>
            <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/gif,image/heic,image/heif,.heic,.heif" multiple required>
            <small>You can select multiple images at once</small>
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <div class="category-select-row">
                <select id="category_id" name="category_id">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= h($cat['id']) ?>" <?= ($selectedCategoryId ?? null) == $cat['id'] ? 'selected' : '' ?>><?= h($cat['indent'] . $cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-secondary btn-sm" id="new-category-btn">New</button>
            </div>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="use_filename" value="1">
                Use filename as description
            </label>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="private" value="1">
                Private (only visible to you)
            </label>
        </div>

        <p class="text-muted mb-2">
            <small>Date taken will be extracted from EXIF data when available.</small>
        </p>

        <!-- Progress indicator -->
        <div id="upload-progress" class="upload-progress" style="display: none;">
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill"></div>
            </div>
            <div class="progress-text" id="progress-text">Uploading... 0%</div>
        </div>

        <button type="submit" class="btn" id="upload-btn">Upload</button>
    </form>
</div>

<!-- New Category Modal -->
<div class="modal-overlay" id="category-modal">
    <div class="modal">
        <div class="modal-header">
            <h3>New Category</h3>
            <button type="button" class="modal-close" id="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="new-category-name">Category Name</label>
                <input type="text" id="new-category-name" required>
            </div>
            <div class="form-group">
                <label for="new-category-parent">Parent Category</label>
                <select id="new-category-parent">
                    <option value="">-- None (Root) --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= h($cat['id']) ?>"><?= h($cat['indent'] . $cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="category-error" class="text-danger small" style="display: none;"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="modal-cancel">Cancel</button>
            <button type="button" class="btn" id="modal-create">Create</button>
        </div>
    </div>
</div>

<script>
    // Upload progress handling
    const uploadForm = document.querySelector('form[action="/image/add"]');
    const uploadBtn = document.getElementById('upload-btn');
    const uploadProgress = document.getElementById('upload-progress');
    const progressFill = document.getElementById('progress-fill');
    const progressText = document.getElementById('progress-text');

    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(uploadForm);
        const xhr = new XMLHttpRequest();

        // Show progress bar
        uploadProgress.style.display = 'block';
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading...';

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressFill.style.width = percent + '%';
                progressText.innerHTML = 'Uploading... ' + percent + '%';
            }
        });

        xhr.upload.addEventListener('load', function() {
            // Upload complete, now server is processing
            progressFill.classList.add('processing');
            progressText.innerHTML = '<span class="spinner"></span>Processing images (generating thumbnails, reading metadata)...';
            uploadBtn.textContent = 'Processing...';
        });

        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                progressText.innerHTML = '<span class="spinner"></span>Finishing up...';
                // Follow redirect - the response will be HTML with a redirect or flash message
                document.open();
                document.write(xhr.responseText);
                document.close();
            } else {
                progressFill.classList.remove('processing');
                progressText.innerHTML = 'Upload failed. Please try again.';
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Upload';
            }
        });

        xhr.addEventListener('error', function() {
            progressText.textContent = 'Upload failed. Please try again.';
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Upload';
        });

        xhr.open('POST', '/image/add');
        xhr.send(formData);
    });

    // Category modal handling
    const modal = document.getElementById('category-modal');
    const newCategoryBtn = document.getElementById('new-category-btn');
    const modalClose = document.getElementById('modal-close');
    const modalCancel = document.getElementById('modal-cancel');
    const modalCreate = document.getElementById('modal-create');
    const categorySelect = document.getElementById('category_id');
    const newCategoryName = document.getElementById('new-category-name');
    const newCategoryParent = document.getElementById('new-category-parent');
    const categoryError = document.getElementById('category-error');
    const csrfToken = '<?= csrf_token() ?>';

    function openModal() {
        modal.classList.add('active');
        newCategoryName.value = '';
        // Default parent to currently selected category
        newCategoryParent.value = categorySelect.value || '';
        categoryError.style.display = 'none';
        newCategoryName.focus();
    }

    function closeModal() {
        modal.classList.remove('active');
    }

    async function createCategory() {
        const name = newCategoryName.value.trim();
        const parentId = newCategoryParent.value;

        if (!name) {
            categoryError.textContent = 'Category name is required.';
            categoryError.style.display = 'block';
            return;
        }

        modalCreate.disabled = true;
        modalCreate.textContent = 'Creating...';

        try {
            const formData = new FormData();
            formData.append('name', name);
            formData.append('parent_id', parentId);
            formData.append('csrf_token', csrfToken);

            const response = await fetch('/category/add-json', {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to create category');
            }

            // Add new option to both selects
            const indent = parentId ? getIndentForParent(parentId) + '— ' : '';
            const option = new Option(indent + data.name, data.id, true, true);
            categorySelect.add(option);

            // Also add to parent select for future categories
            const parentOption = new Option(indent + data.name, data.id);
            newCategoryParent.add(parentOption);

            closeModal();
        } catch (error) {
            categoryError.textContent = error.message;
            categoryError.style.display = 'block';
        } finally {
            modalCreate.disabled = false;
            modalCreate.textContent = 'Create';
        }
    }

    function getIndentForParent(parentId) {
        const parentOption = newCategoryParent.querySelector(`option[value="${parentId}"]`);
        if (parentOption) {
            const match = parentOption.textContent.match(/^((?:— )*)/);
            return match ? match[1] : '';
        }
        return '';
    }

    newCategoryBtn.addEventListener('click', openModal);
    modalClose.addEventListener('click', closeModal);
    modalCancel.addEventListener('click', closeModal);
    modalCreate.addEventListener('click', createCategory);

    // Close on overlay click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Close on Escape, create on Enter
    document.addEventListener('keydown', (e) => {
        if (!modal.classList.contains('active')) return;

        if (e.key === 'Escape') {
            closeModal();
        } else if (e.key === 'Enter' && document.activeElement === newCategoryName) {
            e.preventDefault();
            createCategory();
        }
    });
</script>
