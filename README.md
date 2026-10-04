# Language School Management Platform

A responsive language school management platform built with PHP, PDO, MySQL and Bootstrap 5. It provides separate student and admin spaces to manage courses, enrollments, statuses and payment tracking.

## Preview

Screenshots have not been added yet. Capture the application screens and save them under `docs/screenshots/` using the filenames below.

| Page | Screenshot |
| --- | --- |
| Homepage | TODO: `docs/screenshots/homepage.png` |
| Student login | TODO: `docs/screenshots/student-login.png` |
| Student profile | TODO: `docs/screenshots/student-profile.png` |
| Student registrations | TODO: `docs/screenshots/student-registrations.png` |
| Admin dashboard | TODO: `docs/screenshots/admin-dashboard.png` |
| Admin registrations | TODO: `docs/screenshots/admin-registrations.png` |
| Admin courses | TODO: `docs/screenshots/admin-courses.png` |

## Features

### Student

- Secure login
- Profile management
- Browse available courses
- Request course enrollment
- View enrollment and payment statuses
- Cancel a pending enrollment

### Admin

- Secure admin login
- Dashboard statistics
- Create, view, update and delete courses
- View students
- Review and manage registrations
- Confirm or cancel registrations
- Mark registrations as paid

## Tech Stack

- PHP 8+
- PDO
- MySQL
- Bootstrap 5
- HTML5
- CSS3
- JavaScript
- PHP Sessions

## Security

- PDO prepared statements for database operations
- Password hashing and verification with `password_hash()` and `password_verify()`
- CSRF protection for state-changing requests
- Role-based authorization for student and admin areas
- Escaped dynamic output to help prevent cross-site scripting (XSS)
- Transactional enrollment and cancellation logic to keep course seat counts consistent

## Database

The database contains four main entities:

- **Etudiant** — student identity, contact details and login credentials
- **Cours** — language, level, dates, price and total/remaining seats
- **Inscription** — student/course enrollment, registration status and payment status
- **Admin** — administrator identity and login credentials

Foreign keys maintain enrollment relationships. A unique student/course constraint prevents duplicate registrations, and seat availability is tracked on each course.

## Project Structure

```text
config/
controllers/
database/
models/
public/
tests/
views/
docs/screenshots/
```

## Installation

Requirements: PHP 8+ with PDO MySQL and `mbstring`, and MySQL.

```sh
git clone https://github.com/med4py/language-school-management.git
cd language-school-management
mysql -u root < database/database.sql
php database/seed.php
php -S 127.0.0.1:8000 -t public
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

The default local database connection uses host `127.0.0.1`, database `language_school`, user `root`, and an empty password. Configure `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` environment variables before running the seed script or application if your local MySQL setup differs.

## Demo Accounts

**Student**

- Login: `student`
- Password: `Student123!`

**Admin**

- Login: `admin`
- Password: `Admin123!`

These credentials are for local demonstration only and should be changed in production.

## Testing

Run the existing automated QA suite while the PHP server and MySQL are available:

```sh
php tests/qa-smoke.php
```

The suite covers authentication, authorization, enrollment and course management workflows, CSRF rejection, SQL-injection-style login input, XSS output escaping, database integrity, and enrollment/seat transaction consistency.

## Responsive Design

The interface supports desktop, laptop, tablet and mobile layouts. Course listings and management controls adapt to smaller screens, while responsive tables and navigation remain accessible.

## Screenshots

No screenshots are currently available. Add the following files to `docs/screenshots/` when captured:

- `homepage.png`
- `student-login.png`
- `student-profile.png`
- `student-registrations.png`
- `admin-dashboard.png`
- `admin-registrations.png`
- `admin-courses.png`

## Future Improvements

- Pagination for larger course, student and registration lists
- Email notifications for enrollment status changes
- Docker-based local development setup
- Production deployment configuration
- Password reset workflow
- Expanded analytics for administrators

## Author

Ahmed Karkach

GitHub: [https://github.com/med4py](https://github.com/med4py)

## License

No license has been added yet.
