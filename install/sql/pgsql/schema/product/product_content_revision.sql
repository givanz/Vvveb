-- DROP SEQUENCE IF EXISTS product_content_revision_seq;
-- CREATE SEQUENCE IF NOT EXISTS product_content_revision_seq;

DROP TABLE IF EXISTS product_content_revision;

-- CREATE TABLE IF NOT EXISTS product_content_revision (
CREATE TABLE product_content_revision (
--  "product_content_revision" int check ("product_content_revision" > 0) NOT NULL DEFAULT NEXTVAL ('product_content_revision_seq'),
  "product_id" int check ("product_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "content" text DEFAULT NULL,
  "admin_id" int check ("admin_id" > 0) NOT NULL,
  "created_at" timestamp(0) NOT NULL DEFAULT '2022-05-01 00:00:00',
  PRIMARY KEY ("product_id","language_id","created_at")
);


DROP INDEX IF EXISTS "product_content_revision_product_language_created";

CREATE INDEX "product_content_revision_product_language_created" ON product_content_revision ("product_id","language_id","created_at", "admin_id" );
