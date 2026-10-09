<?php
require_once __DIR__ . '/auth.php';

function render_flash(): void
{
    $flash = flash_get();
    if (!$flash) {
        return;
    }
    $cls = $flash['type'] === 'success' ? 'alert-success' : 'alert-error';
    echo '<div class="alert ' . $cls . '">' . e($flash['message']) . '</div>';
}

function app_head(string $title): void
{
    $css = url('assets/css/style.css');
    echo '<!DOCTYPE html><html lang="en"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . e($title) . ' · Lakat</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="' . e($css) . '">';
    echo '</head><body>';
}

function app_foot(): void
{
    echo '</body></html>';
}

function sidebar_nav(array $items, string $active, string $logoutUrl): void
{
    echo '<aside class="sidebar">';
    echo '<div class="sidebar-brand"><span class="brand-mark" aria-hidden="true"></span><span class="brand-name">Lakat</span></div>';
    echo '<nav class="sidebar-nav">';
    foreach ($items as $href => $label) {
        $cls = ($href === $active) ? 'nav-item active' : 'nav-item';
        echo '<a class="' . $cls . '" href="' . e(url($href)) . '">' . e($label) . '</a>';
    }
    echo '</nav>';
    echo '<a class="nav-item logout" href="' . e(url($logoutUrl)) . '">Log out</a>';
    echo '</aside>';
}

function seeker_nav(string $active): void
{
    sidebar_nav([
        'seeker/dashboard.php'    => 'Dashboard',
        'seeker/trainings.php'    => 'Find Trainings',
        'seeker/jobs.php'         => 'Job Openings',
        'seeker/applications.php' => 'My Applications',
        'seeker/profile.php'      => 'My Profile',
    ], $active, 'logout.php');
}

function employer_nav(string $active): void
{
    sidebar_nav([
        'employer/dashboard.php' => 'Dashboard',
        'employer/post-job.php'  => 'Post a Job',
        'employer/my-jobs.php'   => 'My Job Postings',
        'employer/applicants.php'=> 'Applicants',
        'employer/profile.php'   => 'Business Profile',
    ], $active, 'logout.php');
}

function provider_nav(string $active): void
{
    sidebar_nav([
        'provider/dashboard.php'     => 'Dashboard',
        'provider/post-training.php' => 'Post a Training',
        'provider/my-trainings.php'  => 'My Trainings',
        'provider/enrollments.php'   => 'Enrollments',
        'provider/profile.php'       => 'Provider Profile',
    ], $active, 'logout.php');
}

function admin_nav(string $active): void
{
    sidebar_nav([
        'admin/dashboard.php'   => 'Dashboard',
        'admin/verify.php'      => 'Verify Accounts',
        'admin/reports.php'     => 'Pilot Reports',
    ], $active, 'logout.php');
}
