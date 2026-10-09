<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('employer');
$employer = get_employer_for_user($conn, (int) $user['user_id']);
$eid = (int) $employer['employer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobId = (int) ($_POST['job_id'] ?? 0);
    $status = $_POST['status'] ?? 'closed';
    if (!in_array($status, ['open', 'closed', 'filled'], true)) {
        $status = 'closed';
    }
    $stmt = $conn->prepare('UPDATE jobs SET status = ? WHERE job_id = ? AND employer_id = ?');
    $stmt->bind_param('sii', $status, $jobId, $eid);
    $stmt->execute();
    $stmt->close();
    flash_set('success', 'Job status updated.');
    header('Location: ' . url('employer/my-jobs.php'));
    exit;
}

$rows = $conn->query(
    "SELECT j.*, (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.job_id) AS applicant_count
     FROM jobs j WHERE j.employer_id = $eid ORDER BY j.created_at DESC"
);

app_head('My Job Postings');
?>
<div class="app-shell">
  <?php employer_nav('employer/my-jobs.php'); ?>
  <main class="main">
    <?php render_flash(); ?>
    <div class="page-head">
      <div>
        <h1>My job postings</h1>
        <p class="sub">Manage openings and close them when filled.</p>
      </div>
      <a class="btn btn-primary" href="<?= e(url('employer/post-job.php')) ?>" style="width:auto">Post a Job</a>
    </div>
    <section class="panel">
      <table class="table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Location</th>
            <th>Deadline</th>
            <th>Applicants</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php while ($j = $rows->fetch_assoc()): ?>
            <tr>
              <td><?= e($j['title']) ?></td>
              <td><?= e($j['location']) ?></td>
              <td><?= e(format_date($j['deadline'])) ?></td>
              <td><?= (int)$j['applicant_count'] ?></td>
              <td><span class="badge <?= $j['status'] === 'open' ? 'badge-green' : 'badge-closed' ?>"><?= e(ucfirst($j['status'])) ?></span></td>
              <td>
                <?php if ($j['status'] === 'open'): ?>
                  <form method="post" style="display:inline">
                    <input type="hidden" name="job_id" value="<?= (int)$j['job_id'] ?>">
                    <input type="hidden" name="status" value="closed">
                    <button class="btn btn-outline btn-sm" type="submit">Close</button>
                  </form>
                <?php else: ?>
                  <form method="post" style="display:inline">
                    <input type="hidden" name="job_id" value="<?= (int)$j['job_id'] ?>">
                    <input type="hidden" name="status" value="open">
                    <button class="btn btn-outline btn-sm" type="submit">Reopen</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </section>
  </main>
</div>
<?php app_foot(); ?>
