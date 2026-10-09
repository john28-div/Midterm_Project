<?php
require_once dirname(__DIR__) . '/includes/layout.php';
require_role('admin');

$seekers = (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'job_seeker'")->fetch_assoc()['c'];
$employers = (int) $conn->query('SELECT COUNT(*) AS c FROM employers')->fetch_assoc()['c'];
$trainings = (int) $conn->query('SELECT COUNT(*) AS c FROM trainings')->fetch_assoc()['c'];
$enrollments = (int) $conn->query('SELECT COUNT(*) AS c FROM enrollments')->fetch_assoc()['c'];
$applications = (int) $conn->query('SELECT COUNT(*) AS c FROM applications')->fetch_assoc()['c'];
$hired = (int) $conn->query("SELECT COUNT(*) AS c FROM applications WHERE status = 'hired'")->fetch_assoc()['c'];
$pending = (int) $conn->query('SELECT COUNT(*) AS c FROM employers WHERE is_verified = 0')->fetch_assoc()['c'];

app_head('Admin Dashboard');
?>
<div class="app-shell">
  <?php admin_nav('admin/dashboard.php'); ?>
  <main class="main">
    <div class="page-head">
      <div>
        <h1>PESO / GAD dashboard</h1>
        <p class="sub">Pilot indicators for Lakat in Lambunao.</p>
      </div>
    </div>
    <div class="stat-grid">
      <div class="stat-card"><div class="label">Women registered</div><div class="value"><?= $seekers ?></div></div>
      <div class="stat-card"><div class="label">Partner employers</div><div class="value"><?= $employers ?></div></div>
      <div class="stat-card"><div class="label">Training programs</div><div class="value"><?= $trainings ?></div></div>
      <div class="stat-card highlight"><div class="label">Pending verifications</div><div class="value"><?= $pending ?></div></div>
    </div>
    <div class="stat-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="stat-card"><div class="label">Enrollments</div><div class="value"><?= $enrollments ?></div></div>
      <div class="stat-card"><div class="label">Job applications</div><div class="value"><?= $applications ?></div></div>
      <div class="stat-card"><div class="label">Women hired</div><div class="value"><?= $hired ?></div></div>
    </div>
  </main>
</div>
<?php app_foot(); ?>
