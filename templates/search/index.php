<h2 class="mb-2">Search Images</h2>

<div class="card card-lg">
    <form method="GET" action="/search/results">
        <div class="form-group">
            <label for="keywords">Keywords</label>
            <input type="text" id="keywords" name="keywords" placeholder="Search descriptions...">
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="start_date">From Date</label>
                <input type="date" id="start_date" name="start_date">
            </div>

            <div class="form-group">
                <label for="end_date">To Date</label>
                <input type="date" id="end_date" name="end_date">
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label for="photographer">Photographer</label>
                <select id="photographer" name="photographer">
                    <option value="">-- Any --</option>
                    <?php foreach ($photographers as $p): ?>
                        <option value="<?= h($p['id']) ?>"><?= h($p['fname'] . ' ' . $p['lname']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="person_id">People Tagged</label>
                <select id="person_id" name="person_id[]" multiple size="4">
                    <?php foreach ($taggedPeople as $p): ?>
                        <option value="<?= h($p['id']) ?>"><?= h($p['fname'] . ' ' . $p['lname']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small>Hold Ctrl/Cmd to select multiple</small>
            </div>
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">-- Any --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= h($cat['id']) ?>"><?= h($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="sort">Sort By</label>
            <select id="sort" name="sort">
                <option value="date_taken">Date Taken</option>
                <option value="added">Date Added</option>
                <option value="views">Most Viewed</option>
                <option value="random">Random</option>
            </select>
        </div>

        <button type="submit" class="btn">Search</button>
    </form>
</div>
