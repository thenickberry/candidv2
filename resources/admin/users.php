<div class="card">
    <div class="flex-between mb-2">
        <h2>User Management</h2>
        <a href="/admin/users/create" class="btn">Create User</a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Username</th>
                <th class="hide-mobile">Name</th>
                <th class="hide-mobile">Email</th>
                <th class="text-center">Access</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <?= h($u['username']) ?>
                        <?php if ($u['must_change_password']): ?>
                            <span class="text-danger small">(must change password)</span>
                        <?php endif; ?>
                    </td>
                    <td class="hide-mobile"><?= h($u['fname'] . ' ' . $u['lname']) ?></td>
                    <td class="hide-mobile"><?= h($u['email']) ?></td>
                    <td class="text-center">
                        <?php
                        $accessLabels = [0 => 'Guest', 1 => 'User', 5 => 'Admin'];
                        echo $accessLabels[$u['access']] ?? $u['access'];
                        ?>
                    </td>
                    <td class="text-center action-icons">
                        <a href="/admin/users/<?= h($u['id']) ?>/edit" class="action-icon" title="Edit">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </a>
                        <?php if ((int)$u['id'] !== (int)$user['id']): ?>
                            <form method="POST" action="/admin/users/<?= h($u['id']) ?>/delete" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit"
                                                    data-confirm="Delete this user?"
                                                    data-confirm-title="Delete User"
                                                    data-confirm-danger
                                                    class="action-icon action-icon-danger" title="Delete">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                    </svg>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
