<div class="card">
    <h2 class="mb-2"><?= h($displayName) ?></h2>

    <table class="meta-table">
        <?php if ($profile['email'] && ($canEdit || $auth->isAdmin())): ?>
            <tr>
                <th>Email:</th>
                <td><?= h($profile['email']) ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <th>Member since:</th>
            <td><?= format_date($profile['created']) ?></td>
        </tr>
        <tr>
            <th>Images uploaded:</th>
            <td><?= number_format($stats['images']) ?></td>
        </tr>
        <tr>
            <th>Comments:</th>
            <td><?= number_format($stats['comments']) ?></td>
        </tr>
        <tr>
            <th>Tagged in:</th>
            <td><?= number_format($stats['tagged_in']) ?> images</td>
        </tr>
    </table>

    <?php if ($canEdit): ?>
        <p>
            <a href="/profile/edit" class="btn">Edit Profile</a>
            <a href="/profile/password" class="btn btn-secondary">Change Password</a>
        </p>
    <?php endif; ?>
</div>

<?php if (!empty($recentImages)): ?>
    <div class="card mt-2">
        <h3 class="mb-1">Recent Uploads</h3>
        <div class="image-grid">
            <?php foreach ($recentImages as $image):
                $photographer = trim(($image['fname'] ?? '') . ' ' . ($image['lname'] ?? ''));
                $canEditImage = ($currentUserId && ((int)$image['owner'] === $currentUserId || $isAdmin));
            ?>
                <div class="image-card"
                     data-date-taken="<?= h($image['date_taken'] ?? '') ?>"
                     data-photographer="<?= h($photographer) ?>"
                     data-camera="<?= h($image['camera'] ?? '') ?>"
                     data-can-edit="<?= $canEditImage ? '1' : '0' ?>">
                    <a href="/image/<?= h($image['id']) ?>">
                        <img src="/image/<?= h($image['id']) ?>/show?size=thumb" alt="<?= h($image['descr']) ?>" loading="lazy">
                    </a>
                    <div class="caption">
                        <a href="/image/<?= h($image['id']) ?>"><?= h($image['descr']) ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($taggedImages)): ?>
    <div class="card mt-2">
        <h3 class="mb-1">Tagged In</h3>
        <div class="image-grid">
            <?php foreach ($taggedImages as $image):
                $photographer = trim(($image['fname'] ?? '') . ' ' . ($image['lname'] ?? ''));
                $canEditImage = ($currentUserId && ((int)$image['owner'] === $currentUserId || $isAdmin));
            ?>
                <div class="image-card"
                     data-date-taken="<?= h($image['date_taken'] ?? '') ?>"
                     data-photographer="<?= h($photographer) ?>"
                     data-camera="<?= h($image['camera'] ?? '') ?>"
                     data-can-edit="<?= $canEditImage ? '1' : '0' ?>">
                    <a href="/image/<?= h($image['id']) ?>">
                        <img src="/image/<?= h($image['id']) ?>/show?size=thumb" alt="<?= h($image['descr']) ?>" loading="lazy">
                    </a>
                    <div class="caption">
                        <a href="/image/<?= h($image['id']) ?>"><?= h($image['descr']) ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
