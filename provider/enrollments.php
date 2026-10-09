<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('provider');
$uid = (int) $user['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enId = (int) ($_POST['enrollment_id'] ?? 0);
    $status = $_POST['status'] ?? 'confirmed';
    if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
        $status = 'confirmed';
    }
    $stmt = $conn->prepare(
        'UPDATE enrollments
         SET status = ?
         WHERE enrollment_id = ?
           AND training_id IN (SELECT training_id FROM trainings WHERE provider_user_id = ?)'
    );
    $stmt->bind_param('sii', $status, $enId, $uid);
    $stmt->execute();
    $stmt->close();
    flash_set('success', 'Enrollment updated.');
    header('Location: ' . url('provider/enrollments.php'));
    exit;
}

$rows = $conn->query(
    "SELECT e.*, u.full_name, u.contact_no, u.barangay, t.title
     FROM enrollments e
     JOIN users u ON u.user_id = e.user_id
     JOIN trainings t ON t.training_id = e.training_id
     WHERE t.provider_user_id = $uid
     ORDER BY e.enrolled_at DESC"
);

app_head('Enrollments');
?>
<div class="app-shell">
  <?php provider_nav('provider/enrollments.php'); ?>
  <main class="main">
    <?php render_flash(); ?>
    <div class="page-head">
      <div>
        <h1>Enrollments</h1>
        <p class="sub">Women enrolled in your programs.</p>
      </div>
    </div>
    <section class="panel">
      <?php if ($rows->num_rows === 0): ?>
        <p class="empty">No enrollments yet.</p>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr><th>Trainee</th><th>Training</th><th>Enrolled</th><th>Status</th><th>Update</th></tr>
          </thead>
          <tbody>
            <?php while ($r = $rows->fetch_assoc()): ?>
              <tr>
                <td>
                  <strong><?= e($r['full_name']) ?></strong><br>
                  <span class="meta"><?= e($r['contact_no'] ?: $r['barangay'] ?: '—') ?></span>
                </td>
                <td><?= e($r['title']) ?></td>
                <td><?= e(format_date($r['enrolled_at'])) ?></td>
                <td><?= e(enrollment_label($r['status'])) ?></td>
                <td>
                  <form method="post" style="display:flex;gap:.4rem">
                    <input type="hidden" name="enrollment_id" value="<?= (int)$r['enrollment_id'] ?>">
                    <select name="status">
                      <?php foreach (['pending','confirmed','completed','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= e(enrollment_label($s)) ?></option>
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
