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
4. For an existing installation, apply the needed additive scripts from `makangaweb/database/` instead. The password recovery feature requires `password_reset_migration.sql`; unique phone numbers require `unique_user_phone_migration.sql`; in-app admin replies require `message_replies_notifications_migration.sql`; course deposits require `training_payments_migration.sql`. The phone migration normalizes stored numbers, so resolve any numbers that become duplicates (such as `078...` and `+25678...`) before applying its unique index.
5. Review `makangaweb/includes/db.php` and set the database connection values for your environment.
6. Visit `http://localhost/makangaweb/`.

Register an account to use the site. To make an account an administrator, update its role in the database:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your_email@example.com';
```

## Password recovery

Password reset email requires a working PHP mail transport. Configure the `APP_BASE_URL` and `MAIL_FROM_ADDRESS` environment variables and configure mail delivery in PHP. Admin replies are saved in the recipient's account and appear in the notification bell; email delivery is attempted when mail is configured. A contact sender must use the email address of their registered account to receive in-app notifications.

## Deployment

### Deploying on Vercel

The root `Dockerfile.vercel` selects Vercel's Container Runtime and runs the PHP/Apache application. In Vercel, use the repository root as the project root (not the `makangaweb/` subdirectory) and set the framework preset to **Container** if Vercel does not select it automatically. The Container Runtime is currently in beta.

The app requires a separate, persistent MySQL or MariaDB database; do not run the local XAMPP database inside the Vercel container. Create a fresh database and import `makangaweb/database/database.sql` once. **That setup file drops and recreates `makangaweb_db`; never import it into a database containing data you need.**

Add these environment variables to the Vercel project for each environment:

- `DB_HOST`: database server hostname
- `DB_PORT`: database server port (usually `3306`)
- `DB_NAME`: database name
- `DB_USER`: database username
- `DB_PASS`: database password; save it as a secret

The database provider must allow connections from the deployed app. After the first deployment, open the Vercel project and choose **Visit** to view the site. Vercel's container filesystem is temporary, so profile-photo uploads and PHP file-based sessions are not durable across restarts or multiple instances; use shared storage/session infrastructure before relying on those features in production.

User-uploaded profile photos under `makangaweb/uploads/profile/` are excluded from Git. They are private runtime data and should not be published in a public repository.
