INSERT INTO `product_review` (`product_review_id`, `product_id`, `user_id`, `author`, `content`, `rating`, `status`, `parent_id`, `created_at`, `updated_at`) VALUES

(1,	1,	1,	'John Doe',	'Cool product!',			5,	1,	0,	'2022-05-01 00:00:00',	'2022-05-01 00:00:00'),
(2,	1,	1,	'John Doe',	'This is another review',	5,	1,	0,	'2022-05-01 00:00:00',	'2022-05-01 00:00:00'),
(3,	1,	1,	'John Doe',	'Pending review',			5,	0,	0,	'2022-05-01 00:00:00',	'2022-05-01 00:00:00'),
(4,	1,	1,	'John Doe',	'Spam review',				5,	2,	0,	'2022-05-01 00:00:00',	'2022-05-01 00:00:00'),
(5,	1,	1,	'John Doe',	'Trash review',				5,	3,	0,	'2022-05-01 00:00:00',	'2022-05-01 00:00:00');