# Lakat: A Women's Skills Training and Job-Matching Platform for Lambunao

**CC 207: Application Development and Emerging Technologies, Midterm Project**
Group 10 · Section 3-C · Instructor: Joyce C. Limua

**Members:** Kate Catlyn Artagame, Sherylyn Camarista, Marielle Bentillo

**SDG addressed:** SDG 5: Gender Equality (Targets 5.5 and 5.a)

## About

Lakat puts trainings, job openings, and applications in one website for the women of Lambunao. Job seekers can find trainings by schedule and barangay, enroll online, and apply for jobs. Employers post jobs and see applicants. Training providers post programs and see who enrolled. PESO/GAD staff act as admins who verify employers and view pilot reports.

## Tech Stack

| Layer | Technology |
| --- | --- |
| Front end | HTML, CSS, JavaScript |
| Back end | Plain PHP (7.4 or newer) |
| Database | MySQL / MariaDB (bundled with XAMPP) |
| Local environment | XAMPP + phpMyAdmin |

Architecture: three-tier client-server (browser → PHP → MySQL).

## Project structure

```
files/
  index.php              Landing + login
  register.php           Create account
  logout.php
  test_db.php            Connection smoke test
  config/db.php          MySQL connection
  database/schema.sql    Creates lakat_db + 6 tables
  database/seed.php      Demo data (password123)
  assets/css/style.css
  includes/              Auth + layout helpers
  seeker/                Job seeker pages
  employer/              Employer pages (Post a Job, applicants…)
  provider/              Training provider pages
  admin/                 PESO/GAD verify + reports
```

## Setup (XAMPP)

1. **Copy this folder** into XAMPP's web root as `lakat`:
   ```text
   C:\xampp\htdocs\lakat
   ```
   (macOS/Linux: `/opt/lampp/htdocs/lakat`)

2. **Start Apache and MySQL** in the XAMPP Control Panel.

3. **Create the database.** In phpMyAdmin → **Import** → choose `database/schema.sql` → **Go**.
   Or from a terminal:
   ```bash
   mysql -u root < database/schema.sql
   ```

4. **Load demo data** (optional but recommended):
   - Open <http://localhost/lakat/database/seed.php>
   - Or run: `php database/seed.php`

5. **Test the connection:** open <http://localhost/lakat/test_db.php>.
   You should see `Connected! Users in database: …`.

6. **Open the app:** <http://localhost/lakat/>

### Demo accounts (after seeding)

Password for all: `password123`

| Role | Email |
| --- | --- |
| Job seeker | maria@example.com |
| Employer | employer@example.com |
| Training provider | tesda@example.com |
| Admin (PESO/GAD) | admin@lambunao.gov.ph |

If MySQL uses a password, edit `config/db.php`.

## Database

Six tables, 3NF, matching the ERD. Definitions: [`database/schema.sql`](database/schema.sql) (same content as root `schema.sql`).

| Table | Purpose |
| --- | --- |
| `users` | All accounts; `role` = job_seeker, provider, employer, or admin |
| `employers` | Business details; `is_verified` set by admin |
| `trainings` | Programs posted by providers |
| `enrollments` | Users ↔ trainings |
| `jobs` | Openings posted by employers |
| `applications` | Users ↔ jobs |

Re-running `schema.sql` drops and recreates tables (**erases data**).

## Screens connected to SQL

- **Landing / login** — stats from `users`, `employers`, `trainings`; login reads `users`
- **Job seeker dashboard** — enrollments, applications, trainings, jobs
- **Post a Job** — inserts into `jobs`; recent postings + applicant counts from SQL
- **Applicants / enrollments** — status updates written back to MySQL
- **Admin reports** — live pilot indicator counts

## Pilot Targets

| Indicator | Target |
| --- | --- |
| Women registered | 30 |
| Training programs posted | 2 |
| Employers registered | 3 |
| Training enrollments | 15 |
| Job applications submitted | 10 |
| Women hired through the platform | 2 |

## Troubleshooting

- **MySQL won't start:** another service may be using port 3306.
- **Access denied for user 'root':** check `config/db.php`.
- **Unknown database 'lakat_db':** import `database/schema.sql` first.
- **Blank page / path errors:** the folder must be named `lakat` under `htdocs`, or open via the correct virtual path.
