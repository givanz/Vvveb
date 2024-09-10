INSERT INTO `role` (`role_id`, `name`, `display_name`, `permissions`) VALUES
(1, 'super_admin', 'Super Administrator', '{"allow":["*"], "deny":[], "capabilities":["view_other_posts","edit_other_posts","view_other_products","edit_other_products","view_other_sites","edit_other_sites"]}'),
(2, 'admin', 'Administrator', '{"allow":["*"], "deny":["admin/*"], "capabilities":["view_other_posts","edit_other_posts","view_other_products","edit_other_products","view_other_sites","edit_other_sites"]}'),
(3, 'site_admin', 'Site Administrator', '{"allow":["index/index","content/*","user/*","product/*","order/*","editor/*","media/*","settings/*","tools/*"], "deny":["settings/email/*", "tools/update/*", "tools/cron/*","settings/site/add"], "capabilities":["view_other_posts","edit_other_posts","view_other_products","edit_other_products","view_other_sites","edit_other_sites"]}'),
(4, 'editor', 'Editor', '{"allow":["index/index","content/*", "editor/*","media/media/scan"], "deny":[], "capabilities":["view_other_posts","edit_other_posts","view_other_products","edit_other_products","view_other_sites","edit_other_sites"]}'),
(5, 'author', 'Author', '{"allow":["index/index","content/*", "editor/*","media/media/scan"], "deny":[]}'),
(6, 'contributor', 'Contributor', '{"allow":["index/index","content/*","editor/*","media/media/scan"], "deny":[]}'),
(7, 'shop', 'Shop', '{"allow":["index/index","product/*","order/*","media/media/scan"], "deny":[], "capabilities":["view_other_products","edit_other_products"]}'),
(8, 'vendor', 'Vendor', '{"allow":["index/index","product/*","order/*","media/media/scan"], "deny":["product/vendors/*","product/vendor/*","product/manufacturers/*", "product/manufacturer/*"]}'),
(9, 'rest', 'Rest', '{"allow":["content/*"],"deny":["*/save","*/delete"], "capabilities":["view_other_products","edit_other_products"]}}'),
(10, 'demo', 'Demo', '{"allow":["*"],"deny":["*/save","*/delete","*/rename","*/duplicate","*/upload", "*/activate","*/deactivate","*/update","*/deletemenu"], "capabilities":["view_other_posts","view_other_products","view_other_sites"]}');
