<?php if (!empty($breadcrumb)): ?>
    <div class="breadcrumb">
        <a href="/browse">Categories</a>
        <?php foreach ($breadcrumb as $crumb): ?>
            <span><a href="/browse/<?= h($crumb['id']) ?>"><?= h($crumb['name']) ?></a></span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<h2 class="mb-2"><?= h($image['descr']) ?></h2>

<div class="image-view-layout">
    <div class="image-view-main">
        <div class="image-detail">
            <img src="/image/<?= h($image['id']) ?>/show" alt="<?= h($image['descr']) ?>">
        </div>
    </div>

    <div class="image-view-sidebar">
        <div class="card">
            <div class="card-header-row">
                <h3>Details</h3>
                <?php if ($canEdit): ?>
                    <?php $returnCategory = !empty($categories) ? $categories[0]['id'] : ''; ?>
                    <a href="/image/<?= h($image['id']) ?>/edit<?= $returnCategory ? '?return_category=' . h($returnCategory) : '' ?>" class="action-icon" title="Edit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
            <table class="meta-table">
                <?php if ($image['date_taken']): ?>
                    <tr>
                        <th>Date Taken:</th>
                        <td><?= format_date($image['date_taken']) ?></td>
                    </tr>
                <?php endif; ?>

                <?php if ($image['photographer_fname']): ?>
                    <tr>
                        <th>Photographer:</th>
                        <td><?= h($image['photographer_fname'] . ' ' . $image['photographer_lname']) ?></td>
                    </tr>
                <?php endif; ?>

                <?php if ($image['camera']): ?>
                    <tr>
                        <th>Camera:</th>
                        <td><?= h($image['camera']) ?></td>
                    </tr>
                <?php endif; ?>

                <tr>
                    <th>Dimensions:</th>
                    <td><?= (int) $image['width'] ?> x <?= (int) $image['height'] ?></td>
                </tr>

                <tr>
                    <th>Views:</th>
                    <td><?= number_format((int) $image['views']) ?></td>
                </tr>

                <?php if (!empty($categories)): ?>
                    <tr>
                        <th>Categories:</th>
                        <td>
                            <?php foreach ($categories as $i => $cat): ?>
                                <?php if ($i > 0) echo ', '; ?>
                                <a href="/browse/<?= h($cat['id']) ?>"><?= h($cat['name']) ?></a>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if (!empty($people)): ?>
                    <tr>
                        <th>People:</th>
                        <td>
                            <?php foreach ($people as $i => $person): ?>
                                <?php if ($i > 0) echo ', '; ?>
                                <a href="/profile/<?= h($person['id']) ?>"><?= h($person['fname'] . ' ' . $person['lname']) ?></a>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="card mt-2">
            <h3 class="mb-1">Comments (<?= count($comments) ?>)</h3>

            <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <strong>
                            <a href="/profile/<?= h($comment['user_id']) ?>"><?= h($comment['fname'] . ' ' . $comment['lname']) ?></a>
                        </strong>
                        <small class="text-muted"><?= format_datetime($comment['stamp']) ?></small>
                        <?php if ($user && ((int)$comment['user_id'] === (int)$user['id'] || ($user['access'] ?? 0) >= 5)): ?>
                            <form method="POST" action="/comment/<?= h($comment['id']) ?>/delete" class="inline float-right">
                                <?= csrf_field() ?>
                                <input type="hidden" name="image_id" value="<?= h($image['id']) ?>">
                                <button type="submit" class="action-icon action-icon-danger"
                                                        data-confirm="Delete this comment?"
                                                        data-confirm-title="Delete Comment"
                                                        data-confirm-danger
                                                        title="Delete comment">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </form>
                        <?php endif; ?>
                        <p class="mt-1"><?= nl2br(h($comment['comment'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No comments yet.</p>
            <?php endif; ?>

            <?php if ($auth->check()): ?>
                <form method="POST" action="/comment/<?= h($image['id']) ?>/add" class="mt-2">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="comment">Add a comment</label>
                        <textarea id="comment" name="comment" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn">Post Comment</button>
                </form>
            <?php else: ?>
                <p class="mt-2"><a href="/login">Log in</a> to leave a comment.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .image-view-layout {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 1.5rem;
        align-items: start;
    }

    .image-view-main .image-detail {
        background: var(--gray-100);
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
    }

    .image-view-main .image-detail img {
        max-width: 100%;
        max-height: 80vh;
        border-radius: 4px;
    }

    .card-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .card-header-row h3 {
        margin: 0;
    }

    .image-view-sidebar .meta-table {
        margin: 0;
    }

    .image-view-sidebar .meta-table th {
        white-space: nowrap;
        padding-right: 1rem;
    }

    @media (max-width: 900px) {
        .image-view-layout {
            grid-template-columns: 1fr;
        }

        .image-view-sidebar {
            order: 2;
        }
    }
</style>
