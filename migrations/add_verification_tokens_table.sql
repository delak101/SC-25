-- Add verification_tokens table for email verification functionality
-- This table stores temporary tokens for email verification during user registration

CREATE TABLE IF NOT EXISTS `verification_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `type` enum('email_verification', 'password_reset', 'phone_verification') NOT NULL DEFAULT 'email_verification',
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `expires_at` (`expires_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes for better performance
CREATE INDEX `idx_verification_tokens_user_type` ON `verification_tokens` (`user_id`, `type`);
CREATE INDEX `idx_verification_tokens_token_expires` ON `verification_tokens` (`token`, `expires_at`);
