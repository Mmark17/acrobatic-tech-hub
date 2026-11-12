# Acrobatic Tech Hub

Website and account portal for Acrobatic Tech Hub, built with PHP, MySQL, HTML, CSS, and JavaScript.

## Requirements

- PHP 8.0 or later with `mysqli` and `mbstring`
- MySQL or MariaDB
- Apache (XAMPP is suitable for local development)

## Local setup with XAMPP

1. Copy or clone this repository into `C:\xampp\htdocs\makangaweb`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open phpMyAdmin and import `makangaweb/database/database.sql` to create the database and tables.
   - This is a fresh-install script: it drops and recreates `makangaweb_db`. Do not import it into a database containing data you need.
4. For an existing installation, apply the needed additive scripts from `makangaweb/database/` instead. The password recovery feature requires `password_reset_migration.sql`.
5. Review `makangaweb/includes/db.php` and set the database connection values for your environment.
6. Visit `http://localhost/makangaweb/`.

Register an account to use the site. To make an account an administrator, update its role in the database:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your_email@example.com';
```

## Password recovery

Password reset email requires a working PHP mail transport. Configure the `APP_BASE_URL` and `MAIL_FROM_ADDRESS` environment variables and configure mail delivery in PHP. On a local XAMPP hostname (`localhost` or `127.0.0.1`), the site provides a local reset link if email delivery is not configured.

## Deployment

GitHub stores the source code; it does not run this PHP/MySQL application. Deploy the site to a PHP host with a MySQL-compatible database, configure database and mail settings for that host, and import the schema using the host's database tools.

User-uploaded profile photos under `makangaweb/uploads/profile/` are excluded from Git. They are private runtime data and should not be published in a public repository.
