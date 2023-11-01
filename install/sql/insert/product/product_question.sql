INSERT INTO `product_question` (`product_question_id`, `product_id`, `user_id`, `author`, `content`, `status`, `parent_id`, `created_at`, `updated_at`) VALUES

(1,	1,	1,	'John Doe',	'How does this product work?',	1,	0,	'2022-05-01 00:00:00',	'2022-05-01 00:00:00'),
(2,	1,	1,	'John Doe',	'This is another question?',	1,	0,	'2022-05-01 00:00:00',	'2022-05-01 00:00:00'),
(3,	1,	1,	'John Doe',	'Pending question?',			0,	0,	'2022-05-01 00:00:00',	'2022-05-01 00:00:00'),
(4,	1,	1,	'John Doe',	'Spam question?',				2,	0,	'2022-05-01 00:00:00',	'2022-05-01 00:00:00'),
(5,	1,  1,	'John Doe',	'Trash question?',				3,	0,	'2022-05-01 00:00:00',	'2022-05-01 00:00:00');