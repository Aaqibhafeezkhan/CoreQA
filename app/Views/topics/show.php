<article class="topic card" style="margin-bottom:24px">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
    <div style="flex:1">
      <h1 style="font-size:32px;font-weight:800;margin:0 0 12px;letter-spacing:-0.025em;line-height:1.2"><?= e($topic['title']) ?></h1>
      <div style="color:var(--muted);font-size:14px">Asked on <?= date('M j, Y', strtotime($topic['created_at'])) ?><?php if (!empty($topic['updated_at']) && $topic['updated_at'] !== $topic['created_at']): ?> · edited <?= date('M j, Y', strtotime($topic['updated_at'])) ?><?php endif; ?></div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end">
      <?php if (!empty($topic['pinned_at'])): ?>
        <span class="badge" style="background:var(--primary-100);color:var(--primary);font-size:12px;padding:4px 12px;border-radius:6px;font-weight:700">PINNED</span>
      <?php endif; ?>
      <span class="badge" style="background:<?= $topic['status'] === 'open' ? '#dcfce7;color:#166534' : '#fee2e2;color:#991b1b' ?>;font-size:12px;padding:4px 12px;border-radius:6px;font-weight:700"><?= strtoupper(e($topic['status'])) ?></span>
      <?php if ($auth->check() && ($auth->id() === (int)$topic['user_id'] || in_array($auth->role(), ['mod', 'admin'], true))): ?>
        <a class="btn ghost sm" href="/topics/<?= (int)$topic['id'] ?>/edit">Edit</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="body" style="font-size:17px;line-height:1.8;color:#334155;margin-top:24px"><?= $md->toHtml(e($topic['body'])) ?></div>
</article>

<div style="margin:48px 0 24px;display:flex;justify-content:space-between;align-items:center">
  <h2 style="font-size:24px;font-weight:700;margin:0"><?= count($posts) ?> Answers</h2>
</div>

<div class="posts" style="display:flex;flex-direction:column;gap:16px">
  <?php foreach ($posts as $p): ?>
    <div class="post card" style="display:flex;gap:24px;padding:24px" data-id="<?= $p['id'] ?>">
      <div class="vote" style="display:flex;flex-direction:column;align-items:center;background:#f1f5f9;padding:12px;border-radius:12px;height:max-content;min-width:60px">
        <form class="vote-form" method="post" action="/posts/<?= $p['id'] ?>/vote">
          <input type="hidden" name="_token" value="<?= $csrf->token() ?>">
          <button name="value" value="1" type="submit" style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--muted);padding:4px">▲</button>
          <div class="score" style="font-size:20px;font-weight:800;margin:4px 0;color:var(--fg-color)"><?= (int)$p['_score'] ?></div>
          <button name="value" value="-1" type="submit" style="background:none;border:none;cursor:pointer;font-size:20px;color:var(--muted);padding:4px">▼</button>
        </form>
      </div>
      <div style="flex:1">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px">
          <div style="display:flex;align-items:center;gap:12px">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--border);display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:12px"><?= strtoupper(substr($p['name'], 0, 1)) ?></div>
            <div>
              <strong style="font-size:15px"><?= e($p['name']) ?></strong>
              <div style="font-size:12px;color:var(--muted)"><?= date('M j, Y \\a\\t g:i a', strtotime($p['created_at'])) ?><?php if (!empty($p['updated_at']) && $p['updated_at'] !== $p['created_at']): ?> · edited<?php endif; ?></div>
            </div>
          </div>
          <div style="display:flex;gap:8px;align-items:center">
            <?php if ($auth->check() && ($auth->id() === (int)$p['user_id'] || in_array($auth->role(), ['mod', 'admin'], true))): ?>
              <a class="btn ghost sm" href="/posts/<?= (int)$p['id'] ?>/edit">Edit</a>
            <?php endif; ?>
            <?php if ($auth->check() && in_array($auth->role(), ['mod', 'admin'])): ?>
              <form method="post" action="/panel/mod/posts/<?= $p['id'] ?>/delete" onsubmit="return confirm('Delete this answer?')">
                <?= $csrf->field() ?>
                <button class="btn ghost sm" style="color:var(--danger);border-color:#fecaca">Delete</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
        <div class="body" style="font-size:16px;line-height:1.7;color:#334155"><?= $md->toHtml(e($p['body'])) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($auth->check() && $topic['status'] === 'open'): ?>
  <div style="margin-top:48px">
    <h3 style="font-size:20px;font-weight:700;margin-bottom:16px">Your Answer</h3>
    <form method="post" action="/topics/<?= $topic['id'] ?>/reply" class="card">
      <input type="hidden" name="_token" value="<?= $csrf->token() ?>">
      <input type="hidden" name="slug" value="<?= e($topic['slug']) ?>">
      <textarea name="body" required maxlength="20000" rows="6" style="margin-bottom:16px" placeholder="Explain your answer in detail..."><?= e($replyBody) ?></textarea>
      <button class="btn">Post your answer</button>
    </form>
  </div>
<?php elseif ($topic['status'] !== 'open'): ?>
  <div class="card text-center" style="margin-top:48px;background:#fef2f2;border-color:#fecaca">
    <p style="color:#991b1b;font-weight:600;margin:0">This conversation is closed and no longer accepting new answers.</p>
  </div>
<?php else: ?>
  <div class="card text-center" style="margin-top:48px">
    <p style="margin-bottom:16px;color:var(--muted)">You must be logged in to participate in this discussion.</p>
    <a class="btn" href="/login">Log in to answer</a>
  </div>
<?php endif; ?>
