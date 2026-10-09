<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('employer');
$employer = get_employer_for_user($conn, (int) $user['user_id']);
if (!$employer) {
    flash_set('error', 'Business profile missing.');
    header('Location: ' . url('employer/dashboard.php'));
    exit;
}
$eid = (int) $employer['employer_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $deadline = trim($_POST['deadline'] ?? '');
    $contact = trim($_POST['contact_no'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title === '' || $location === '' || $deadline === '') {
        $error = 'Job title, location, and deadline are required.';
    } else {
        if ($contact !== '') {
            $uid = (int) $user['user_id'];
            $upd = $conn->prepare('UPDATE users SET contact_no = ? WHERE user_id = ?');
            $upd->bind_param('si', $contact, $uid);
            $upd->execute();
            $upd->close();
            $_SESSION['user']['contact_no'] = $contact;
        }

        $stmt = $conn->prepare(
            "INSERT INTO jobs (employer_id, title, description, location, deadline, status)
             VALUES (?, ?, ?, ?, ?, 'open')"
        );
        $stmt->bind_param('issss', $eid, $title, $description, $location, $deadline);
        if ($stmt->execute()) {
            flash_set('success', 'Job published successfully.');
            header('Location: ' . url('employer/my-jobs.php'));
            exit;
        }
        $error = 'Could not publish job.';
        $stmt->close();
    }
}

$recent = $conn->query(
    "SELECT j.*, (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.job_id) AS applicant_count
     FROM jobs j WHERE j.employer_id = $eid ORDER BY j.created_at DESC LIMIT 5"
);

app_head('Post a Job');
?>
<div class="app-shell">
  <?php employer_nav('employer/post-job.php'); ?>
  <main class="main">
    <?php render_flash(); ?>
    <div class="crumb">Dashboard / Job Postings / <span>New</span></div>
    <div class="page-head">
      <div>
        <h1>Post a new job opening</h1>
        <p class="sub">Women in Lambunao will see this once your posting is published.</p>
      </div>
    </div>

    <div class="grid-form">
      <form class="panel form-card" method="post">
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <div class="field">
          <label for="title">Job title</label>
          <input id="title" name="title" required placeholder="Sales Clerk" value="<?= e($_POST['title'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="business">Business</label>
          <input id="business" value="<?= e($employer['business_name']) ?>" disabled>
        </div>
        <div class="two-col">
          <div class="field">
            <label for="location">Location (barangay)</label>
            <select id="location" name="location" required>
              <option value="">Select barangay</option>
              <?php foreach (barangays() as $b): ?>
                <option value="<?= e($b) ?>" <?= ($_POST['location'] ?? $user['barangay'] ?? '') === $b ? 'selected' : '' ?>><?= e($b) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="deadline">Application deadline</label>
            <input id="deadline" type="date" name="deadline" required value="<?= e($_POST['deadline'] ?? '') ?>">
          </div>
        </div>
        <div class="field">
          <label for="contact_no">Contact number</label>
          <input id="contact_no" name="contact_no" placeholder="09XX XXX XXXX"
                 value="<?= e($_POST['contact_no'] ?? ($user['contact_no'] ?? '')) ?>">
        </div>
        <div class="field">
          <label for="description">Description and requirements</label>
          <textarea id="description" name="description" rows="5" placeholder="Assist customers, handle cash, and arrange stock. No experience needed; training provided."><?= e($_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
          <a class="btn btn-outline" href="<?= e(url('employer/dashboard.php')) ?>">Cancel</a>
          <button class="btn btn-primary" type="submit">Publish job</button>
        </div>
      </form>

      <aside class="panel">
        <div class="panel-head"><h2>Recent postings</h2></div>
        <?php if ($recent->num_rows === 0): ?>
          <p class="empty">No postings yet.</p>
        <?php else: ?>
          <?php while ($j = $recent->fetch_assoc()): ?>
            <div class="list-item">
              <div>
                <h3><?= e($j['title']) ?></h3>
                <div class="meta"><?= (int)$j['applicant_count'] ?> applicants</div>
              </div>
              <span class="badge <?= $j['status'] === 'open' ? 'badge-green' : 'badge-closed' ?>">
                <?= e(ucfirst($j['status'])) ?>
              </span>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </aside>
    </div>
  </main>
</div>
<?php app_foot(); ?>
