<div class="flex-between mb-2">
    <h2>Browse Categories</h2>
    <?php if ($canAddCategory ?? false): ?>
        <a href="/category/add" class="btn">Add Category</a>
    <?php endif; ?>
</div>

<?php if (!empty($categories)): ?>
    <div class="image-grid">
        <?php foreach ($categories as $category): ?>
            <div class="card">
                <h3><a href="/browse/<?= h($category['id']) ?>"><?= h($category['name']) ?></a></h3>
                <?php if ($category['descr']): ?>
                    <p><?= h($category['descr']) ?></p>
                <?php endif; ?>
                <small><?= (int) $category['total_image_count'] ?> images</small>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <p>No categories yet.</p>
    </div>
<?php endif; ?>
