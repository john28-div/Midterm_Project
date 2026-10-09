<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('job_seeker');
$uid = (int) $user['user_id'];

$apps = $conn->query(
    "SELECT a.*, j.title, j.location, j.deadline, e.business_name
     FROM applications a
     JOIN jobs j ON j.job_id = a.job_id
     JOIN employers e ON e.employer_id = j.employer_id
     WHERE a.user_id = $uid
     ORDER BY a.applied_at DESC"
);
$ens = $conn->query(
    "SELECT en.*, t.title, t.schedule, t.venue, u.full_name AS provider_name
     FROM enrollments en
     JOIN trainings t ON t.training_id = en.training_id
     JOIN users u ON u.user_id = t.provider_user_id
     WHERE en.user_id = $uid
     ORDER BY en.enrolled_at DESC"
);

app_head('My Applications');
?>
<div class="app-shell">
  <?php seeker_nav('seeker/applications.php'); ?>
  <main class="main">
    <div class="page-head">
      <div>
        <h1>My applications</h1>
        <p class="sub">Track job applications and training enrollments.</p>
      </div>
    </div>
    <div class="grid-2">
      <section class="panel">
        <div class="panel-head"><h2>Jobs</h2></div>
        <?php if ($apps->num_rows === 0): ?>
          <p class="empty">No job applications yet.</p>
        <?php else: ?>
          <?php while ($a = $apps->fetch_assoc()): ?>
            <div class="list-item">
              <div>
                <h3><?= e($a['title']) ?></h3>
                <div class="meta"><?= e($a['business_name']) ?> · Applied <?= e(format_date($a['applied_at'])) ?></div>
              </div>
              <span class="badge <?= in_array($a['status'], ['interview','hired'], true) ? 'badge-green' : 'badge-orange' ?>">
                <?= e(application_label($a['status'])) ?>
              </span>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </section>
      <section class="panel">
        <div class="panel-head"><h2>Trainings</h2></div>
        <?php if ($ens->num_rows === 0): ?>
          <p class="empty">No enrollments yet.</p>
        <?php else: ?>
          <?php while ($en = $ens->fetch_assoc()): ?>
            <div class="list-item">
              <div>
                <h3><?= e($en['title']) ?></h3>
                <div class="meta"><?= e($en['provider_name']) ?> · Enrolled <?= e(format_date($en['enrolled_at'])) ?></div>
              </div>
              <span class="badge badge-green"><?= e(enrollment_label($en['status'])) ?></span>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </section>
    </div>
  </main>
</div>
<?php app_foot(); ?>
