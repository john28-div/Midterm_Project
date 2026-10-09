<?php
require_once dirname(__DIR__) . '/includes/layout.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employerId = (int) ($_POST['employer_id'] ?? 0);
    $verified = (int) ($_POST['is_verified'] ?? 1);
    $stmt = $conn->prepare('UPDATE employers SET is_verified = ? WHERE employer_id = ?');
    $stmt->bind_param('ii', $verified, $employerId);
    $stmt->execute();
    $stmt->close();
    flash_set('success', 'Employer verification updated.');
    header('Location: ' . url('admin/verify.php'));
    exit;
}

$rows = $conn->query(
    "SELECT e.*, u.full_name, u.email, u.contact_no, u.barangay
     FROM employers e
     JOIN users u ON u.user_id = e.user_id
     ORDER BY e.is_verified ASC, e.employer_id DESC"
);

app_head('Verify Accounts');
?>
<div class="app-shell">
  <?php admin_nav('admin/verify.php'); ?>
  <main class="main">
    <?php render_flash(); ?>
    <div class="page-head">
      <div>
        <h1>Verify accounts</h1>
        <p class="sub">PESO/GAD staff verify every employer account.</p>
      </div>
    </div>
    <section class="panel">
      <table class="table">
        <thead>
          <tr><th>Business</th><th>Contact</th><th>Barangay</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php while ($r = $rows->fetch_assoc()): ?>
            <tr>
              <td>
                <strong><?= e($r['business_name']) ?></strong><br>
                <span class="meta"><?= e($r['full_name']) ?> · <?= e($r['email']) ?></span>
              </td>
              <td><?= e($r['contact_no'] ?: '—') ?></td>
              <td><?= e($r['barangay'] ?: '—') ?></td>
              <td>
                <span class="badge <?= $r['is_verified'] ? 'badge-green' : 'badge-orange' ?>">
                  <?= $r['is_verified'] ? 'Verified' : 'Pending' ?>
                </span>
              </td>
              <td>
                <form method="post">
                  <input type="hidden" name="employer_id" value="<?= (int)$r['employer_id'] ?>">
                  <?php if ($r['is_verified']): ?>
                    <input type="hidden" name="is_verified" value="0">
                    <button class="btn btn-outline btn-sm" type="submit">Unverify</button>
                  <?php else: ?>
                    <input type="hidden" name="is_verified" value="1">
                    <button class="btn btn-primary btn-sm" type="submit" style="width:auto">Verify</button>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </section>
  </main>
</div>
<?php app_foot(); ?>
