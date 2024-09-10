DROP TABLE IF EXISTS media_content;


CREATE TABLE media_content (
  "media_id" int check ("media_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(191) NOT NULL DEFAULT '',
  "caption" varchar(191) NOT NULL DEFAULT '',
  "description" varchar(191) NOT NULL DEFAULT '',
--  "content" text DEFAULT NULL,
  PRIMARY KEY ("media_id","language_id")
);

--CREATE INDEX "media_content_slug" ON media_content ("slug");
CREATE INDEX "media_name" ON media_content USING gin (to_tsvector('english', name));
--CREATE INDEX "media_alt" ON media_content USING gin (to_tsvector('english', alt));
-- FULLTEXT KEY "search" ("name","content");
