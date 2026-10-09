-- =====================================================================
-- Lakat: Women's Skills Training and Job-Matching Platform (Lambunao)
-- CC 207 Midterm Project - Group 10, Section 3-C
-- Database schema (MySQL 8 / MariaDB 10.4+, as bundled with XAMPP)
-- Matches the submitted ERD: 6 tables, 3NF
--   users, employers, trainings, enrollments, jobs, applications
-- =====================================================================

CREATE DATABASE IF NOT EXISTS lakat_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE lakat_db;

-- Drop in reverse dependency order so the script can be re-run safely
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS trainings;
DROP TABLE IF EXISTS employers;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------------------
-- 1. users
--    One table for all roles: job seeker, provider, employer, admin.
-- ---------------------------------------------------------------------
CREATE TABLE users (
  user_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,            -- use PHP password_hash()
  role          ENUM('job_seeker','provider','employer','admin') NOT NULL DEFAULT 'job_seeker',
  barangay      VARCHAR(100) NULL,
  contact_no    VARCHAR(20)  NULL,
  status        ENUM('pending','active','suspended') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role),
  KEY idx_users_barangay (barangay)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. employers  (one-to-one with users who have role = 'employer')
--    is_verified is set by PESO / GAD admin staff.
-- ---------------------------------------------------------------------
CREATE TABLE employers (
  employer_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED NOT NULL,
  business_name VARCHAR(150) NOT NULL,
  address       VARCHAR(255) NULL,
  is_verified   TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (employer_id),
  UNIQUE KEY uq_employers_user (user_id),
  CONSTRAINT fk_employers_user
    FOREIGN KEY (user_id) REFERENCES users (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. trainings  (posted by a provider user)
-- ---------------------------------------------------------------------
CREATE TABLE trainings (
  training_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  provider_user_id INT UNSIGNED NOT NULL,
  title            VARCHAR(150) NOT NULL,
  description      TEXT NULL,
  schedule         DATETIME NOT NULL,
  venue            VARCHAR(200) NOT NULL,
  slots            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (training_id),
  KEY idx_trainings_schedule (schedule),
  KEY idx_trainings_provider (provider_user_id),
  CONSTRAINT fk_trainings_provider
    FOREIGN KEY (provider_user_id) REFERENCES users (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. enrollments  (users <-> trainings, many-to-many)
-- ---------------------------------------------------------------------
CREATE TABLE enrollments (
  enrollment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED NOT NULL,
  training_id   INT UNSIGNED NOT NULL,
  status        ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  enrolled_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (enrollment_id),
  UNIQUE KEY uq_enrollment (user_id, training_id),   -- no double enrollment
  KEY idx_enrollments_training (training_id),
  CONSTRAINT fk_enrollments_user
    FOREIGN KEY (user_id) REFERENCES users (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_enrollments_training
    FOREIGN KEY (training_id) REFERENCES trainings (training_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. jobs  (posted by an employer)
-- ---------------------------------------------------------------------
CREATE TABLE jobs (
  job_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  employer_id INT UNSIGNED NOT NULL,
  title       VARCHAR(150) NOT NULL,
  description TEXT NULL,
  location    VARCHAR(150) NOT NULL,              -- barangay / place of work
  deadline    DATE NOT NULL,
  status      ENUM('open','closed','filled') NOT NULL DEFAULT 'open',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (job_id),
  KEY idx_jobs_location (location),
  KEY idx_jobs_deadline (deadline),
  KEY idx_jobs_status (status),
  KEY idx_jobs_employer (employer_id),
  CONSTRAINT fk_jobs_employer
    FOREIGN KEY (employer_id) REFERENCES employers (employer_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. applications  (users <-> jobs, many-to-many)
-- ---------------------------------------------------------------------
CREATE TABLE applications (
  application_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id        INT UNSIGNED NOT NULL,
  job_id         INT UNSIGNED NOT NULL,
  status         ENUM('submitted','under_review','interview','hired','rejected','withdrawn')
                 NOT NULL DEFAULT 'submitted',
  applied_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (application_id),
  UNIQUE KEY uq_application (user_id, job_id),       -- one application per job
  KEY idx_applications_job (job_id),
  CONSTRAINT fk_applications_user
    FOREIGN KEY (user_id) REFERENCES users (user_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_applications_job
    FOREIGN KEY (job_id) REFERENCES jobs (job_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Pilot indicator queries (for GAD reports; see proposal section 1.4)
-- ---------------------------------------------------------------------
-- Women registered:        SELECT COUNT(*) FROM users WHERE role = 'job_seeker';
-- Trainings posted:        SELECT COUNT(*) FROM trainings;
-- Employers registered:    SELECT COUNT(*) FROM employers;
-- Training enrollments:    SELECT COUNT(*) FROM enrollments;
-- Job applications:        SELECT COUNT(*) FROM applications;
-- Women hired:             SELECT COUNT(*) FROM applications WHERE status = 'hired';
