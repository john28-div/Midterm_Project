<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('job_seeker');
$uid = (int) $user['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jid = (int) ($_POST['job_id'] ?? 0);
    $stmt = $conn->prepare("INSERT IGNORE INTO applications (user_id, job_id, status) VALUES (?, ?, 'submitted')");
    $stmt->bind_param('ii', $uid, $jid);
    $stmt->execute();
    $stmt->close();
    flash_set('success', 'Application submitted.');
    header('Location: ' . url('seeker/jobs.php'));
    exit;
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT j.*, e.business_name, e.is_verified,
        EXISTS(SELECT 1 FROM applications a WHERE a.job_id = j.job_id AND a.user_id = ?) AS already
        FROM jobs j
        JOIN employers e ON e.employer_id = j.employer_id
        WHERE j.status = 'open' AND j.deadline >= CURDATE()";
$types = 'i';
$params = [$uid];
if ($q !== '') {
    $sql .= ' AND (j.title LIKE ? OR j.location LIKE ? OR e.business_name LIKE ?)';
    $like = '%' . $q . '%';
    $types .= 'sss';
    array_push($params, $like, $like, $like);
}
$sql .= ' ORDER BY j.deadline ASC';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result();

app_head('Job Openings');
?>
<div class="app-shell">
  <?php seeker_nav('seeker/jobs.php'); ?>
  <main class="main">
    <?php render_flash(); ?>
    <div class="page-head">
      <div>
        <h1>Job openings</h1>
        <p class="sub">Browse openings from local employers and track your status.</p>
      </div>
      <form method="get">
        <input class="search-input" type="search" name="q" placeholder="Search jobs" value="<?= e($q) ?>">
      </form>
    </div>
    <section class="panel">
      <?php if ($rows->num_rows === 0): ?>
        <p class="empty">No open jobs match your search.</p>
      <?php else: ?>
        <?php while ($j = $rows->fetch_assoc()): ?>
          <div class="list-item">
            <div>
              <h3><?= e($j['title']) ?></h3>
              <div class="meta">
                <?= $j['is_verified'] ? 'Verified employer' : e($j['business_name']) ?>
                · <?= e($j['location']) ?> · Apply by <?= e(format_date($j['deadline'])) ?>
              </div>
              <?php if ($j['description']): ?><div class="meta"><?= e($j['description']) ?></div><?php endif; ?>
            </div>
            <?php if ($j['already']): ?>
              <span class="badge badge-orange">Applied</span>
            <?php else: ?>
              <form method="post">
                <input type="hidden" name="job_id" value="<?= (int)$j['job_id'] ?>">
                <button class="btn btn-outline btn-sm" type="submit">Apply</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </section>
  </main>
</div>
<?php app_foot(); ?>
