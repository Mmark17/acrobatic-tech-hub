-- Normalize existing phone values to match the format used by the application.
-- For existing installations, run this UPDATE first, then check for duplicates:
-- SELECT phone, COUNT(*) FROM users GROUP BY phone HAVING COUNT(*) > 1;
-- Resolve duplicate accounts before running the ALTER TABLE statement below.
UPDATE users
SET phone = CASE
    WHEN CHAR_LENGTH(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', '')) = 10
         AND LEFT(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), 1) = '0'
        THEN CONCAT('+256', SUBSTRING(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), 2))
    WHEN CHAR_LENGTH(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', '')) = 12
         AND LEFT(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), 3) = '256'
        THEN CONCAT('+', REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''))
    ELSE REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', '')
END
WHERE phone IS NOT NULL AND TRIM(phone) <> '';

ALTER TABLE users
    ADD UNIQUE KEY uq_users_phone (phone);
