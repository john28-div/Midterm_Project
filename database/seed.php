<?php
/**
 * Seed demo data for Lakat.
 * Run once after importing schema.sql:
 *   php database/seed.php
 * Or open http://localhost/lakat/database/seed.php in the browser.
 *
 * All demo accounts use password: password123
 */
require_once dirname(__DIR__) . '/config/db.php';

$pwd = password_hash('password123', PASSWORD_DEFAULT);

$conn->query('SET FOREIGN_KEY_CHECKS = 0');
$conn->query('TRUNCATE TABLE applications');
$conn->query('TRUNCATE TABLE enrollments');
$conn->query('TRUNCATE TABLE jobs');
$conn->query('TRUNCATE TABLE trainings');
$conn->query('TRUNCATE TABLE employers');
$conn->query('TRUNCATE TABLE users');
$conn->query('SET FOREIGN_KEY_CHECKS = 1');

function insert_user(mysqli $conn, string $name, string $email, string $hash, string $role, ?string $barangay, ?string $contact): int {
    $stmt = $conn->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, barangay, contact_no, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $status = ($role === 'employer' || $role === 'provider') ? 'pending' : 'active';
    if ($role === 'admin') {
        $status = 'active';
    }
    // Demo accounts are already usable
    $status = 'active';
    $stmt->bind_param('sssssss', $name, $email, $hash, $role, $barangay, $contact, $status);
    $stmt->execute();
    $id = (int) $stmt->insert_id;
    $stmt->close();
    return $id;
}

$adminId = insert_user($conn, 'PESO Admin', 'admin@lambunao.gov.ph', $pwd, 'admin', 'Poblacion Ilaya', '0331234567');
$mariaId = insert_user($conn, 'Maria Santos', 'maria@example.com', $pwd, 'job_seeker', 'Poblacion Ilawod', '09171234567');
$anaId = insert_user($conn, 'Ana Reyes', 'ana@example.com', $pwd, 'job_seeker', 'Poblacion Ilaya', '09181234567');

$tesdaId = insert_user($conn, 'TESDA Lambunao', 'tesda@example.com', $pwd, 'provider', 'Poblacion Ilaya', '0337654321');
$pesoId = insert_user($conn, 'Municipal PESO', 'peso@example.com', $pwd, 'provider', 'Poblacion Ilawod', '0331112233');
$womenId = insert_user($conn, "Women's Livelihood Group", 'women@example.com', $pwd, 'provider', 'Bonbon', '09190001122');

$empUser1 = insert_user($conn, 'Juan Dela Cruz', 'employer@example.com', $pwd, 'employer', 'Poblacion Ilawod', '09192223344');
$empUser2 = insert_user($conn, 'Liza Mercado', 'market@example.com', $pwd, 'employer', 'Poblacion Ilaya', '09193334455');
$empUser3 = insert_user($conn, 'Carlo Gomez', 'cafe@example.com', $pwd, 'employer', 'Jayubo', '09194445566');

$stmt = $conn->prepare(
    'INSERT INTO employers (user_id, business_name, address, is_verified) VALUES (?, ?, ?, ?)'
);
$verified = 1;
$biz1 = 'Verified employer';
$addr1 = 'Poblacion Ilawod, Lambunao';
$stmt->bind_param('issi', $empUser1, $biz1, $addr1, $verified);
$stmt->execute();
$employer1 = (int) $stmt->insert_id;

$biz2 = 'Mercado Tailoring';
$addr2 = 'Poblacion Ilaya, Lambunao';
$stmt->bind_param('issi', $empUser2, $biz2, $addr2, $verified);
$stmt->execute();
$employer2 = (int) $stmt->insert_id;

$biz3 = 'Gomez Kitchen';
$addr3 = 'Jayubo, Lambunao';
$stmt->bind_param('issi', $empUser3, $biz3, $addr3, $verified);
$stmt->execute();
$employer3 = (int) $stmt->insert_id;
$stmt->close();

$trainings = [
    [$tesdaId, 'Basic Dressmaking (NC II)', 'Learn basic garment construction and finishing for NC II.', '2026-10-13 13:00:00', 'Poblacion Ilaya', 20],
    [$pesoId, 'Food Processing', 'Preserve and package local food products for micro-enterprise.', '2026-10-11 08:00:00', 'Poblacion Ilawod', 8],
    [$womenId, 'Digital Skills for Online Selling', 'Use social media and basic e-commerce tools to sell products.', '2026-10-17 14:00:00', 'Bonbon', 5],
];

