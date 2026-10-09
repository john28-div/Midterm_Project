<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('employer');
$employer = get_employer_for_user($conn, (int) $user['user_id']);
$eid = (int) $employer['employer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appId = (int) ($_POST['application_id'] ?? 0);
    $status = $_POST['status'] ?? 'under_review';
    $allowed = ['submitted', 'under_review', 'interview', 'hired', 'rejected'];
    if (!in_array($status, $allowed, true)) {
        $status = 'under_review';
    }
    $stmt = $conn->prepare(
        'UPDATE applications
         SET status = ?
         WHERE application_id = ?
           AND job_id IN (SELECT job_id FROM jobs WHERE employer_id = ?)'
    );
    $stmt->bind_param('sii', $status, $appId, $eid);
    $stmt->execute();
    $stmt->close();
    flash_set('success', 'Applicant status updated.');
    header('Location: ' . url('employer/applicants.php'));
    exit;
}

$rows = $conn->query(
    "SELECT a.*, u.full_name, u.email, u.contact_no, u.barangay, j.title
     FROM applications a
     JOIN users u ON u.user_id = a.user_id
     JOIN jobs j ON j.job_id = a.job_id
     WHERE j.employer_id = $eid
     ORDER BY a.applied_at DESC"
);

app_head('Applicants');
?>
<div class="app-shell">
  <?php employer_nav('employer/applicants.php'); ?>
  <main class="main">
    <?php render_flash(); ?>
    <div class="page-head">
      <div>
        <h1>Applicants</h1>
        <p class="sub">Review candidates and update their status.</p>
      </div>
    </div>
    <section class="panel">
      <?php if ($rows->num_rows === 0): ?>
        <p class="empty">No applicants yet.</p>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Applicant</th>
              <th>Job</th>
              <th>Contact</th>
              <th>Applied</th>
              <th>Status</th>
              <th>Update</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($a = $rows->fetch_assoc()): ?>
              <tr>
                <td>
                  <strong><?= e($a['full_name']) ?></strong><br>
                  <span class="meta"><?= e($a['barangay'] ?: '—') ?></span>
                </td>
                <td><?= e($a['title']) ?></td>
                <td><?= e($a['contact_no'] ?: $a['email']) ?></td>
                <td><?= e(format_date($a['applied_at'])) ?></td>
                <td><?= e(application_label($a['status'])) ?></td>
                <td>
                  <form method="post" style="display:flex;gap:.4rem;align-items:center">
                    <input type="hidden" name="application_id" value="<?= (int)$a['application_id'] ?>">
                    <select name="status">
                      <?php foreach (['submitted','under_review','interview','hired','rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>><?= e(application_label($s)) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline btn-sm" type="submit">Save</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </main>
</div>
<?php app_foot(); ?>
