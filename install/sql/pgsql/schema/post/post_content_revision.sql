--DROP SEQUENCE IF EXISTS post_content_revision_seq;
--CREATE SEQUENCE IF NOT EXISTS post_content_revision_seq;


CREATE TABLE IF NOT EXISTS post_content_revision (
--  "post_content_revision" int check ("post_content_revision" > 0) NOT NULL DEFAULT NEXTVAL ('post_content_revision_seq'),
  "post_id" int check ("post_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "content" text DEFAULT NULL,
  "admin_id" int check ("admin_id" > 0) NOT NULL,
  "created_at" timestamp(0) NOT NULL DEFAULT clock_timestamp(),
  PRIMARY KEY ("post_id","language_id","created_at")
);


-- DROP INDEX IF EXISTS "post_content_revision_post_language_created";

-- CREATE INDEX "post_content_revision_post_language_created" ON message ("post_id","language_id","created_at");
