DROP TABLE IF EXISTS `admin_password_resets`;

CREATE TABLE `admin_password_resets` (
`email` TEXT NOT NULL,
`token` TEXT NOT NULL,
`created_at` timestamp NULL DEFAULT NULL
);
