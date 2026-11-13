-- Add payment records for deposits submitted when enrolling in a training.
CREATE TABLE IF NOT EXISTS payments (
    payment_id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrol_id              INT NOT NULL,
    amount                DECIMAL(10,2) NOT NULL,
    payment_method        ENUM('Mobile Money','Bank Transfer','Cash') NOT NULL,
    transaction_reference VARCHAR(100) NULL,
    status                ENUM('Pending','Confirmed','Rejected') NOT NULL DEFAULT 'Pending',
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at          DATETIME NULL,
    KEY idx_payments_enrol_created (enrol_id, created_at),
    CONSTRAINT fk_payments_enrolment
        FOREIGN KEY (enrol_id) REFERENCES enrolments (enrol_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
