DROP TABLE IF EXISTS post_content_meta;

-- DROP SEQUENCE IF EXISTS post_content_meta_post_content_meta_id_seq;
-- CREATE SEQUENCE post_content_meta_post_content_meta_id_seq;
-- SELECT setval('post_content_meta_post_content_meta_id_seq', 0, true); -- last inserted id by sample data

CREATE TABLE post_content_meta (
  "post_id" int check ("post_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "namespace" varchar(32) NOT NULL DEFAULT '',
  "key" varchar(191) NOT NULL,
  "value" text DEFAULT NULL,
  PRIMARY KEY ("post_id","language_id","namespace","key")
);

-- CREATE UNIQUE INDEX "post_content_meta_post_id" ON post_content_meta ("post_id","language_id","namespace","key");
