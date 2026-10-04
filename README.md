# EcoleLangue — Language School Management Platform

EcoleLangue is a lightweight language-school management application built with PHP and MySQL. Students can browse upcoming courses, send enrollment requests, and manage their profiles. Administrators can manage courses, students, enrollment decisions, and payment status.

## Features

- Public course catalogue showing future sessions, availability, dates, and prices
- Separate student and administrator sign-in with hashed passwords
- Student profile editing and enrollment request history
- Transactional enrollment and cancellation with accurate seat counts
- Course management with date, price, and capacity validation
- Admin dashboard, student list, and enrollment/payment actions
- Prepared PDO statements, role-based session checks, CSRF protection, and escaped output
- Responsive interface using Bootstrap 5
- Branded public course catalogue, split-screen sign-in, and compact role-based dashboard navigation

## Stack

- PHP 8.1+
- MySQL 8+
- PDO
- Bootstrap 5.3
- HTML and CSS
- PHP sessions

## Visual identity

The interface uses a navy, blue, and light-neutral palette with local SVG EcoleLangue logo and education illustration assets in `public/assets/`.

## Screenshots

Add screenshots of the public course catalogue, student area, and admin dashboard here:

| Public catalogue | Student profile | Admin dashboard |
| --- | --- | --- |
| `screenshots/home.png` | `screenshots/student-profile.png` | `screenshots/admin-dashboard.png` |

## Installation

1. Install PHP 8.1+ with the PDO MySQL extension and MySQL 8+.
2. Create the database and load its schema and sample courses:

   ```sh
   mysql -u root -p < database/database.sql
   ```

3. Configure the database connection. The defaults are `127.0.0.1`, database `language_school`, user `root`, and an empty password. Override them with environment variables if needed:

   ```sh
   export DB_HOST=127.0.0.1
   export DB_NAME=language_school
   export DB_USER=root
   export DB_PASSWORD='your-local-password'
   ```

4. Create or refresh the demonstration accounts:

   ```sh
   php database/seed.php
   ```

   Test the database connection from the command line with `php database/test-connection.php`.

5. Start the built-in PHP web server with the public directory as its document root:

   ```sh
   php -S 127.0.0.1:8000 -t public
   ```

6. Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

Never deploy the PHP development server to production. Configure a production web server to use `public/` as its document root.

## Demonstration accounts

| Role | Login | Password |
| --- | --- | --- |
| Student | `student` | `Student123!` |
| Administrator | `admin` | `Admin123!` |

The seed script hashes these passwords with `password_hash()`. Change the demonstration credentials before using the project outside a local portfolio/demo environment.

## Project structure

```text
config/
  bootstrap.php       Session, escaping, CSRF, flash messages, access checks
  database.php        PDO connection and environment-based configuration
controllers/
  AdminController.php
  AuthController.php
  StudentController.php
database/
  database.sql        Tables, indexes, constraints, sample courses
  seed.php            Hashed demonstration student and administrator accounts
  test-connection.php CLI-only database connectivity check
models/
  Admin.php
  Course.php
  Registration.php
  Student.php
public/
  assets/
    illustrations/language-world.svg
    logo/             EcoleLangue logo and favicon variants
  css/style.css
  js/app.js           Password visibility toggle
  index.php           Front controller and route dispatch
tests/
  qa-smoke.php        Database-backed HTTP and workflow regression checks
views/
  admin/
  auth/
  student/
  home.php
  layout.php
```

## Notes

- Enrollment is unique per student and course, including previously cancelled requests, as enforced by the database constraint.
- New registrations reserve a seat immediately; deleting a pending request or cancelling an enrollment restores it.
- Payments are represented by the required `paiement` status on each enrollment; there is no separate payment gateway.
- With MySQL running and the application server available at `http://127.0.0.1:8000`, run `php tests/qa-smoke.php` for the automated regression suite. The suite creates and removes temporary test data.
