<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('provider');
$uid = (int) $user['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $schedule = trim($_POST['schedule'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $slots = (int) ($_POST['slots'] ?? 0);

    if ($title === '' || $schedule === '' || $venue === '' || $slots < 1) {
        $error = 'Title, schedule, venue, and slots are required.';
    } else {
        $scheduleSql = date('Y-m-d H:i:s', strtotime($schedule));
        $stmt = $conn->prepare(
            'INSERT INTO trainings (provider_user_id, title, description, schedule, venue, slots)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issssi', $uid, $title, $description, $scheduleSql, $venue, $slots);
        if ($stmt->execute()) {
            flash_set('success', 'Training published.');
            header('Location: ' . url('provider/my-trainings.php'));
            exit;
        }
        $error = 'Could not save training.';
        $stmt->close();
    }
}

app_head('Post a Training');
?>
<div class="app-shell">
  <?php provider_nav('provider/post-training.php'); ?>
  <main class="main">
    <div class="page-head">
      <div>
        <h1>Post a training</h1>
        <p class="sub">Women in Lambunao can enroll once this is published.</p>
      </div>
    </div>
    <form class="panel form-card" method="post" style="max-width:720px">
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <div class="field">
        <label>Training title</label>
        <input name="title" required value="<?= e($_POST['title'] ?? '') ?>" placeholder="Basic Dressmaking (NC II)">
      </div>
      <div class="two-col">
        <div class="field">
          <label>Schedule</label>
          <input type="datetime-local" name="schedule" required value="<?= e($_POST['schedule'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Slots</label>
          <input type="number" name="slots" min="1" required value="<?= e($_POST['slots'] ?? '20') ?>">
        </div>
      </div>
      <div class="field">
        <label>Venue (barangay)</label>
        <select name="venue" required>
          <option value="">Select</option>
          <?php foreach (barangays() as $b): ?>
            <option value="<?= e($b) ?>" <?= ($_POST['venue'] ?? '') === $b ? 'selected' : '' ?>><?= e($b) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Description</label>
        <textarea name="description" rows="4"><?= e($_POST['description'] ?? '') ?></textarea>
      </div>
      <div class="form-actions">
        <a class="btn btn-outline" href="<?= e(url('provider/dashboard.php')) ?>">Cancel</a>
        <button class="btn btn-primary" type="submit">Publish training</button>
      </div>
    </form>
  </main>
</div>
<?php app_foot(); ?>
