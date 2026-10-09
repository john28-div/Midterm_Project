<?php
require_once __DIR__ . '/includes/layout.php';

if (current_user()) {
    header('Location: ' . role_home(current_user()['role']));
    exit;
}

$error = '';
$role = $_POST['role'] ?? 'job_seeker';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'job_seeker';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid email or password.';
        } elseif ($user['status'] === 'suspended') {
            $error = 'This account is suspended. Contact PESO/GAD staff.';
        } elseif ($user['role'] !== $role && $user['role'] !== 'admin') {
            $error = 'That account is registered as ' . str_replace('_', ' ', $user['role']) . '. Choose the matching role.';
        } else {
            login_user($user);
            header('Location: ' . role_home($user['role']));
            exit;
        }
    }
}

$seekers = (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'job_seeker'")->fetch_assoc()['c'];
$employers = (int) $conn->query('SELECT COUNT(*) AS c FROM employers')->fetch_assoc()['c'];
$trainings = (int) $conn->query('SELECT COUNT(*) AS c FROM trainings')->fetch_assoc()['c'];

$roleLabels = [
    'job_seeker' => 'Log in as job seeker',
    'employer'   => 'Log in as employer',
    'provider'   => 'Log in as training provider',
];

app_head('Welcome');
?>
<div class="landing">
  <header class="landing-top">
    <div class="brand"><span class="brand-mark"></span> Lakat</div>
    <div class="muni">Municipality of Lambunao · SDG 5: Gender Equality</div>
  </header>

  <section class="hero">
    <div>
      <span class="pill">Trainings · Jobs · One place</span>
      <h1>Take the next step toward your own income.</h1>
      <p class="hero-copy">
        Find trainings from TESDA, schools and women's groups, and apply for jobs
        from local employers, without traveling from office to office.
      </p>
      <div class="stats">
        <div class="stat"><strong><?= $seekers ?></strong><span>women in our pilot</span></div>
        <div class="stat"><strong><?= $employers ?></strong><span>partner employers</span></div>
        <div class="stat"><strong><?= $trainings ?></strong><span>training programs</span></div>
      </div>
    </div>

    <div class="login-card">
      <h2>Welcome back</h2>
      <?php render_flash(); ?>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <form method="post" action="">
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
          <label for="email">Email address</label>
          <input id="email" type="email" name="email" placeholder="you@example.com" required
                 value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="field">
          <div class="field-row">
            <label for="password">Password</label>
            <span class="link-muted">Forgot password?</span>
          </div>
          <input id="password" type="password" name="password" required>
        </div>
        <button class="btn btn-primary" type="submit" id="loginBtn"><?= e($roleLabels[$role] ?? 'Log in') ?></button>
      </form>
      <p class="login-foot">New to Lakat? <a href="<?= e(url('register.php')) ?>">Create a free account</a></p>
    </div>
  </section>

  <section class="feature-row">
    <article class="feature-card">
      <h3>Find trainings</h3>
      <p>Search by schedule and barangay, then enroll online.</p>
    </article>
    <article class="feature-card">
      <h3>Apply for jobs</h3>
      <p>Browse openings from local employers and track your status.</p>
    </article>
    <article class="feature-card">
      <h3>Employers and providers</h3>
      <p>Post jobs and programs. PESO/GAD staff verify every account.</p>
    </article>
  </section>
</div>
<script>
  const labels = {
    job_seeker: 'Log in as job seeker',
    employer: 'Log in as employer',
    provider: 'Log in as training provider'
  };
  document.querySelectorAll('input[name="role"]').forEach((el) => {
    el.addEventListener('change', () => {
      document.getElementById('loginBtn').textContent = labels[el.value] || 'Log in';
    });
  });
</script>
<?php app_foot(); ?>
