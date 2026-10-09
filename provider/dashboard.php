<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('provider');
$uid = (int) $user['user_id'];

$programs = (int) $conn->query("SELECT COUNT(*) AS c FROM trainings WHERE provider_user_id = $uid")->fetch_assoc()['c'];
$enrolled = (int) $conn->query(
    "SELECT COUNT(*) AS c FROM enrollments e JOIN trainings t ON t.training_id = e.training_id WHERE t.provider_user_id = $uid"
)->fetch_assoc()['c'];

$recent = $conn->query(
    "SELECT t.*,
            (SELECT COUNT(*) FROM enrollments e WHERE e.training_id = t.training_id AND e.status != 'cancelled') AS enrolled_count
     FROM trainings t WHERE t.provider_user_id = $uid ORDER BY t.created_at DESC LIMIT 6"
);

app_head('Provider Dashboard');
?>
<div class="app-shell">
  <?php provider_nav('provider/dashboard.php'); ?>
  <main class="main">
    <?php render_flash(); ?>
    <div class="page-head">
      <div>
        <h1><?= e(greeting_hiligaynon()) ?>, <?= e(first_name($user['full_name'])) ?>!</h1>
        <p class="sub">Post programs and see who enrolled.</p>
      </div>
      <a class="btn btn-accent" href="<?= e(url('provider/post-training.php')) ?>">Post a Training</a>
    </div>
    <div class="stat-grid" style="grid-template-columns:repeat(2,1fr)">
      <div class="stat-card"><div class="label">Training programs</div><div class="value"><?= $programs ?></div></div>
      <div class="stat-card"><div class="label">Total enrollments</div><div class="value"><?= $enrolled ?></div></div>
    </div>
    <section class="panel">
      <div class="panel-head"><h2>Your trainings</h2></div>
      <?php if ($recent->num_rows === 0): ?>
        <p class="empty">No trainings posted yet.</p>
      <?php else: ?>
        <?php while ($t = $recent->fetch_assoc()): ?>
          <div class="list-item">
            <div>
              <h3><?= e($t['title']) ?></h3>
              <div class="meta"><?= e(format_schedule($t['schedule'])) ?> · <?= e($t['venue']) ?> · <?= (int)$t['enrolled_count'] ?>/<?= (int)$t['slots'] ?> enrolled</div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </section>
  </main>
</div>
<?php app_foot(); ?>
