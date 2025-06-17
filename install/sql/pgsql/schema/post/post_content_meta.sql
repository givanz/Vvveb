DROP TABLE IF EXISTS post_content_meta;

CREATE TABLE post_content_meta (
  "post_id" int check ("post_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "namespace" varchar(32) NOT NULL DEFAULT '',
  "key" varchar(191) NOT NULL,
  "value" text DEFAULT NULL,
  PRIMARY KEY ("post_id","language_id","namespace","key")
);
