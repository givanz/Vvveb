INSERT INTO `admin` (`admin_id`, `username`, `first_name`, `last_name`,`password`, `email`, `url`, `created_at`, `token`, `status`, `site_access`, `display_name`, `avatar`, `cover`, `bio`, `role_id`) VALUES

(1, 'admin', 'Admin', 'Admin', '$', 'admin@admin.com', '', '2022-05-01 00:00:00', '', 0, '[]', 'Admin', 'vvveb.svg', 'posts/2.jpg', 'Has access everywhere', 1),
(2, 'administrator',  'Administrator', 'Administrator', '$', 'demo@admin.com', '', '2022-05-01 00:00:00', '', 0, '[]', 'Administrator', 'vvveb.svg', 'posts/2.jpg', 'Has access everywhere except admin user and role management', 2),
(3, 'site-admin',  'Site Administrator', 'Site Administrator', '$', 'demo@admin.com', '', '2022-05-01 00:00:00', '', 0, '[]', 'Site Admin', 'vvveb.svg', 'posts/2.jpg', 'Manages content, products and orders', 3),
(4, 'editor', 'Editor', 'Editor','$', 'demo@admin.com', '', '2022-05-01 00:00:00', '', 0, '[]', 'Editor', 'vvveb.svg', 'posts/2.jpg', 'Manages content', 4),
(5, 'author', 'Author', 'Author', '$', 'demo@admin.com', '', '2022-05-01 00:00:00', '', 0, '[]', 'Author', 'vvveb.svg', 'posts/2.jpg', 'Can add and update content', 5),
(6, 'contributor', 'Contributor', 'Contributor', '$', 'demo@admin.com', '', '2022-05-01 00:00:00', '', 0, '[]', 'Contributor', 'vvveb.svg', 'posts/2.jpg', 'Can add and update content', 5),
(7, 'shop', 'Shop', 'Shop', '$', 'demo@admin.com', '', '2022-05-01 00:00:00', '', 0, '[]', 'Shop assistant', 'vvveb.svg', 'posts/2.jpg', 'Manages products and orders', 7),
(8, 'vendor', 'Vendor', 'Vendor', '$', 'demo@admin.com', '', '2022-05-01 00:00:00', '', 0, '[]', 'Vendor', 'vvveb.svg', 'posts/2.jpg', 'Can add own products and process orders', 8),
(9, 'rest', 'Rest', 'Rest', '$', 'demo@admin.com', '', '2022-05-01 00:00:00', '', 0, '[]', 'Rest Api', 'vvveb.svg', 'posts/2.jpg', 'Rest API access user', 9),
(10, 'demo', 'Demo', 'Demo', '', 'demo@admin.com', '', '2022-05-01 00:00:00', '', 0, '[]', 'Demo', 'vvveb.svg', 'posts/2.jpg', 'Read only account with access everywhere', 10);
