INSERT INTO `role` (`role_id`, `name`, `display_name`, `permissions`) VALUES
(1, 'super_admin', 'Super Administrator', '{"allow":["*"], "deny":[]}'),
(2, 'admin', 'Administrator', '{"allow":["*"], "deny":["admin/*"]}'),
(3, 'editor', 'Editor', '{"allow":["index/index","content/*", "editor/*"], "deny":[]}'),
(4, 'author', 'Author', '{"allow":["index/index","content/*", "editor/*"], "deny":[]}'),
(5, 'contributor', 'Contributor', '{"allow":["index/index","content/*", "editor/*"], "deny":[]}'),
(6, 'shop', 'Shop', '{"allow":["index/index","product/*","order/*"], "deny":[]}'),
(7, 'rest', 'Rest', '{"allow":["content/*"],"deny":["*/save","*/delete"]}'),
(8, 'demo', 'Demo', '{"allow":["*"],"deny":["*/save","*/delete","*/rename","*/upload", "*/activate","*/deactivate","*/update","*/deletemenu"]}');
