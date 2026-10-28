-- LOCK TABLES `post` WRITE;

INSERT INTO `post` (`admin_id`, `status`, `image`, `comment_status`, `password`, `parent`, `sort_order`, `type`, `template`, `comment_count`, `created_at`, `updated_at`)  VALUES 

(1,'publish','demo/posts/1.jpg','open','',0,0,'post','',0,'2026-05-01 00:00:00','2026-05-01 00:00:00'),
(1,'publish','demo/posts/2.jpg','open','',0,0,'post','content/post-image-header.html',0,'2026-05-02 00:00:00','2026-05-02 00:00:00'),
(1,'publish','demo/posts/1.jpg','open','',0,0,'page','contact.html',0,'2026-05-01 00:00:00','2026-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','',0,'2026-05-01 00:00:00','2026-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','',0,'2026-05-01 00:00:00','2026-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','',0,'2026-05-01 00:00:00','2026-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','about.html',0,'2026-05-01 00:00:00','2026-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','services.html',0,'2026-05-01 00:00:00','2026-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','pricing.html',0,'2026-05-01 00:00:00','2026-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','portfolio.html',0,'2026-05-01 00:00:00','2026-05-01 00:00:00'),
(1,'publish','','open','',0,0,'page','',0,'2026-05-01 00:00:00','2026-05-01 00:00:00');

-- UNLOCK TABLES
