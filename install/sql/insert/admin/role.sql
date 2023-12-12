INSERT INTO `role` (`role_id`, `name`, `display_name`, `permissions`) VALUES
(1, 'super_admin', 'Super Administrator', '{"allow":["*"], "deny":[], "capabilities":["view_other_posts","edit_other_posts","view_other_products","edit_other_products"]}'),
(2, 'admin', 'Administrator', '{"allow":["*"], "deny":["admin/*"], "capabilities":["view_other_posts","edit_other_posts","view_other_products","edit_other_products"]}'),
(3, 'editor', 'Editor', '{"allow":["index/index","content/*", "editor/*","media/media/scan"], "deny":[], "capabilities":["view_other_posts","edit_other_posts","view_other_products","edit_other_products"]}'),
(4, 'author', 'Author', '{"allow":["index/index","content/*", "editor/*","media/media/scan"], "deny":[]}'),
(5, 'contributor', 'Contributor', '{"allow":["index/index","content/*", "editor/*","media/media/scan"], "deny":[]}'),
(6, 'shop', 'Shop', '{"allow":["index/index","product/*","order/*","media/media/scan"], "deny":[], "capabilities":["view_other_products","edit_other_products"]}'),
(7, 'vendor', 'Vendor', '{"allow":["index/index","product/*","order/*","media/media/scan"], "deny":["product/vendors/*","product/vendor/*","product/manufacturers/*", "product/manufacturer/*"]}'),
(8, 'rest', 'Rest', '{"allow":["content/*"],"deny":["*/save","*/delete"], "capabilities":["view_other_products","edit_other_products"]}}'),
(9, 'demo', 'Demo', '{"allow":["*"],"deny":["*/save","*/delete","*/rename","*/upload", "*/activate","*/deactivate","*/update","*/deletemenu"], "capabilities":["view_other_posts","view_other_products"]}');
