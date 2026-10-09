<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('employer');
$employer = get_employer_for_user($conn, (int) $user['user_id']);
if (!$employer) {
    flash_set('error', 'Business profile missing. Contact admin.');
    header('Location: ' . url('logout.php'));
    exit;
}
$eid = (int) $employer['employer_id'];

$openJobs = (int) $conn->query("SELECT COUNT(*) AS c FROM jobs WHERE employer_id = $eid AND status = 'open'")->fetch_assoc()['c'];
$apps = (int) $conn->query(
    "SELECT COUNT(*) AS c FROM applications a JOIN jobs j ON j.job_id = a.job_id WHERE j.employer_id = $eid"
)->fetch_assoc()['c'];
$interviews = (int) $conn->query(
    "SELECT COUNT(*) AS c FROM applications a JOIN jobs j ON j.job_id = a.job_id WHERE j.employer_id = $eid AND a.status = 'interview'"
)->fetch_assoc()['c'];

$recent = $conn->query(
    "SELECT j.*, (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.job_id) AS applicant_count
     FROM jobs j WHERE j.employer_id = $eid ORDER BY j.created_at DESC LIMIT 5"
);

app_head('Employer Dashboard');
?>
<div class="app-shell">
  <?php employer_nav('employer/dashboard.php'); ?>
  <main class="main">
    <?php render_flash(); ?>
    <div class="page-head">
      <div>
        <h1><?= e(greeting_hiligaynon()) ?>, <?= e(first_name($user['full_name'])) ?>!</h1>
        <p class="sub"><?= e($employer['business_name']) ?> · <?= $employer['is_verified'] ? 'Verified employer' : 'Pending verification' ?></p>
      </div>
      <a class="btn btn-accent" href="<?= e(url('employer/post-job.php')) ?>">Post a Job</a>
    </div>
    <div class="stat-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="stat-card"><div class="label">Open jobs</div><div class="value"><?= $openJobs ?></div></div>
      <div class="stat-card"><div class="label">Total applicants</div><div class="value"><?= $apps ?></div></div>
      <div class="stat-card"><div class="label">Interviews</div><div class="value"><?= $interviews ?></div></div>
    </div>
    <section class="panel">
      <div class="panel-head"><h2>Recent postings</h2></div>
      <?php while ($j = $recent->fetch_assoc()): ?>
        <div class="list-item">
          <div>
            <h3><?= e($j['title']) ?></h3>
            <div class="meta"><?= (int)$j['applicant_count'] ?> applicants · Deadline <?= e(format_date($j['deadline'])) ?></div>
          </div>
          <span class="badge <?= $j['status'] === 'open' ? 'badge-green' : 'badge-closed' ?>"><?= e(ucfirst($j['status'])) ?></span>
        </div>
      <?php endwhile; ?>
    </section>
  </main>
</div>
<?php app_foot(); ?>
