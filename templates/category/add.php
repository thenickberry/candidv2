<div class="card card-lg">
    <h2 class="mb-2">Add Category</h2>

    <form method="POST" action="/category/add">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="name">Category Name</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="descr">Description</label>
            <textarea id="descr" name="descr" rows="3"></textarea>
        </div>

        <div class="form-group">
            <label for="parent_id">Parent Category</label>
            <select id="parent_id" name="parent_id">
                <option value="">-- None (Root Category) --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= h($cat['id']) ?>"><?= h($cat['indent'] . $cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>
                <input type="radio" name="public" value="y" checked> Public
            </label>
            <label class="ml-2">
                <input type="radio" name="public" value="n"> Private
            </label>
        </div>

        <div class="btn-group">
            <div class="btn-group-left">
                <button type="submit" class="btn">Create</button>
                <a href="/browse" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
