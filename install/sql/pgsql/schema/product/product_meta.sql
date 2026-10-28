DROP TABLE IF EXISTS product_meta;

CREATE TABLE product_meta (
  "product_id" int check ("product_id" > 0) NOT NULL,
  "namespace" varchar(32) NOT NULL DEFAULT '',
  "key" varchar(191) NOT NULL DEFAULT '',
  "value" text DEFAULT NULL,
  PRIMARY KEY ("product_id","namespace","key")
);
