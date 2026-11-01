<div style="max-width:800px;margin:0 auto">
  <div style="margin-bottom:24px">
    <h2 style="font-size:28px;margin:0 0 8px;font-weight:800">Edit Question</h2>
    <p style="color:var(--muted);margin:0">Update the question, details, or tags while keeping the discussion intact.</p>
  </div>

  <form method="post" action="/topics/<?= (int)$topic['id'] ?>/update" class="card" style="box-shadow:var(--shadow-lg)">
    <?= $csrf->field() ?>
    <label>Topic Title
      <input name="title" required maxlength="180" value="<?= e($form['title'] ?? $topic['title']) ?>" placeholder="Enter a clear, descriptive title">
    </label>
    <label>Content
      <textarea name="body" required maxlength="20000" rows="10" placeholder="Describe your question in detail... (Markdown supported)"><?= e($form['body'] ?? $topic['body']) ?></textarea>
    </label>

    <fieldset style="border:1px solid var(--border);border-radius:12px;padding:16px;margin:16px 0">
      <legend style="font-weight:600;padding:0 8px">Tags</legend>
      <ul class="chips">
        <?php foreach($tags as $t): ?>
          <li><label class="chip">
            <input type="checkbox" name="tags[]" value="<?= (int)$t['id'] ?>" <?= in_array((int)$t['id'], $topicTags, true) ? 'checked' : '' ?>>
            <span style="font-weight:500"><?= e($t['name']) ?></span>
          </label></li>
        <?php endforeach; ?>
      </ul>
    </fieldset>

    <div style="display:flex;gap:12px;align-items:center">
      <button class="btn">Save changes</button>
      <a href="/topics/<?= (int)$topic['id'] ?>-<?= e($topic['slug']) ?>" class="btn ghost">Cancel</a>
    </div>
  </form>
</div>
