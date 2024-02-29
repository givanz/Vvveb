DROP TABLE IF EXISTS product_meta;

CREATE TABLE product_meta (
  "product_id" smallint check ("product_id" > 0) NOT NULL DEFAULT 0,
  "namespace" varchar(128) NOT NULL,
  "key" varchar(128) NOT NULL,
  "value" text NOT NULL,
  PRIMARY KEY ("product_id","namespace","key")
);
