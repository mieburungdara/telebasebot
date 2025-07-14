CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `telegram_id` bigint(20) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `role` enum('member','editor','admin','superadmin') NOT NULL DEFAULT 'member',
  `points` int(11) NOT NULL DEFAULT 0,
  `is_banned` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `telegram_id` (`telegram_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message_id` bigint(20) NOT NULL,
  `type` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `caption` text DEFAULT NULL,
  `status` enum('pending','ready_review','forwarded','cancelled','deleted','error') NOT NULL DEFAULT 'pending',
  `auto_publish_at` timestamp NULL DEFAULT NULL,
  `editor_message_id` bigint(20) DEFAULT NULL,
  `public_message_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `message_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
