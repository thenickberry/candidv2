<div class="card card-md">
    <h2 class="mb-2">Create User</h2>

    <form method="POST" action="/admin/users/create">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" required>
        </div>

        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required minlength="6">
            <small>Minimum 6 characters</small>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email">
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="fname">First Name</label>
                <input type="text" id="fname" name="fname">
            </div>

            <div class="form-group">
                <label for="lname">Last Name</label>
                <input type="text" id="lname" name="lname">
            </div>
        </div>

        <div class="form-group">
            <label for="access">Access Level</label>
            <select id="access" name="access">
                <option value="1">User</option>
                <option value="5">Admin</option>
            </select>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="must_change_password" value="1" checked>
                Require password change on first login
            </label>
        </div>

        <div class="btn-group">
            <div class="btn-group-left">
                <button type="submit" class="btn">Create</button>
                <a href="/admin/users" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
