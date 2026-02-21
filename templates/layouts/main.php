<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title ?? 'CANDIDv2') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="/">CANDIDv2</a></h1>
            <nav>
                <a href="/">Home</a>
                <a href="/browse">Browse</a>
                <a href="#" onclick="openSearchModal(); return false;">Search</a>
                <?php if ($auth->check()): ?>
                    <a href="#" onclick="openUploadModal(window.currentCategoryId); return false;">Upload</a>
                <?php endif; ?>
                <div class="profile-dropdown">
                    <div class="profile-trigger">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <circle cx="12" cy="10" r="3"></circle>
                            <path d="M6.168 18.849A4 4 0 0 1 10 16h4a4 4 0 0 1 3.834 2.855"></path>
                        </svg>
                    </div>
                    <div class="profile-menu">
                        <?php if ($auth->check()): ?>
                            <a href="/profile/<?= h($user['id'] ?? '') ?>">Profile</a>
                            <a href="/logout">Logout</a>
                            <?php if ($auth->isAdmin()): ?>
                                <div class="profile-menu-section">Admin</div>
                                <a href="/admin/users" class="profile-menu-indented">Users</a>
                                <a href="/admin/trash" class="profile-menu-indented">Trash</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="/login">Login</a>
                            <a href="/register">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <?php
            $messages = flash();
            foreach ($messages as $type => $data):
                $message = is_array($data) ? $data['message'] : $data;
                $details = is_array($data) ? ($data['details'] ?? []) : [];
            ?>
                <div class="flash flash-<?= h($type) ?>">
                    <div class="flash-content">
                        <?= h($message) ?>
                        <?php if (!empty($details)): ?>
                            <button type="button" class="flash-toggle" onclick="this.parentElement.parentElement.classList.toggle('expanded')">Details</button>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($details)): ?>
                        <div class="flash-details">
                            <?php foreach ($details as $detail): ?>
                                <div><?= h($detail) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?= $content ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>CANDIDv2 &copy; <?= date('Y') ?></p>
        </div>
    </footer>

    <script src="/assets/js/modal.js"></script>
    <script src="/assets/js/lightbox.js"></script>

    <?php if ($auth->check()): ?>
    <!-- Image Edit Modal -->
    <div class="modal-overlay" id="image-edit-modal">
        <div class="modal modal-wide">
            <div class="modal-header">
                <h3>Edit Image</h3>
                <button type="button" class="modal-close" id="image-edit-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="image-edit-loading">Loading...</div>
                <div id="image-edit-form" class="modal-form" style="display: none;">
                    <div class="form-group">
                        <label for="edit-modal-description">Description</label>
                        <input type="text" id="edit-modal-description">
                    </div>

                    <div class="form-group">
                        <label for="edit-modal-date-taken">Date Taken</label>
                        <input type="date" id="edit-modal-date-taken">
                    </div>

                    <div class="form-group">
                        <label for="edit-modal-category">Category</label>
                        <select id="edit-modal-category">
                            <option value="">-- None --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit-modal-people">Tag People</label>
                        <select id="edit-modal-people" multiple size="4">
                        </select>
                        <small style="color: var(--gray-600);">Hold Ctrl/Cmd to select multiple</small>
                    </div>

                    <div class="form-group">
                        <label for="edit-modal-access">Access Level</label>
                        <select id="edit-modal-access">
                            <option value="0">Public</option>
                            <option value="1">Registered Users</option>
                            <option value="5">Admin Only</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="edit-modal-private" value="1">
                            Private (only visible to owner)
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="image-edit-cancel">Cancel</button>
                <button type="button" class="btn" id="image-edit-save">Save</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const modal = document.getElementById('image-edit-modal');
        if (!modal) return;

        const formContainer = document.getElementById('image-edit-form');
        const loading = document.getElementById('image-edit-loading');
        const closeBtn = document.getElementById('image-edit-close');
        const cancelBtn = document.getElementById('image-edit-cancel');
        const saveBtn = document.getElementById('image-edit-save');

        let currentImageId = null;
        let currentCsrfToken = null;

        function openModal(imageId) {
            currentImageId = imageId;
            modal.classList.add('active');
            formContainer.style.display = 'none';
            loading.style.display = 'block';
            loading.textContent = 'Loading...';

            fetch(`/image/${imageId}/json`)
                .then(response => {
                    if (!response.ok) throw new Error('Failed to load image data');
                    return response.json();
                })
                .then(data => {
                    populateForm(data);
                    currentCsrfToken = data.csrfToken;
                    loading.style.display = 'none';
                    formContainer.style.display = 'block';
                })
                .catch(error => {
                    loading.textContent = 'Error loading image data';
                    console.error(error);
                });
        }

        function populateForm(data) {
            document.getElementById('edit-modal-description').value = data.image.descr || '';
            document.getElementById('edit-modal-date-taken').value = data.image.date_taken || '';
            document.getElementById('edit-modal-access').value = data.image.access;
            document.getElementById('edit-modal-private').checked = data.image.private === 1;

            // Populate categories
            const categorySelect = document.getElementById('edit-modal-category');
            categorySelect.innerHTML = '<option value="">-- None --</option>';
            data.categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.name;
                if (cat.id === data.image.category_id) option.selected = true;
                categorySelect.appendChild(option);
            });

            // Populate people
            const peopleSelect = document.getElementById('edit-modal-people');
            peopleSelect.innerHTML = '';
            data.users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.name;
                if (data.taggedPeopleIds.includes(user.id)) option.selected = true;
                peopleSelect.appendChild(option);
            });
        }

        function closeModal() {
            modal.classList.remove('active');
            currentImageId = null;
            currentCsrfToken = null;
        }

        function saveImage() {
            if (!currentImageId) return;

            const formData = new FormData();
            formData.append('csrf_token', currentCsrfToken);
            formData.append('description', document.getElementById('edit-modal-description').value);
            formData.append('date_taken', document.getElementById('edit-modal-date-taken').value);
            formData.append('category_id', document.getElementById('edit-modal-category').value);
            formData.append('access', document.getElementById('edit-modal-access').value);
            if (document.getElementById('edit-modal-private').checked) {
                formData.append('private', '1');
            }

            // Add selected people
            const peopleSelect = document.getElementById('edit-modal-people');
            for (const option of peopleSelect.selectedOptions) {
                formData.append('people[]', option.value);
            }

            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            fetch(`/image/${currentImageId}/edit-json`, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Failed to save');
                return response.json();
            })
            .then(data => {
                // Update the image card caption if visible
                const card = document.querySelector(`.image-card[data-image-id="${currentImageId}"]`);
                if (card) {
                    const captionLink = card.querySelector('.caption > a');
                    if (captionLink) {
                        captionLink.textContent = data.image.descr || '';
                    }
                }
                closeModal();
            })
            .catch(error => {
                Modal.alert('Error', 'Failed to save changes. Please try again.');
                console.error(error);
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save';
            });
        }

        // Event listeners
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        saveBtn.addEventListener('click', saveImage);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (!modal.classList.contains('active')) return;

            if (e.key === 'Escape') {
                closeModal();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                saveImage();
            }
        });

        // Global function to open modal
        window.openImageEditModal = openModal;
    })();
    </script>

    <!-- Category Edit Modal -->
    <div class="modal-overlay" id="category-edit-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Edit Category</h3>
                <button type="button" class="modal-close" id="category-edit-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="category-edit-loading">Loading...</div>
                <div id="category-edit-form" class="modal-form" style="display: none;">
                    <div class="form-group">
                        <label for="edit-category-name">Name</label>
                        <input type="text" id="edit-category-name" required>
                    </div>

                    <div class="form-group">
                        <label for="edit-category-descr">Description</label>
                        <textarea id="edit-category-descr" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Visibility</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="edit-category-public" value="y" checked> Public
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="edit-category-public" value="n"> Private
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit-category-sort">Default Sort Order</label>
                        <select id="edit-category-sort">
                            <option value="">Date Taken (default)</option>
                            <option value="date_taken">Date Taken</option>
                            <option value="date_added">Date Added</option>
                            <option value="description">Description</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="category-edit-cancel">Cancel</button>
                <button type="button" class="btn" id="category-edit-save">Save</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const modal = document.getElementById('category-edit-modal');
        if (!modal) return;

        const formContainer = document.getElementById('category-edit-form');
        const loading = document.getElementById('category-edit-loading');
        const closeBtn = document.getElementById('category-edit-close');
        const cancelBtn = document.getElementById('category-edit-cancel');
        const saveBtn = document.getElementById('category-edit-save');

        let currentCategoryId = null;
        let currentCsrfToken = null;

        function openModal(categoryId) {
            currentCategoryId = categoryId;
            modal.classList.add('active');
            formContainer.style.display = 'none';
            loading.style.display = 'block';
            loading.textContent = 'Loading...';

            fetch(`/category/${categoryId}/json`)
                .then(response => {
                    if (!response.ok) throw new Error('Failed to load category data');
                    return response.json();
                })
                .then(data => {
                    populateForm(data);
                    currentCsrfToken = data.csrfToken;
                    loading.style.display = 'none';
                    formContainer.style.display = 'block';
                    document.getElementById('edit-category-name').focus();
                })
                .catch(error => {
                    loading.textContent = 'Error loading category data';
                    console.error(error);
                });
        }

        function populateForm(data) {
            document.getElementById('edit-category-name').value = data.category.name || '';
            document.getElementById('edit-category-descr').value = data.category.descr || '';
            document.getElementById('edit-category-sort').value = data.category.sort_by || '';

            // Set public/private radio
            const publicValue = data.category.public || 'y';
            document.querySelectorAll('input[name="edit-category-public"]').forEach(radio => {
                radio.checked = radio.value === publicValue;
            });
        }

        function closeModal() {
            modal.classList.remove('active');
            currentCategoryId = null;
            currentCsrfToken = null;
        }

        function saveCategory() {
            if (!currentCategoryId) return;

            const name = document.getElementById('edit-category-name').value.trim();
            if (!name) {
                Modal.alert('Error', 'Category name is required.');
                return;
            }

            const formData = new FormData();
            formData.append('csrf_token', currentCsrfToken);
            formData.append('name', name);
            formData.append('descr', document.getElementById('edit-category-descr').value);
            formData.append('sort_by', document.getElementById('edit-category-sort').value);

            const publicRadio = document.querySelector('input[name="edit-category-public"]:checked');
            formData.append('public', publicRadio ? publicRadio.value : 'y');

            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            fetch(`/category/${currentCategoryId}/edit-json`, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Failed to save');
                return response.json();
            })
            .then(data => {
                // Update page elements if they exist
                const breadcrumbLink = document.querySelector('.breadcrumb span:last-child a:first-child');
                if (breadcrumbLink) {
                    breadcrumbLink.textContent = data.category.name;
                }
                const pageTitle = document.querySelector('.flex-between h2, h2.mb-2');
                if (pageTitle && pageTitle.textContent.trim() !== 'Edit Category') {
                    pageTitle.textContent = data.category.name;
                }
                const descPara = document.querySelector('.mb-2 + p.mb-2, h2 + p.mb-2');
                if (descPara && data.category.descr) {
                    descPara.textContent = data.category.descr;
                }
                closeModal();
            })
            .catch(error => {
                Modal.alert('Error', 'Failed to save changes. Please try again.');
                console.error(error);
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save';
            });
        }

        // Event listeners
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        saveBtn.addEventListener('click', saveCategory);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (!modal.classList.contains('active')) return;

            if (e.key === 'Escape') {
                closeModal();
            } else if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                saveCategory();
            }
        });

        // Global function to open modal
        window.openCategoryEditModal = openModal;
    })();
    </script>

    <!-- Category Add Modal -->
    <div class="modal-overlay" id="category-add-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Add Category</h3>
                <button type="button" class="modal-close" id="category-add-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="category-add-loading">Loading...</div>
                <div id="category-add-form" class="modal-form" style="display: none;">
                    <div class="form-group">
                        <label for="add-category-name">Name</label>
                        <input type="text" id="add-category-name" required>
                    </div>

                    <div class="form-group">
                        <label for="add-category-descr">Description</label>
                        <textarea id="add-category-descr" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="add-category-parent">Parent Category</label>
                        <select id="add-category-parent">
                            <option value="">-- None (Root Category) --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Visibility</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="add-category-public" value="y" checked> Public
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="add-category-public" value="n"> Private
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="category-add-cancel">Cancel</button>
                <button type="button" class="btn" id="category-add-save">Create</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const modal = document.getElementById('category-add-modal');
        if (!modal) return;

        const formContainer = document.getElementById('category-add-form');
        const loading = document.getElementById('category-add-loading');
        const closeBtn = document.getElementById('category-add-close');
        const cancelBtn = document.getElementById('category-add-cancel');
        const saveBtn = document.getElementById('category-add-save');
        const parentSelect = document.getElementById('add-category-parent');

        let currentCsrfToken = null;
        let defaultParentId = null;

        function openModal(parentId) {
            defaultParentId = parentId || null;
            modal.classList.add('active');
            formContainer.style.display = 'none';
            loading.style.display = 'block';
            loading.textContent = 'Loading...';

            // Reset form
            document.getElementById('add-category-name').value = '';
            document.getElementById('add-category-descr').value = '';
            document.querySelectorAll('input[name="add-category-public"]').forEach(radio => {
                radio.checked = radio.value === 'y';
            });

            fetch('/category/list-json')
                .then(response => {
                    if (!response.ok) throw new Error('Failed to load categories');
                    return response.json();
                })
                .then(data => {
                    populateParentSelect(data.categories);
                    currentCsrfToken = data.csrfToken;
                    loading.style.display = 'none';
                    formContainer.style.display = 'block';
                    document.getElementById('add-category-name').focus();
                })
                .catch(error => {
                    loading.textContent = 'Error loading data';
                    console.error(error);
                });
        }

        function populateParentSelect(categories) {
            parentSelect.innerHTML = '<option value="">-- None (Root Category) --</option>';
            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = (cat.indent || '') + cat.name;
                if (defaultParentId && cat.id == defaultParentId) {
                    option.selected = true;
                }
                parentSelect.appendChild(option);
            });
        }

        function closeModal() {
            modal.classList.remove('active');
            currentCsrfToken = null;
            defaultParentId = null;
        }

        function createCategory() {
            const name = document.getElementById('add-category-name').value.trim();
            if (!name) {
                Modal.alert('Error', 'Category name is required.');
                return;
            }

            const formData = new FormData();
            formData.append('csrf_token', currentCsrfToken);
            formData.append('name', name);
            formData.append('descr', document.getElementById('add-category-descr').value);
            formData.append('parent_id', parentSelect.value);

            const publicRadio = document.querySelector('input[name="add-category-public"]:checked');
            formData.append('public', publicRadio ? publicRadio.value : 'y');

            saveBtn.disabled = true;
            saveBtn.textContent = 'Creating...';

            fetch('/category/add-json', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Failed to create category');
                return response.json();
            })
            .then(data => {
                closeModal();
                // Redirect to the new category
                window.location.href = '/browse/' + data.id;
            })
            .catch(error => {
                Modal.alert('Error', 'Failed to create category. Please try again.');
                console.error(error);
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Create';
            });
        }

        // Event listeners
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        saveBtn.addEventListener('click', createCategory);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (!modal.classList.contains('active')) return;

            if (e.key === 'Escape') {
                closeModal();
            } else if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                createCategory();
            }
        });

        // Global function to open modal
        window.openCategoryAddModal = openModal;
    })();
    </script>

    <!-- Upload Modal -->
    <div class="modal-overlay" id="upload-modal">
        <div class="modal modal-wide">
            <div class="modal-header">
                <h3>Upload Images</h3>
                <button type="button" class="modal-close" id="upload-modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="upload-modal-loading">Loading...</div>
                <div id="upload-modal-form" class="modal-form" style="display: none;">
                    <div class="form-group">
                        <label for="upload-modal-files">Image Files</label>
                        <input type="file" id="upload-modal-files" accept="image/jpeg,image/png,image/gif,image/heic,image/heif,.heic,.heif" multiple required>
                        <small>You can select multiple images at once</small>
                    </div>

                    <div class="form-group">
                        <label for="upload-modal-category">Category</label>
                        <select id="upload-modal-category">
                            <option value="">-- Select Category --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="radio-label">
                            <input type="checkbox" id="upload-modal-use-filename" value="1">
                            Use filename as description
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="radio-label">
                            <input type="checkbox" id="upload-modal-private" value="1">
                            Private (only visible to you)
                        </label>
                    </div>

                    <p class="text-muted small">Date taken will be extracted from EXIF data when available.</p>

                    <div id="upload-modal-progress" class="upload-progress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill" id="upload-modal-progress-fill"></div>
                        </div>
                        <div class="progress-text" id="upload-modal-progress-text">Uploading... 0%</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="upload-modal-cancel">Cancel</button>
                <button type="button" class="btn" id="upload-modal-submit">Upload</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const modal = document.getElementById('upload-modal');
        if (!modal) return;

        const formContainer = document.getElementById('upload-modal-form');
        const loading = document.getElementById('upload-modal-loading');
        const closeBtn = document.getElementById('upload-modal-close');
        const cancelBtn = document.getElementById('upload-modal-cancel');
        const submitBtn = document.getElementById('upload-modal-submit');
        const categorySelect = document.getElementById('upload-modal-category');
        const fileInput = document.getElementById('upload-modal-files');
        const progressContainer = document.getElementById('upload-modal-progress');
        const progressFill = document.getElementById('upload-modal-progress-fill');
        const progressText = document.getElementById('upload-modal-progress-text');

        let currentCsrfToken = null;
        let defaultCategoryId = null;
        let isUploading = false;

        function openModal(categoryId) {
            defaultCategoryId = categoryId || null;
            modal.classList.add('active');
            formContainer.style.display = 'none';
            loading.style.display = 'block';
            loading.textContent = 'Loading...';
            progressContainer.style.display = 'none';
            isUploading = false;

            // Reset form
            fileInput.value = '';
            document.getElementById('upload-modal-use-filename').checked = false;
            document.getElementById('upload-modal-private').checked = false;
            submitBtn.disabled = false;
            submitBtn.textContent = 'Upload';

            fetch('/category/list-json')
                .then(response => {
                    if (!response.ok) throw new Error('Failed to load categories');
                    return response.json();
                })
                .then(data => {
                    populateCategorySelect(data.categories);
                    currentCsrfToken = data.csrfToken;
                    loading.style.display = 'none';
                    formContainer.style.display = 'block';
                })
                .catch(error => {
                    loading.textContent = 'Error loading data';
                    console.error(error);
                });
        }

        function populateCategorySelect(categories) {
            categorySelect.innerHTML = '<option value="">-- Select Category --</option>';
            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = (cat.indent || '') + cat.name;
                if (defaultCategoryId && cat.id == defaultCategoryId) {
                    option.selected = true;
                }
                categorySelect.appendChild(option);
            });
        }

        function closeModal() {
            if (isUploading) {
                if (!confirm('Upload in progress. Are you sure you want to cancel?')) {
                    return;
                }
            }
            modal.classList.remove('active');
            currentCsrfToken = null;
            defaultCategoryId = null;
            isUploading = false;
        }

        function uploadImages() {
            const files = fileInput.files;
            if (!files || files.length === 0) {
                Modal.alert('Error', 'Please select at least one image.');
                return;
            }

            const formData = new FormData();
            formData.append('csrf_token', currentCsrfToken);
            formData.append('category_id', categorySelect.value);
            if (document.getElementById('upload-modal-use-filename').checked) {
                formData.append('use_filename', '1');
            }
            if (document.getElementById('upload-modal-private').checked) {
                formData.append('private', '1');
            }
            for (let i = 0; i < files.length; i++) {
                formData.append('images[]', files[i]);
            }

            isUploading = true;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Uploading...';
            cancelBtn.disabled = true;
            closeBtn.style.display = 'none';
            progressContainer.style.display = 'block';
            progressFill.style.width = '0%';
            progressFill.classList.remove('processing');

            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressFill.style.width = percent + '%';
                    progressText.innerHTML = 'Uploading... ' + percent + '%';
                }
            });

            xhr.upload.addEventListener('load', function() {
                progressFill.classList.add('processing');
                progressText.innerHTML = '<span class="spinner"></span>Processing images...';
                submitBtn.textContent = 'Processing...';
            });

            xhr.addEventListener('load', function() {
                isUploading = false;
                if (xhr.status === 200) {
                    // Redirect to category or reload
                    const catId = categorySelect.value;
                    if (catId) {
                        window.location.href = '/browse/' + catId;
                    } else {
                        window.location.reload();
                    }
                } else {
                    progressFill.classList.remove('processing');
                    progressText.innerHTML = 'Upload failed. Please try again.';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Upload';
                    cancelBtn.disabled = false;
                    closeBtn.style.display = '';
                }
            });

            xhr.addEventListener('error', function() {
                isUploading = false;
                progressText.textContent = 'Upload failed. Please try again.';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Upload';
                cancelBtn.disabled = false;
                closeBtn.style.display = '';
            });

            xhr.open('POST', '/image/add');
            xhr.send(formData);
        }

        // Event listeners
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        submitBtn.addEventListener('click', uploadImages);

        modal.addEventListener('click', (e) => {
            if (e.target === modal && !isUploading) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (!modal.classList.contains('active')) return;

            if (e.key === 'Escape' && !isUploading) {
                closeModal();
            }
        });

        // Global function to open modal
        window.openUploadModal = openModal;
    })();
    </script>

    <!-- Bulk Edit Modal -->
    <div class="modal-overlay" id="bulk-edit-modal">
        <div class="modal modal-wide">
            <div class="modal-header">
                <h3 id="bulk-edit-title">Edit Images</h3>
                <button type="button" class="modal-close" id="bulk-edit-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="bulk-edit-loading">Loading...</div>
                <div id="bulk-edit-content" style="display: none;">
                    <div class="bulk-edit-thumbs" id="bulk-edit-thumbs"></div>

                    <p class="text-muted small mb-1">Leave fields blank to keep existing values.</p>

                    <div class="modal-form bulk-edit-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="bulk-edit-description">Description</label>
                                <input type="text" id="bulk-edit-description" placeholder="Leave blank to keep existing">
                            </div>
                            <div class="form-group">
                                <label for="bulk-edit-date">Date Taken</label>
                                <input type="date" id="bulk-edit-date">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="bulk-edit-category">Category</label>
                                <select id="bulk-edit-category">
                                    <option value="">-- No change --</option>
                                    <option value="0">-- Remove from category --</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="bulk-edit-access">Access Level</label>
                                <select id="bulk-edit-access">
                                    <option value="">-- No change --</option>
                                    <option value="0">Public</option>
                                    <option value="1">Registered Users</option>
                                    <option value="5">Admin Only</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Private</label>
                            <div class="radio-group-inline">
                                <label class="radio-label"><input type="radio" name="bulk-edit-private" value="" checked> No change</label>
                                <label class="radio-label"><input type="radio" name="bulk-edit-private" value="1"> Yes</label>
                                <label class="radio-label"><input type="radio" name="bulk-edit-private" value="0"> No</label>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="bulk-edit-add-people">Add People Tags</label>
                                <select id="bulk-edit-add-people" multiple size="3"></select>
                            </div>
                            <div class="form-group">
                                <label for="bulk-edit-remove-people">Remove People Tags</label>
                                <select id="bulk-edit-remove-people" multiple size="3"></select>
                            </div>
                        </div>
                        <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="bulk-edit-cancel">Cancel</button>
                <button type="button" class="btn" id="bulk-edit-save">Save</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const modal = document.getElementById('bulk-edit-modal');
        if (!modal) return;

        const content = document.getElementById('bulk-edit-content');
        const loading = document.getElementById('bulk-edit-loading');
        const thumbsContainer = document.getElementById('bulk-edit-thumbs');
        const titleEl = document.getElementById('bulk-edit-title');
        const closeBtn = document.getElementById('bulk-edit-close');
        const cancelBtn = document.getElementById('bulk-edit-cancel');
        const saveBtn = document.getElementById('bulk-edit-save');
        const categorySelect = document.getElementById('bulk-edit-category');
        const addPeopleSelect = document.getElementById('bulk-edit-add-people');
        const removePeopleSelect = document.getElementById('bulk-edit-remove-people');

        let currentImageIds = [];
        let currentCsrfToken = null;

        function openModal(imageIds) {
            if (!imageIds || imageIds.length === 0) {
                Modal.alert('No Selection', 'Please select at least one image.');
                return;
            }

            currentImageIds = imageIds;
            modal.classList.add('active');
            content.style.display = 'none';
            loading.style.display = 'block';
            loading.textContent = 'Loading...';

            // Reset form
            document.getElementById('bulk-edit-description').value = '';
            document.getElementById('bulk-edit-date').value = '';
            document.getElementById('bulk-edit-access').value = '';
            document.querySelectorAll('input[name="bulk-edit-private"]').forEach(r => r.checked = r.value === '');
            addPeopleSelect.selectedIndex = -1;
            removePeopleSelect.selectedIndex = -1;

            titleEl.textContent = 'Edit ' + imageIds.length + ' Image' + (imageIds.length !== 1 ? 's' : '');

            // Show thumbnails
            thumbsContainer.innerHTML = imageIds.map(id =>
                `<img src="/image/${id}/show?size=thumb" alt="" loading="lazy">`
            ).join('');

            // Load categories and users
            fetch('/category/list-json')
                .then(response => response.json())
                .then(data => {
                    populateCategorySelect(data.categories);
                    populatePeopleSelects(data.users || []);
                    currentCsrfToken = data.csrfToken;
                    loading.style.display = 'none';
                    content.style.display = 'block';
                })
                .catch(error => {
                    loading.textContent = 'Error loading data';
                    console.error(error);
                });
        }

        function populateCategorySelect(categories) {
            categorySelect.innerHTML = '<option value="">-- No change --</option><option value="0">-- Remove from category --</option>';
            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = (cat.indent || '') + cat.name;
                categorySelect.appendChild(option);
            });
        }

        function populatePeopleSelects(users) {
            addPeopleSelect.innerHTML = '';
            removePeopleSelect.innerHTML = '';
            users.forEach(user => {
                const name = [user.fname, user.lname].filter(Boolean).join(' ') || user.username;

                const addOption = document.createElement('option');
                addOption.value = user.id;
                addOption.textContent = name;
                addPeopleSelect.appendChild(addOption);

                const removeOption = document.createElement('option');
                removeOption.value = user.id;
                removeOption.textContent = name;
                removePeopleSelect.appendChild(removeOption);
            });
        }

        function closeModal() {
            modal.classList.remove('active');
            currentImageIds = [];
            currentCsrfToken = null;
        }

        function saveChanges() {
            const formData = new FormData();
            formData.append('csrf_token', currentCsrfToken);

            currentImageIds.forEach(id => formData.append('image_ids[]', id));

            const description = document.getElementById('bulk-edit-description').value;
            if (description) formData.append('description', description);

            const dateTaken = document.getElementById('bulk-edit-date').value;
            if (dateTaken) formData.append('date_taken', dateTaken);

            const category = categorySelect.value;
            if (category !== '') formData.append('category_id', category);

            const access = document.getElementById('bulk-edit-access').value;
            if (access !== '') formData.append('access', access);

            const privateRadio = document.querySelector('input[name="bulk-edit-private"]:checked');
            if (privateRadio && privateRadio.value !== '') formData.append('private', privateRadio.value);

            // Add people to tag
            for (const option of addPeopleSelect.selectedOptions) {
                formData.append('add_people[]', option.value);
            }

            // People to remove tags
            for (const option of removePeopleSelect.selectedOptions) {
                formData.append('remove_people[]', option.value);
            }

            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            fetch('/image/bulk/edit-json', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Failed to save');
                return response.json();
            })
            .then(data => {
                closeModal();
                // Reload to show changes
                window.location.reload();
            })
            .catch(error => {
                Modal.alert('Error', 'Failed to save changes. Please try again.');
                console.error(error);
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save';
            });
        }

        // Event listeners
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        saveBtn.addEventListener('click', saveChanges);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (!modal.classList.contains('active')) return;
            if (e.key === 'Escape') closeModal();
        });

        // Global function to open modal
        window.openBulkEditModal = openModal;
    })();
    </script>
    <?php endif; ?>

    <!-- Search Modal (available to all users) -->
    <div class="modal-overlay" id="search-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Search Images</h3>
                <button type="button" class="modal-close" id="search-modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="search-modal-loading">Loading...</div>
                <form id="search-modal-form" class="modal-form" method="GET" action="/search/results" style="display: none;">
                    <div class="form-group">
                        <label for="search-keywords">Keywords</label>
                        <input type="text" id="search-keywords" name="keywords" placeholder="Search descriptions...">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="search-start-date">From Date</label>
                            <input type="date" id="search-start-date" name="start_date">
                        </div>
                        <div class="form-group">
                            <label for="search-end-date">To Date</label>
                            <input type="date" id="search-end-date" name="end_date">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="search-photographer">Photographer</label>
                            <select id="search-photographer" name="photographer">
                                <option value="">-- Any --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="search-category">Category</label>
                            <select id="search-category" name="category_id">
                                <option value="">-- Any --</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="search-people">People Tagged</label>
                        <select id="search-people" name="person_id[]" multiple size="3">
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                    </div>

                    <div class="form-group">
                        <label for="search-sort">Sort By</label>
                        <select id="search-sort" name="sort">
                            <option value="date_taken">Date Taken</option>
                            <option value="added">Date Added</option>
                            <option value="views">Most Viewed</option>
                            <option value="random">Random</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="search-modal-cancel">Cancel</button>
                <button type="button" class="btn" id="search-modal-submit">Search</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const modal = document.getElementById('search-modal');
        if (!modal) return;

        const form = document.getElementById('search-modal-form');
        const loading = document.getElementById('search-modal-loading');
        const closeBtn = document.getElementById('search-modal-close');
        const cancelBtn = document.getElementById('search-modal-cancel');
        const submitBtn = document.getElementById('search-modal-submit');

        const photographerSelect = document.getElementById('search-photographer');
        const categorySelect = document.getElementById('search-category');
        const peopleSelect = document.getElementById('search-people');

        let optionsLoaded = false;

        function openModal() {
            modal.classList.add('active');

            if (!optionsLoaded) {
                form.style.display = 'none';
                loading.style.display = 'block';
                loading.textContent = 'Loading...';

                fetch('/search/options-json')
                    .then(response => response.json())
                    .then(data => {
                        populateOptions(data);
                        optionsLoaded = true;
                        loading.style.display = 'none';
                        form.style.display = 'block';
                        document.getElementById('search-keywords').focus();
                    })
                    .catch(error => {
                        loading.textContent = 'Error loading options';
                        console.error(error);
                    });
            } else {
                loading.style.display = 'none';
                form.style.display = 'block';
                document.getElementById('search-keywords').focus();
            }
        }

        function populateOptions(data) {
            // Photographers
            photographerSelect.innerHTML = '<option value="">-- Any --</option>';
            (data.photographers || []).forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = [p.fname, p.lname].filter(Boolean).join(' ');
                photographerSelect.appendChild(option);
            });

            // Categories
            categorySelect.innerHTML = '<option value="">-- Any --</option>';
            (data.categories || []).forEach(c => {
                const option = document.createElement('option');
                option.value = c.id;
                option.textContent = c.name;
                categorySelect.appendChild(option);
            });

            // Tagged people
            peopleSelect.innerHTML = '';
            (data.taggedPeople || []).forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = [p.fname, p.lname].filter(Boolean).join(' ');
                peopleSelect.appendChild(option);
            });
        }

        function closeModal() {
            modal.classList.remove('active');
        }

        function submitSearch() {
            form.submit();
        }

        // Event listeners
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        submitBtn.addEventListener('click', submitSearch);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (!modal.classList.contains('active')) return;

            if (e.key === 'Escape') {
                closeModal();
            } else if (e.key === 'Enter' && e.target.tagName !== 'SELECT') {
                e.preventDefault();
                submitSearch();
            }
        });

        // Global function to open modal
        window.openSearchModal = openModal;
    })();
    </script>
</body>
</html>
