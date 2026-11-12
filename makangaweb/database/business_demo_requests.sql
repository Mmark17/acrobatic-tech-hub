-- Add database-backed sample business requests to makangaweb_db.
-- Safe to run more than once; this migration does not drop existing data.
USE makangaweb_db;

CREATE TABLE IF NOT EXISTS business_demo_requests (
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

CREATE TABLE IF NOT EXISTS business_demo_request_items (
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
