<div class="card" class="card-sm card-center">
    <h2 class="mb-2">Register</h2>

    <form method="POST" action="/register">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= h(old('username')) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= h(old('email')) ?>" required>
        </div>

        <div class="form-group">
            <label for="fname">First Name</label>
            <input type="text" id="fname" name="fname" value="<?= h(old('fname')) ?>">
        </div>

        <div class="form-group">
            <label for="lname">Last Name</label>
            <input type="text" id="lname" name="lname" value="<?= h(old('lname')) ?>">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="password_confirm">Confirm Password</label>
            <input type="password" id="password_confirm" name="password_confirm" required>
        </div>

        <button type="submit" class="btn">Register</button>
    </form>

    <p class="mt-2">
        Already have an account? <a href="/login">Login</a>
    </p>
</div>
