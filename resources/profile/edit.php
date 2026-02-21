<div class="card card-lg">
    <h2 class="mb-2">Edit Profile</h2>

    <form method="POST" action="/profile/edit">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="fname">First Name</label>
            <input type="text" id="fname" name="fname" value="<?= h($profile['fname']) ?>">
        </div>

        <div class="form-group">
            <label for="lname">Last Name</label>
            <input type="text" id="lname" name="lname" value="<?= h($profile['lname']) ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= h($profile['email']) ?>">
        </div>

        <div class="form-group">
            <label for="name_disp">Display name as</label>
            <select id="name_disp" name="name_disp">
                <option value="fname" <?= $profile['name_disp'] === 'fname' ? 'selected' : '' ?>>First name</option>
                <option value="lname" <?= $profile['name_disp'] === 'lname' ? 'selected' : '' ?>>Last name</option>
                <option value="both" <?= $profile['name_disp'] === 'both' ? 'selected' : '' ?>>Full name</option>
            </select>
        </div>

        <div class="form-group">
            <label for="init_disp">Default image size</label>
            <select id="init_disp" name="init_disp">
                <option value="480x360" <?= $profile['init_disp'] === '480x360' ? 'selected' : '' ?>>480 x 360</option>
                <option value="640x480" <?= $profile['init_disp'] === '640x480' ? 'selected' : '' ?>>640 x 480</option>
                <option value="800x600" <?= $profile['init_disp'] === '800x600' ? 'selected' : '' ?>>800 x 600</option>
            </select>
        </div>

        <div class="form-group">
            <label for="theme">Theme</label>
            <select id="theme" name="theme">
                <option value="default" <?= $profile['theme'] === 'default' ? 'selected' : '' ?>>Default</option>
            </select>
        </div>

        <div class="btn-group">
            <div class="btn-group-left">
                <button type="submit" class="btn">Save</button>
                <a href="/profile/<?= h($profile['id']) ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
