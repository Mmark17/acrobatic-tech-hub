-- =====================================================
-- MakangaWeb Studio - Soroti City
-- Database: makangaweb_db
-- Import via phpMyAdmin (Import tab) or: mysql -u root < database.sql
-- Existing installations: apply the additive migrations in database/.
-- Do not re-import this full setup file into an existing database; it starts by dropping that database.
-- =====================================================

DROP DATABASE IF EXISTS makangaweb_db;
CREATE DATABASE makangaweb_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE makangaweb_db;

-- -----------------------------------------------------
-- 1. users (registered clients and admins)
-- -----------------------------------------------------
CREATE TABLE users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(120) NOT NULL UNIQUE,
    phone       VARCHAR(20),
    profile_photo VARCHAR(255) NULL,
    password    VARCHAR(255) NOT NULL,          -- store password_hash() output
    role        ENUM('admin','client') NOT NULL DEFAULT 'client',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 1a. password reset tokens (only token hashes are stored)
-- -----------------------------------------------------
CREATE TABLE password_resets (
    reset_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (reset_id),
    UNIQUE KEY uq_password_resets_token_hash (token_hash),
    KEY idx_password_resets_user_created (user_id, created_at),
    CONSTRAINT fk_password_resets_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 2. projects (completed work shown on Projects page)
-- -----------------------------------------------------
CREATE TABLE projects (
    project_id      INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(150) NOT NULL,
    description     TEXT NOT NULL,
    technologies    VARCHAR(200),
    client_name     VARCHAR(120),
    status          ENUM('Completed','Ongoing') NOT NULL DEFAULT 'Completed',
    date_completed  DATE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 3. trainings (technical skills courses offered)
-- -----------------------------------------------------
CREATE TABLE trainings (
    training_id  INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(150) NOT NULL,
    description  TEXT,
    duration     VARCHAR(50),
    fee          DECIMAL(10,2) NULL DEFAULT NULL,       -- in UGX; NULL means fee to be confirmed
    start_date   DATE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 4. enrolments (users <-> trainings, many-to-many)
-- -----------------------------------------------------
CREATE TABLE enrolments (
    enrol_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    training_id  INT NOT NULL,
    enrol_date   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_training (user_id, training_id),  -- no double enrolment
    CONSTRAINT fk_enrol_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_enrol_training
        FOREIGN KEY (training_id) REFERENCES trainings(training_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 5. messages (Contact page form submissions)
-- -----------------------------------------------------
CREATE TABLE messages (
    message_id  INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(120) NOT NULL,
    subject     VARCHAR(150),
    message     TEXT NOT NULL,
    sent_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 6. business demo requests and their item snapshots
-- -----------------------------------------------------
CREATE TABLE business_demo_requests (
    request_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_slug VARCHAR(32) NOT NULL,
    request_type ENUM('order','booking','appointment','produce_order') NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(120) NOT NULL,
    customer_phone VARCHAR(20) NULL,
    requested_date DATE NOT NULL,
    checkout_date DATE NULL,
    stay_nights SMALLINT UNSIGNED NULL,
    estimated_total BIGINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('Pending','Reviewed','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (request_id),
    KEY idx_demo_requests_status_created (status, created_at),
    KEY idx_demo_requests_business_created (business_slug, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE business_demo_request_items (
    request_item_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id BIGINT UNSIGNED NOT NULL,
    item_name VARCHAR(120) NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL,
    unit_price INT UNSIGNED NOT NULL,
    line_total BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (request_item_id),
    KEY idx_demo_request_items_request (request_id),
    CONSTRAINT fk_demo_request_items_request
        FOREIGN KEY (request_id) REFERENCES business_demo_requests (request_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

INSERT INTO projects (title, description, technologies, client_name, status, date_completed) VALUES
('VoltPay Prepaid Power and Rent System',
 'Smart system for prepaid power monitoring and rent management for landlords and tenants.',
 'PHP, MySQL, ESP32, JavaScript', 'Final Year Research Project', 'Ongoing', NULL),
('Gas Guardian',
 'LPG leak detector and cylinder level monitor with alerts.',
 'ESP32, MQ-6, HX711, C++', 'Fundi Bots Internship', 'Completed', '2026-08-01'),
('School Website',
 'Responsive website for a local school with news and admissions pages.',
 'HTML, CSS, JavaScript, PHP', 'Soroti School', 'Completed', '2026-06-10'),
('Shop Website',
 'Online catalogue for a retail shop with WhatsApp ordering.',
 'HTML, CSS, PHP, MySQL', 'Soroti Shop', 'Completed', '2026-07-20');

INSERT INTO trainings (title, description, duration, fee, start_date) VALUES
('HTML and CSS Basics', 'Build and style your first responsive web pages.', '4 weeks', 150000.00, '2026-11-16'),
('PHP and MySQL Development', 'Dynamic websites with login, forms and databases.', '6 weeks', 250000.00, '2026-12-01'),
('Responsive Web Design', 'Mobile-first layouts with Flexbox, Grid and Bootstrap.', '3 weeks', 120000.00, '2026-12-15'),
('WordPress for Small Businesses', 'Launch and manage a business site without heavy coding.', '3 weeks', 130000.00, '2027-01-10'),
('SolidWorks for Mechanical Engineers', 'Learn parametric sketching, 3D part modelling, assemblies and engineering drawings through a practical mechanical design project.', '6 weeks', NULL, NULL),
('KiCad PCB Design', 'Create circuit schematics, assign footprints, lay out a printed circuit board, run design-rule checks and prepare manufacturing files.', '4 weeks', NULL, NULL);

-- Create the admin account:
-- 1) Register normally on register.php
-- 2) Then promote it by running (replace the email):
-- UPDATE users SET role = 'admin' WHERE email = 'your_email@example.com';
