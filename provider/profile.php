<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('provider');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $contact = trim($_POST['contact_no'] ?? '');
    $uid = (int) $user['user_id'];
    $stmt = $conn->prepare('UPDATE users SET full_name = ?, barangay = ?, contact_no = ? WHERE user_id = ?');
    $stmt->bind_param('sssi', $name, $barangay, $contact, $uid);
    $stmt->execute();
    $stmt->close();
    $_SESSION['user']['full_name'] = $name;
    $_SESSION['user']['barangay'] = $barangay;
    $_SESSION['user']['contact_no'] = $contact;
    flash_set('success', 'Provider profile updated.');
    header('Location: ' . url('provider/profile.php'));
    exit;
}

$user = current_user();
app_head('Provider Profile');
?>
<div class="app-shell">
  <?php provider_nav('provider/profile.php'); ?>
  <main class="main">
    <?php render_flash(); ?>
    <div class="page-head">
      <div>
        <h1>Provider profile</h1>
        <p class="sub">Organization details shown on training listings.</p>
      </div>
    </div>
    <form class="panel form-card" method="post" style="max-width:640px">
      <div class="field">
        <label>Organization / provider name</label>
        <input name="full_name" required value="<?= e($user['full_name']) ?>">
      </div>
      <div class="field">
        <label>Email</label>
        <input value="<?= e($user['email']) ?>" disabled>
      </div>
      <div class="two-col">
        <div class="field">
          <label>Barangay</label>
          <select name="barangay">
            <?php foreach (barangays() as $b): ?>
              <option value="<?= e($b) ?>" <?= ($user['barangay'] ?? '') === $b ? 'selected' : '' ?>><?= e($b) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Contact number</label>
          <input name="contact_no" value="<?= e($user['contact_no'] ?? '') ?>">
        </div>
      </div>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save profile</button>
      </div>
    </form>
  </main>
</div>
<?php app_foot(); ?>
