<?php
require_once dirname(__DIR__) . '/includes/layout.php';
$user = require_role('employer');
$employer = get_employer_for_user($conn, (int) $user['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $contact = trim($_POST['contact_no'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $business = trim($_POST['business_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $uid = (int) $user['user_id'];
    $eid = (int) $employer['employer_id'];

    $stmt = $conn->prepare('UPDATE users SET full_name = ?, contact_no = ?, barangay = ? WHERE user_id = ?');
    $stmt->bind_param('sssi', $name, $contact, $barangay, $uid);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('UPDATE employers SET business_name = ?, address = ? WHERE employer_id = ?');
    $stmt->bind_param('ssi', $business, $address, $eid);
    $stmt->execute();
    $stmt->close();

    $_SESSION['user']['full_name'] = $name;
    $_SESSION['user']['contact_no'] = $contact;
    $_SESSION['user']['barangay'] = $barangay;
    flash_set('success', 'Business profile updated.');
    header('Location: ' . url('employer/profile.php'));
    exit;
}

$employer = get_employer_for_user($conn, (int) $user['user_id']);
$user = current_user();

app_head('Business Profile');
?>
<div class="app-shell">
  <?php employer_nav('employer/profile.php'); ?>
  <main class="main">
    <?php render_flash(); ?>
    <div class="page-head">
      <div>
        <h1>Business profile</h1>
        <p class="sub">
          <?= $employer['is_verified'] ? 'Verified by PESO/GAD staff.' : 'Pending PESO/GAD verification.' ?>
        </p>
      </div>
    </div>
    <form class="panel form-card" method="post" style="max-width:680px">
      <div class="field">
        <label>Business name</label>
        <input name="business_name" required value="<?= e($employer['business_name']) ?>">
      </div>
      <div class="field">
        <label>Contact person</label>
        <input name="full_name" required value="<?= e($user['full_name']) ?>">
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
      <div class="field">
        <label>Address</label>
        <input name="address" value="<?= e($employer['address'] ?? '') ?>">
      </div>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit">Save profile</button>
      </div>
    </form>
  </main>
</div>
<?php app_foot(); ?>
