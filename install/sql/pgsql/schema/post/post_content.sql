DROP TABLE IF EXISTS post_content;

DROP SEQUENCE IF EXISTS post_content_seq;
CREATE SEQUENCE post_content_seq;


CREATE TABLE post_content (
  "post_id" int check ("post_id" > 0) NOT NULL DEFAULT NEXTVAL ('post_content_seq'),
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(191) NOT NULL DEFAULT '',
  "slug" varchar(191) NOT NULL DEFAULT '',
  "content" text DEFAULT NULL,
  "excerpt" text DEFAULT NULL,
  "meta_keywords" text DEFAULT NULL,
  "meta_description" text DEFAULT NULL,
  PRIMARY KEY ("post_id","language_id")
);

CREATE INDEX "post_content_slug" ON post_content ("slug");
CREATE INDEX "post_content_name" ON post_content USING gin (to_tsvector('english', name));
CREATE INDEX "post_content_content" ON post_content USING gin (to_tsvector('english', content));
-- FULLTEXT KEY "search" ("name","content");
