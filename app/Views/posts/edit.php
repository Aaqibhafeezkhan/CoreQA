<div style="max-width:800px;margin:0 auto">
  <div style="margin-bottom:24px">
    <h2 style="font-size:28px;margin:0 0 8px;font-weight:800">Edit Answer</h2>
    <p style="color:var(--muted);margin:0">Update your answer while keeping it attached to the original question.</p>
  </div>

  <div class="card" style="margin-bottom:16px;background:var(--primary-50)">
    <div style="font-size:13px;color:var(--muted);margin-bottom:6px">Question</div>
    <a href="/topics/<?= (int)$topic['id'] ?>-<?= e($topic['slug']) ?>" style="font-weight:700"><?= e($topic['title']) ?></a>
  </div>

  <form method="post" action="/posts/<?= (int)$post['id'] ?>/update" class="card" style="box-shadow:var(--shadow-lg)">
    <?= $csrf->field() ?>
    <label>Answer
      <textarea name="body" required maxlength="20000" rows="12" placeholder="Write a useful answer... (Markdown supported)"><?= e($form['body'] ?? $post['body']) ?></textarea>
    </label>
    <div style="display:flex;gap:12px;align-items:center">
      <button class="btn">Save changes</button>
      <a href="/topics/<?= (int)$topic['id'] ?>-<?= e($topic['slug']) ?>" class="btn ghost">Cancel</a>
    </div>
  </form>
</div>
