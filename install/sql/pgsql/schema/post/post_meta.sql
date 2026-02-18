DROP TABLE IF EXISTS post_meta;

-- DROP SEQUENCE IF EXISTS post_meta_post_meta_id_seq;
-- CREATE SEQUENCE post_meta_post_meta_id_seq;

CREATE TABLE post_meta (
  "post_id" int check ("post_id" > 0) NOT NULL,
  "namespace" varchar(32) NOT NULL DEFAULT '',
  "key" varchar(191) NOT NULL,
  "value" text DEFAULT NULL,
  PRIMARY KEY ("post_id","namespace","key")
);

-- CREATE UNIQUE INDEX "post_meta_post_id" ON post_meta ("post_id","namespace","key");
-- SELECT setval('post_meta_post_meta_id_seq', 0, true); -- last inserted id by sample data
