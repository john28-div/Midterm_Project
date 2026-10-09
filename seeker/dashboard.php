<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('job_seeker');
$uid = (int) $user['user_id'];

$enrolled = (int) $conn->query("SELECT COUNT(*) AS c FROM enrollments WHERE user_id = $uid AND status != 'cancelled'")->fetch_assoc()['c'];
$apps = (int) $conn->query("SELECT COUNT(*) AS c FROM applications WHERE user_id = $uid")->fetch_assoc()['c'];
$interviews = (int) $conn->query("SELECT COUNT(*) AS c FROM applications WHERE user_id = $uid AND status = 'interview'")->fetch_assoc()['c'];
$complete = profile_completeness($user);

$q = trim($_GET['q'] ?? '');
$trainings = $conn->query(
    "SELECT t.*, u.full_name AS provider_name,
            (t.slots - (SELECT COUNT(*) FROM enrollments e WHERE e.training_id = t.training_id AND e.status != 'cancelled')) AS slots_left
     FROM trainings t
     JOIN users u ON u.user_id = t.provider_user_id
     ORDER BY t.schedule ASC
     LIMIT 5"
);

$jobs = $conn->query(
    "SELECT j.*, e.business_name, e.is_verified
     FROM jobs j
     JOIN employers e ON e.employer_id = j.employer_id
     WHERE j.status = 'open' AND j.deadline >= CURDATE()
     ORDER BY j.deadline ASC
     LIMIT 5"
);

$mine = $conn->query(
    "(SELECT 'job' AS kind, j.title, a.applied_at AS event_at, a.status, NULL AS interview_note
      FROM applications a JOIN jobs j ON j.job_id = a.job_id
      WHERE a.user_id = $uid)
     UNION ALL
     (SELECT 'training' AS kind, t.title, en.enrolled_at AS event_at, en.status, NULL AS interview_note
      FROM enrollments en JOIN trainings t ON t.training_id = en.training_id
      WHERE en.user_id = $uid)
     ORDER BY event_at DESC
     LIMIT 8"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'enroll') {
        $tid = (int) ($_POST['training_id'] ?? 0);
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO enrollments (user_id, training_id, status) VALUES (?, ?, 'confirmed')"
        );
        $stmt->bind_param('ii', $uid, $tid);
        $stmt->execute();
        $stmt->close();
        flash_set('success', 'Enrollment submitted.');
        header('Location: ' . url('seeker/dashboard.php'));
        exit;
    }
    if ($action === 'apply') {
        $jid = (int) ($_POST['job_id'] ?? 0);
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO applications (user_id, job_id, status) VALUES (?, ?, 'submitted')"
        );
        $stmt->bind_param('ii', $uid, $jid);
        $stmt->execute();
        $stmt->close();
        flash_set('success', 'Application submitted.');
        header('Location: ' . url('seeker/dashboard.php'));
        exit;
    }
}

app_head('Dashboard');
?>
<div class="app-shell">
  <?php seeker_nav('seeker/dashboard.php'); ?>
  <main class="main">
    <?php render_flash(); ?>
    <div class="page-head">
      <div>
        <h1><?= e(greeting_hiligaynon()) ?>, <?= e(first_name($user['full_name'])) ?>!</h1>
        <p class="sub"><?= e($user['barangay'] ?: 'Lambunao') ?> · job and training matches for you</p>
      </div>
      <div class="head-actions">
        <form method="get" action="<?= e(url('seeker/jobs.php')) ?>">
          <input class="search-input" type="search" name="q" placeholder="Search trainings or jobs" value="<?= e($q) ?>">
        </form>
        <a class="btn btn-accent" href="<?= e(url('seeker/jobs.php')) ?>">Apply for a job</a>
      </div>
    </div>

    <div class="stat-grid">
      <div class="stat-card"><div class="label">Enrolled trainings</div><div class="value"><?= $enrolled ?></div></div>
      <div class="stat-card"><div class="label">Job applications</div><div class="value"><?= $apps ?></div></div>
      <div class="stat-card"><div class="label">Interviews scheduled</div><div class="value"><?= $interviews ?></div></div>
      <div class="stat-card highlight"><div class="label">Profile completeness</div><div class="value"><?= $complete ?>%</div></div>
    </div>

    <div class="grid-2">
      <section class="panel">
        <div class="panel-head">
          <h2>Trainings for you</h2>
          <span class="badge badge-blue">Open slots</span>
        </div>
        <?php while ($t = $trainings->fetch_assoc()): ?>
          <div class="list-item">
            <div>
              <h3><?= e($t['title']) ?></h3>
              <div class="meta">
                <?= e($t['provider_name']) ?> · <?= e(format_schedule($t['schedule'])) ?> · <?= e($t['venue']) ?>
                <?php if ((int)$t['slots_left'] <= 8): ?> · <?= (int)$t['slots_left'] ?> slots left<?php endif; ?>
              </div>
            </div>
            <form method="post">
              <input type="hidden" name="action" value="enroll">
              <input type="hidden" name="training_id" value="<?= (int)$t['training_id'] ?>">
              <button class="btn btn-outline btn-sm" type="submit">Enroll</button>
            </form>
          </div>
        <?php endwhile; ?>
      </section>

      <section class="panel">
        <div class="panel-head">
          <h2>Job openings</h2>
          <span class="badge badge-orange">Closing soon</span>
        </div>
        <?php while ($j = $jobs->fetch_assoc()): ?>
          <div class="list-item">
            <div>
              <h3><?= e($j['title']) ?></h3>
              <div class="meta">
                <?= $j['is_verified'] ? 'Verified employer' : e($j['business_name']) ?>
                · <?= e($j['location']) ?> · Apply by <?= e(date('M j', strtotime($j['deadline']))) ?>
              </div>
            </div>
            <form method="post">
              <input type="hidden" name="action" value="apply">
              <input type="hidden" name="job_id" value="<?= (int)$j['job_id'] ?>">
              <button class="btn btn-outline btn-sm" type="submit">Apply</button>
            </form>
          </div>
        <?php endwhile; ?>
      </section>
    </div>

    <section class="panel">
      <div class="panel-head"><h2>My applications</h2></div>
      <?php if ($mine->num_rows === 0): ?>
        <p class="empty">No applications or enrollments yet.</p>
      <?php else: ?>
        <?php while ($row = $mine->fetch_assoc()): ?>
          <div class="list-item">
            <div>
              <h3><?= e($row['title']) ?></h3>
              <div class="meta">
                <?= $row['kind'] === 'job' ? 'Applied' : 'Enrolled' ?>
                <?= e(format_date($row['event_at'])) ?>
              </div>
            </div>
            <?php
              $badge = 'badge-orange';
              $label = $row['kind'] === 'job' ? application_label($row['status']) : enrollment_label($row['status']);
              if (in_array($row['status'], ['confirmed', 'interview', 'hired', 'completed'], true)) {
                  $badge = 'badge-green';
              }
            ?>
            <span class="badge <?= $badge ?>"><?= e($label) ?></span>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </section>
  </main>
</div>
<?php app_foot(); ?>
