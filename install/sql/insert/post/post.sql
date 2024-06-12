-- LOCK TABLES `post` WRITE;

INSERT INTO `post` (`admin_id`, `status`, `image`, `comment_status`, `password`, `parent`, `sort_order`, `type`, `template`, `comment_count`, `created_at`, `updated_at`)  VALUES 

(1,'publish','posts/1.jpg','open','',0,0,'post','',0,'2022-05-01 00:00:00','2022-05-01 00:00:00'),
(1,'publish','posts/2.jpg','open','',0,0,'post','content/post-image-header.html',0,'2022-05-02 00:00:00','2022-05-02 00:00:00'),
(1,'publish','posts/3.jpg','open','',0,0,'post','',0,'2022-05-03 00:00:00','2022-05-03 00:00:00'),
(1,'publish','posts/4.jpg','open','',0,0,'post','',0,'2022-06-01 00:00:00','2022-06-01 00:00:00'),
(1,'publish','posts/5.jpg','open','',0,0,'post','',0,'2022-06-02 00:00:00','2022-06-02 00:00:00'),
(1,'publish','posts/6.jpg','open','',0,0,'post','',0,'2022-06-03 00:00:00','2022-06-03 00:00:00'),
(1,'publish','posts/7.jpg','open','',0,0,'page','contact.html',0,'2022-05-01 00:00:00','2022-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','',0,'2022-05-01 00:00:00','2022-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','',0,'2022-05-01 00:00:00','2022-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','',0,'2022-05-01 00:00:00','2022-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','about.html',0,'2022-05-01 00:00:00','2022-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','services.html',0,'2022-05-01 00:00:00','2022-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','pricing.html',0,'2022-05-01 00:00:00','2022-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','portfolio.html',0,'2022-05-01 00:00:00','2022-05-01 00:00:00');

-- UNLOCK TABLES