<div class="card card-md">
    <h2 class="mb-2">Edit User: <?= h($editUser['username']) ?></h2>

    <form method="POST" action="/admin/users/<?= h($editUser['id']) ?>/edit">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" value="<?= h($editUser['username']) ?>" disabled>
            <small>Username cannot be changed</small>
        </div>

        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" minlength="6">
            <small>Leave blank to keep current password</small>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= h($editUser['email']) ?>">
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="fname">First Name</label>
                <input type="text" id="fname" name="fname" value="<?= h($editUser['fname']) ?>">
            </div>

            <div class="form-group">
                <label for="lname">Last Name</label>
                <input type="text" id="lname" name="lname" value="<?= h($editUser['lname']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="access">Access Level</label>
            <select id="access" name="access">
                <option value="1" <?= (int)$editUser['access'] === 1 ? 'selected' : '' ?>>User</option>
                <option value="5" <?= (int)$editUser['access'] >= 5 ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="must_change_password" value="1" <?= $editUser['must_change_password'] ? 'checked' : '' ?>>
                Require password change on next login
            </label>
        </div>

        <div class="btn-group">
            <div class="btn-group-left">
                <button type="submit" class="btn">Save</button>
                <a href="/admin/users" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
