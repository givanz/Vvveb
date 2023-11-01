DROP TABLE IF EXISTS `password_resets`;

CREATE TABLE `password_resets` (
`email` TEXT NOT NULL,
`token` TEXT NOT NULL,
`created_at` timestamp NULL DEFAULT NULL
);
