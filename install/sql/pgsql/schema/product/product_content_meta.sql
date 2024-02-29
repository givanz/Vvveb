DROP TABLE IF EXISTS product_content_meta;

CREATE TABLE product_content_meta (
  "product_id" smallint check ("product_id" > 0) NOT NULL DEFAULT 0,
  "language_id" smallint check ("language_id" > 0) NOT NULL DEFAULT 0,
  "namespace" varchar(128) NOT NULL,
  "key" varchar(128) NOT NULL,
  "value" text NOT NULL,
  PRIMARY KEY ("product_id","language_id","namespace","key")
);
