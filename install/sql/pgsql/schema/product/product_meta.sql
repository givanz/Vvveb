DROP TABLE IF EXISTS product_meta;

-- DROP SEQUENCE IF EXISTS product_meta_product_meta_id_seq;
-- CREATE SEQUENCE product_meta_product_meta_id_seq;

CREATE TABLE product_meta (
  "product_id" int check ("product_id" > 0) NOT NULL,
  "namespace" varchar(32) NOT NULL DEFAULT '',
  "key" varchar(191) NOT NULL,
  "value" text DEFAULT NULL
);

CREATE UNIQUE INDEX "product_meta_product_id" ON product_meta ("product_id","namespace","key");
-- SELECT setval('product_meta_product_meta_id_seq', 0, true); -- last inserted id by sample data
