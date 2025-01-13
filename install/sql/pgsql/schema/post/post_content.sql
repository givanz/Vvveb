DROP TABLE IF EXISTS post_content;


CREATE TABLE post_content (
  "post_id" int check ("post_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(191) NOT NULL,
  "slug" varchar(191) NOT NULL,
  "content" text NOT NULL,
  "excerpt" text DEFAULT '',
  "meta_keywords" text NOT NULL DEFAULT '',
  "meta_description" text NOT NULL DEFAULT '',
  PRIMARY KEY ("post_id","language_id")
);

CREATE INDEX "post_content_slug" ON post_content ("slug");
CREATE INDEX "post_content_name" ON post_content USING gin (to_tsvector('english', name));
CREATE INDEX "post_content_content" ON post_content USING gin (to_tsvector('english', content));
-- FULLTEXT KEY "search" ("name","content");
