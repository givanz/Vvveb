DROP TABLE IF EXISTS order_meta;

-- DROP SEQUENCE IF EXISTS order_meta_order_meta_id_seq;
-- CREATE SEQUENCE order_meta_order_meta_id_seq;

CREATE TABLE order_meta (
  "order_id" int check ("order_id" > 0) NOT NULL,
  "namespace" varchar(32) NOT NULL DEFAULT '',
  "key" varchar(191) NOT NULL,
  "value" text DEFAULT NULL
);

CREATE UNIQUE INDEX "order_meta_order_id" ON order_meta ("order_id","namespace","key");
-- SELECT setval('order_meta_order_meta_id_seq', 0, true); -- last inserted id by sample data
