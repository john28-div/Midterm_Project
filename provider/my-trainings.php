<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('provider');
$uid = (int) $user['user_id'];

$rows = $conn->query(
    "SELECT t.*,
            (SELECT COUNT(*) FROM enrollments e WHERE e.training_id = t.training_id AND e.status != 'cancelled') AS enrolled_count
     FROM trainings t WHERE t.provider_user_id = $uid ORDER BY t.schedule DESC"
);

app_head('My Trainings');
?>
<div class="app-shell">
  <?php provider_nav('provider/my-trainings.php'); ?>
  <main class="main">
    <?php render_flash(); ?>
    <div class="page-head">
      <div>
        <h1>My trainings</h1>
        <p class="sub">Programs you have posted.</p>
      </div>
      <a class="btn btn-primary" style="width:auto" href="<?= e(url('provider/post-training.php')) ?>">Post a Training</a>
    </div>
    <section class="panel">
      <?php if ($rows->num_rows === 0): ?>
        <p class="empty">No trainings yet.</p>
      <?php else: ?>
        <?php while ($t = $rows->fetch_assoc()): ?>
          <div class="list-item">
            <div>
              <h3><?= e($t['title']) ?></h3>
              <div class="meta"><?= e(format_date($t['schedule'])) ?> · <?= e($t['venue']) ?></div>
            </div>
            <span class="badge badge-blue"><?= (int)$t['enrolled_count'] ?> / <?= (int)$t['slots'] ?> slots</span>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </section>
  </main>
</div>
<?php app_foot(); ?>
