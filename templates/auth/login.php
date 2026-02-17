<div class="card card-sm card-center">
    <h2 class="mb-2">Login</h2>

    <form method="POST" action="/login">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn">Login</button>
    </form>

    <p class="mt-2">
        Don't have an account? <a href="/register">Register</a>
    </p>
</div>