$trainingIds = [];
$stmt = $conn->prepare(
    'INSERT INTO trainings (provider_user_id, title, description, schedule, venue, slots)
     VALUES (?, ?, ?, ?, ?, ?)'
);
foreach ($trainings as $t) {
    $stmt->bind_param('issssi', $t[0], $t[1], $t[2], $t[3], $t[4], $t[5]);
    $stmt->execute();
    $trainingIds[] = (int) $stmt->insert_id;
}
$stmt->close();

$jobs = [
    [$employer1, 'Sales Clerk', 'Assist customers, handle cash, and arrange stock. No experience needed; training provided.', 'Poblacion Ilawod', '2026-10-20', 'open'],
    [$employer2, 'Seamstress', 'Sew uniforms and simple garments. Basic sewing skills preferred.', 'Poblacion Ilaya', '2026-10-24', 'open'],
    [$employer3, 'Kitchen Helper', 'Help prepare meals and keep the kitchen clean during peak hours.', 'Jayubo', '2026-10-30', 'open'],
    [$employer1, 'Cashier', 'Handle POS transactions and daily cash reconciliation.', 'Poblacion Ilawod', '2026-10-15', 'closed'],
];

$jobIds = [];
$stmt = $conn->prepare(
    'INSERT INTO jobs (employer_id, title, description, location, deadline, status)
     VALUES (?, ?, ?, ?, ?, ?)'
);
foreach ($jobs as $j) {
    $stmt->bind_param('isssss', $j[0], $j[1], $j[2], $j[3], $j[4], $j[5]);
    $stmt->execute();
    $jobIds[] = (int) $stmt->insert_id;
}
$stmt->close();

// Maria enrollments & applications (matches dashboard mockup)
$stmt = $conn->prepare(
    'INSERT INTO enrollments (user_id, training_id, status, enrolled_at) VALUES (?, ?, ?, ?)'
);
$status = 'confirmed';
$enrolledAt = '2026-09-28 10:00:00';
$stmt->bind_param('iiss', $mariaId, $trainingIds[0], $status, $enrolledAt);
$stmt->execute();
$status = 'confirmed';
$enrolledAt = '2026-10-01 09:00:00';
$stmt->bind_param('iiss', $mariaId, $trainingIds[1], $status, $enrolledAt);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare(
    'INSERT INTO applications (user_id, job_id, status, applied_at) VALUES (?, ?, ?, ?)'
);
$st = 'under_review';
$at = '2026-10-06 11:00:00';
$stmt->bind_param('iiss', $mariaId, $jobIds[0], $st, $at);
$stmt->execute();
$st = 'interview';
$at = '2026-10-02 14:00:00';
$stmt->bind_param('iiss', $mariaId, $jobIds[1], $st, $at);
$stmt->execute();
$st = 'submitted';
$at = '2026-10-05 16:00:00';
$stmt->bind_param('iiss', $mariaId, $jobIds[2], $st, $at);
$stmt->execute();

// Extra applicants so employer "Recent postings" shows counts
$st = 'submitted';
$at = '2026-10-07 09:00:00';
$stmt->bind_param('iiss', $anaId, $jobIds[1], $st, $at);
$stmt->execute();
$stmt->bind_param('iiss', $anaId, $jobIds[2], $st, $at);
$stmt->execute();
$stmt->bind_param('iiss', $anaId, $jobIds[3], $st, $at);
$stmt->execute();
$stmt->close();

// A few more applications on employer1 jobs for counts
$extraSeekers = [
    insert_user($conn, 'Rosa Villanueva', 'rosa@example.com', $pwd, 'job_seeker', 'Agsirab', '09195556677'),
    insert_user($conn, 'Fe Castillo', 'fe@example.com', $pwd, 'job_seeker', 'Biri', '09196667788'),
    insert_user($conn, 'Gina Lopez', 'gina@example.com', $pwd, 'job_seeker', 'Cayang', '09197778899'),
];
$stmt = $conn->prepare(
    'INSERT INTO applications (user_id, job_id, status, applied_at) VALUES (?, ?, ?, ?)'
);
$st = 'submitted';
$at = '2026-10-08 10:00:00';
foreach ($extraSeekers as $sid) {
    $stmt->bind_param('iiss', $sid, $jobIds[1], $st, $at);
    $stmt->execute();
    $stmt->bind_param('iiss', $sid, $jobIds[3], $st, $at);
    $stmt->execute();
}
$stmt->close();

echo "Seed complete.\n";
echo "Demo logins (password: password123)\n";
echo "  Job seeker: maria@example.com\n";
echo "  Employer:   employer@example.com\n";
echo "  Provider:   tesda@example.com\n";
echo "  Admin:      admin@lambunao.gov.ph\n";
