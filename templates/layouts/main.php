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
                <a href="/search">Search</a>
                <?php if ($auth->check()): ?>
                    <a href="/image/add">Upload</a>
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
    <?php endif; ?>
</body>
</html>
