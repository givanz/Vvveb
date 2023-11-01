INSERT INTO `comment` (`comment_id`, `post_id`, `user_id`, `author`, `email`, `url`, `ip`, `content`, `status`, `type`, `votes`, `parent_id`, `created_at`,`updated_at`) VALUES

(1, 1, 1, 'John Doe', 'john.doe@mail.com', 'https://vvveb.com', '', 'This is an approved comment.\nThis comment is visible on the hello world post page.', 1, '' , 0, 0, '2022-05-01 00:00:00', '2022-05-01 00:00:00'),
(2, 1, 1, 'John Doe', 'john.doe@mail.com', 'https://vvveb.com', '', 'This is a pending comment.\nThis comment is not visible until approved.', 0, '' , 0,  0, '2022-05-01 00:00:00', '2022-05-01 00:00:00'),
(3, 1, 1, 'John Doe', 'john.doe@mail.com', 'https://vvveb.com', '', 'This is a spam comment.\nThis comment is flagged as spam because it raised some flags and needs attention before approving.', 2, '' , 0,  0, '2022-05-01 00:00:00', '2022-05-01 00:00:00'),
(4, 1, 1, 'John Doe', 'john.doe@mail.com', 'https://vvveb.com', '', 'This is a trash comment.',3, '', 0, 0, '2022-05-01 00:00:00', '2022-05-01 00:00:00');
