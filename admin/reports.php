<?php
require_once dirname(__DIR__) . '/includes/layout.php';
require_role('admin');

$indicators = [
    ['Women registered', (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'job_seeker'")->fetch_assoc()['c'], 30],
    ['Training programs posted', (int) $conn->query('SELECT COUNT(*) AS c FROM trainings')->fetch_assoc()['c'], 2],
    ['Employers registered', (int) $conn->query('SELECT COUNT(*) AS c FROM employers')->fetch_assoc()['c'], 3],
    ['Training enrollments', (int) $conn->query('SELECT COUNT(*) AS c FROM enrollments')->fetch_assoc()['c'], 15],
    ['Job applications submitted', (int) $conn->query('SELECT COUNT(*) AS c FROM applications')->fetch_assoc()['c'], 10],
    ['Women hired through the platform', (int) $conn->query("SELECT COUNT(*) AS c FROM applications WHERE status = 'hired'")->fetch_assoc()['c'], 2],
];

app_head('Pilot Reports');
?>
<div class="app-shell">
  <?php admin_nav('admin/reports.php'); ?>
  <main class="main">
    <div class="page-head">
      <div>
        <h1>Pilot reports</h1>
        <p class="sub">GAD indicators from live SQL counts.</p>
      </div>
    </div>
    <section class="panel">
      <table class="table">
        <thead>
          <tr><th>Indicator</th><th>Current</th><th>Target</th><th>Progress</th></tr>
        </thead>
        <tbody>
          <?php foreach ($indicators as [$label, $current, $target]): ?>
            <?php $pct = $target > 0 ? min(100, (int) round(($current / $target) * 100)) : 0; ?>
            <tr>
              <td><?= e($label) ?></td>
              <td><strong><?= $current ?></strong></td>
              <td><?= $target ?></td>
              <td>
                <span class="badge <?= $pct >= 100 ? 'badge-green' : 'badge-orange' ?>"><?= $pct ?>%</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </main>
</div>
<?php app_foot(); ?>
