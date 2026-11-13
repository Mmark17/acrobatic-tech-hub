CREATE TABLE message_replies (
    reply_id    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id  INT NOT NULL,
    admin_id    INT NULL,
    reply_body  TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_message_replies_message_created (message_id, created_at),
    CONSTRAINT fk_message_replies_message
        FOREIGN KEY (message_id) REFERENCES messages (message_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_replies_admin
        FOREIGN KEY (admin_id) REFERENCES users (user_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    notification_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    reply_id        BIGINT UNSIGNED NOT NULL,
    read_at         DATETIME NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notifications_user_reply (user_id, reply_id),
    KEY idx_notifications_user_unread (user_id, read_at, created_at),
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_notifications_reply
        FOREIGN KEY (reply_id) REFERENCES message_replies (reply_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
