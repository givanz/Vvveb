DROP TABLE IF EXISTS product_content_meta;

CREATE TABLE product_content_meta (
  "product_id" int check ("product_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "namespace" varchar(32) NOT NULL DEFAULT '',
  "key" varchar(191) NOT NULL,
  "value" text DEFAULT NULL,
  PRIMARY KEY("product_id","language_id","namespace","key")
);
