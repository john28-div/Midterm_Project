<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('job_seeker');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $contact = trim($_POST['contact_no'] ?? '');
    $stmt = $conn->prepare('UPDATE users SET full_name = ?, barangay = ?, contact_no = ? WHERE user_id = ?');
    $uid = (int) $user['user_id'];
    $stmt->bind_param('sssi', $name, $barangay, $contact, $uid);
    $stmt->execute();
    $stmt->close();
    $_SESSION['user']['full_name'] = $name;
    $_SESSION['user']['barangay'] = $barangay;
    $_SESSION['user']['contact_no'] = $contact;
    $user = current_user();
    flash_set('success', 'Profile updated.');
    header('Location: ' . url('seeker/profile.php'));
    exit;
}

app_head('My Profile');
?>
<div class="app-shell">
  <?php seeker_nav('seeker/profile.php'); ?>
  <main class="main">
    <?php render_flash(); ?>
    <div class="page-head">
      <div>
        <h1>My profile</h1>
        <p class="sub">Keep your details updated so employers and providers can reach you.</p>
      </div>
    </div>
    <form class="panel form-card" method="post" style="max-width:640px">
      <div class="field">
        <label>Full name</label>
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
            <option value="">Select</option>
            <?php foreach (barangays() as $b): ?>
              <option value="<?= e($b) ?>" <?= ($user['barangay'] ?? '') === $b ? 'selected' : '' ?>><?= e($b) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Contact number</label>
          <input name="contact_no" value="<?= e($user['contact_no'] ?? '') ?>" placeholder="09XX XXX XXXX">
        </div>
      </div>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save profile</button>
      </div>
    </form>
  </main>
</div>
<?php app_foot(); ?>
