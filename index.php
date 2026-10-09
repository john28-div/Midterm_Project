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

// Live counts from lakat_db (pilot targets as fallback if empty)
$seekers = 0;
$employers = 0;
$trainings = 0;
try {
    $seekers = (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'job_seeker'")->fetch_assoc()['c'];
    $employers = (int) $conn->query('SELECT COUNT(*) AS c FROM employers')->fetch_assoc()['c'];
    $trainings = (int) $conn->query('SELECT COUNT(*) AS c FROM trainings')->fetch_assoc()['c'];
} catch (Throwable $e) {
    // schema not imported yet
}
if ($seekers === 0) {
    $seekers = 30;
}
if ($employers === 0) {
    $employers = 3;
}
if ($trainings === 0) {
    $trainings = 2;
}

$roleLabels = [
    'job_seeker' => 'Log in as job seeker',
    'employer'   => 'Log in as employer',
    'provider'   => 'Log in as training provider',
];

app_head('Welcome');
?>
<div class="landing">
  <div class="landing-glow" aria-hidden="true"></div>

  <header class="landing-top">
    <a class="brand" href="<?= e(url('index.php')) ?>">
      <span class="brand-mark brand-mark-leaf" aria-hidden="true"></span>
      <span>Lakat</span>
    </a>
    <p class="muni">Municipality of Lambunao · SDG 5: Gender Equality</p>
  </header>

  <section class="hero">
    <div class="hero-copy-block">
      <span class="pill">Trainings · Jobs · One place</span>
      <h1>Take the next step toward your own income.</h1>
      <p class="hero-copy">
        Find trainings from TESDA, schools and women's groups, and apply for jobs
        from local employers, without traveling from office to office.
      </p>
      <div class="stats" aria-label="Pilot indicators">
        <div class="stat">
          <strong><?= $seekers ?></strong>
          <span>women in our pilot</span>
        </div>
        <div class="stat">
          <strong><?= $employers ?></strong>
          <span>partner employers</span>
        </div>
        <div class="stat">
          <strong><?= $trainings ?></strong>
          <span>training programs</span>
        </div>
      </div>
    </div>

    <div class="login-card">
      <h2>Welcome back</h2>
      <?php render_flash(); ?>
      <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= e(url('index.php')) ?>" novalidate>
        <p class="login-as-label">Log in as:</p>
        <div class="role-toggle" role="radiogroup" aria-label="Account type">
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
          <input
            id="email"
            type="email"
            name="email"
            autocomplete="username"
            placeholder="you@example.com"
            required
            value="<?= e($_POST['email'] ?? '') ?>"
          >
        </div>

        <div class="field">
          <div class="field-row">
            <label for="password">Password</label>
            <a class="link-muted" href="#" onclick="alert('Ask PESO/GAD staff to reset your password.'); return false;">Forgot password?</a>
          </div>
          <input
            id="password"
            type="password"
            name="password"
            autocomplete="current-password"
            placeholder="••••••••"
            required
          >
        </div>

        <button class="btn btn-primary" type="submit" id="loginBtn">
          <?= e($roleLabels[$role] ?? 'Log in') ?>
        </button>
      </form>

      <p class="login-foot">
        New to Lakat?
        <a href="<?= e(url('register.php')) ?>">Create a free account</a>
      </p>
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
(function () {
  var labels = {
    job_seeker: 'Log in as job seeker',
    employer: 'Log in as employer',
    provider: 'Log in as training provider'
  };
  var btn = document.getElementById('loginBtn');
  document.querySelectorAll('input[name="role"]').forEach(function (el) {
    el.addEventListener('change', function () {
      btn.textContent = labels[el.value] || 'Log in';
    });
  });
})();
</script>
<?php app_foot(); ?>
