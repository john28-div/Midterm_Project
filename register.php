<?php
require_once __DIR__ . '/includes/layout.php';

if (current_user()) {
    header('Location: ' . role_home(current_user()['role']));
    exit;
}

$error = '';
$role = $_POST['role'] ?? 'job_seeker';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $barangay = trim($_POST['barangay'] ?? '');
    $contact = trim($_POST['contact_no'] ?? '');
    $business = trim($_POST['business_name'] ?? '');
    $role = $_POST['role'] ?? 'job_seeker';

    $allowed = ['job_seeker', 'employer', 'provider'];
    if (!in_array($role, $allowed, true)) {
        $error = 'Invalid account type.';
    } elseif ($fullName === '' || $email === '' || $password === '') {
        $error = 'Name, email, and password are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($role === 'employer' && $business === '') {
        $error = 'Business name is required for employers.';
    } else {
        $check = $conn->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        $check->close();

        if ($exists) {
            $error = 'That email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $status = 'active';
            $stmt = $conn->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, barangay, contact_no, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('sssssss', $fullName, $email, $hash, $role, $barangay, $contact, $status);
            if ($stmt->execute()) {
                $userId = (int) $stmt->insert_id;
                $stmt->close();

                if ($role === 'employer') {
                    $addr = $barangay !== '' ? $barangay . ', Lambunao' : 'Lambunao';
                    $verified = 0;
                    $emp = $conn->prepare(
                        'INSERT INTO employers (user_id, business_name, address, is_verified)
                         VALUES (?, ?, ?, ?)'
                    );
                    $emp->bind_param('issi', $userId, $business, $addr, $verified);
                    $emp->execute();
                    $emp->close();
                }

                flash_set('success', 'Account created. You can log in now.');
                header('Location: ' . url('index.php'));
                exit;
            }
            $error = 'Could not create account. Please try again.';
            $stmt->close();
        }
    }
}

app_head('Create account');
?>
<div class="landing">
  <header class="landing-top">
    <a class="brand" href="<?= e(url('index.php')) ?>"><span class="brand-mark"></span> Lakat</a>
    <div class="muni">Municipality of Lambunao · SDG 5: Gender Equality</div>
  </header>

  <div class="register-wrap">
    <div class="login-card">
      <h2>Create a free account</h2>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <div class="role-toggle">
          <label>
            <input type="radio" name="role" value="job_seeker" <?= $role === 'job_seeker' ? 'checked' : '' ?>>
            <span>Job Seeker</span>
          </label>
          <label>
            <input type="radio" name="role" value="employer" <?= $role === 'employer' ? 'checked' : '' ?>>
            <span>Employer</span>
          </label>
          <label>
            <input type="radio" name="role" value="provider" <?= $role === 'provider' ? 'checked' : '' ?>>
            <span>Training Provider</span>
          </label>
        </div>

        <div class="field">
          <label for="full_name">Full name</label>
          <input id="full_name" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="email">Email address</label>
          <input id="email" type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input id="password" type="password" name="password" required minlength="6">
        </div>
        <div class="two-col">
          <div class="field">
            <label for="barangay">Barangay</label>
            <select id="barangay" name="barangay">
              <option value="">Select barangay</option>
              <?php foreach (barangays() as $b): ?>
                <option value="<?= e($b) ?>" <?= ($_POST['barangay'] ?? '') === $b ? 'selected' : '' ?>><?= e($b) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="contact_no">Contact number</label>
            <input id="contact_no" name="contact_no" placeholder="09XX XXX XXXX" value="<?= e($_POST['contact_no'] ?? '') ?>">
          </div>
        </div>
        <div class="field" id="businessField" style="<?= $role === 'employer' ? '' : 'display:none' ?>">
          <label for="business_name">Business name</label>
          <input id="business_name" name="business_name" value="<?= e($_POST['business_name'] ?? '') ?>">
        </div>
        <button class="btn btn-primary" type="submit">Create account</button>
      </form>
      <p class="login-foot">Already have an account? <a href="<?= e(url('index.php')) ?>">Log in</a></p>
    </div>
  </div>
</div>
<script>
  document.querySelectorAll('input[name="role"]').forEach((el) => {
    el.addEventListener('change', () => {
      document.getElementById('businessField').style.display = el.value === 'employer' ? '' : 'none';
    });
  });
</script>
<?php app_foot(); ?>
