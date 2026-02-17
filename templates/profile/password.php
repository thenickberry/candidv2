<div class="card card-sm">
    <h2 class="mb-2">Change Password</h2>

    <form method="POST" action="/profile/password">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>

        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required minlength="6">
            <small>Minimum 6 characters</small>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>

        <div class="btn-group">
            <div class="btn-group-left">
                <button type="submit" class="btn">Save</button>
                <a href="/profile/<?= h($user['id']) ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
