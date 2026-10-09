<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('job_seeker');
$uid = (int) $user['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = (int) ($_POST['training_id'] ?? 0);
    $stmt = $conn->prepare("INSERT IGNORE INTO enrollments (user_id, training_id, status) VALUES (?, ?, 'confirmed')");
    $stmt->bind_param('ii', $uid, $tid);
    $stmt->execute();
    $stmt->close();
    flash_set('success', 'You are enrolled in this training.');
    header('Location: ' . url('seeker/trainings.php'));
    exit;
}

$q = trim($_GET['q'] ?? '');
$barangay = trim($_GET['barangay'] ?? '');
$sql = "SELECT t.*, u.full_name AS provider_name,
        (t.slots - (SELECT COUNT(*) FROM enrollments e WHERE e.training_id = t.training_id AND e.status != 'cancelled')) AS slots_left,
        EXISTS(SELECT 1 FROM enrollments e2 WHERE e2.training_id = t.training_id AND e2.user_id = ?) AS already
        FROM trainings t
        JOIN users u ON u.user_id = t.provider_user_id
        WHERE 1=1";
$types = 'i';
$params = [$uid];
if ($q !== '') {
    $sql .= ' AND (t.title LIKE ? OR t.venue LIKE ? OR u.full_name LIKE ?)';
    $like = '%' . $q . '%';
    $types .= 'sss';
    array_push($params, $like, $like, $like);
}
if ($barangay !== '') {
    $sql .= ' AND t.venue LIKE ?';
    $types .= 's';
    $params[] = '%' . $barangay . '%';
}
$sql .= ' ORDER BY t.schedule ASC';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result();

app_head('Find Trainings');
?>
<div class="app-shell">
  <?php seeker_nav('seeker/trainings.php'); ?>
  <main class="main">
    <?php render_flash(); ?>
    <div class="page-head">
      <div>
        <h1>Find trainings</h1>
        <p class="sub">Search by schedule and barangay, then enroll online.</p>
      </div>
    </div>
    <form class="panel" method="get" style="margin-bottom:1rem">
      <div class="two-col">
        <div class="field" style="margin:0">
          <label>Search</label>
          <input type="search" name="q" value="<?= e($q) ?>" placeholder="Title or provider">
        </div>
        <div class="field" style="margin:0">
          <label>Barangay / venue</label>
          <select name="barangay">
            <option value="">All</option>
            <?php foreach (barangays() as $b): ?>
              <option value="<?= e($b) ?>" <?= $barangay === $b ? 'selected' : '' ?>><?= e($b) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-actions" style="margin-top:1rem">
        <button class="btn btn-primary" type="submit">Search</button>
      </div>
    </form>
    <section class="panel">
      <?php if ($rows->num_rows === 0): ?>
        <p class="empty">No trainings found.</p>
      <?php else: ?>
        <?php while ($t = $rows->fetch_assoc()): ?>
          <div class="list-item">
            <div>
              <h3><?= e($t['title']) ?></h3>
              <div class="meta">
                <?= e($t['provider_name']) ?> · <?= e(format_schedule($t['schedule'])) ?> · <?= e($t['venue']) ?>
                · <?= (int)$t['slots_left'] ?> slots left
              </div>
              <?php if ($t['description']): ?><div class="meta"><?= e($t['description']) ?></div><?php endif; ?>
            </div>
            <?php if ($t['already']): ?>
              <span class="badge badge-green">Enrolled</span>
            <?php else: ?>
              <form method="post">
                <input type="hidden" name="training_id" value="<?= (int)$t['training_id'] ?>">
                <button class="btn btn-outline btn-sm" type="submit">Enroll</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </section>
  </main>
</div>
<?php app_foot(); ?>
