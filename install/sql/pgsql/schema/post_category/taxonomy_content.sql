DROP TABLE IF EXISTS taxonomy_content;
CREATE TABLE taxonomy_content (
  "taxonomy_id" int check ("taxonomy_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(191) NOT NULL,
  "slug" varchar(191) NOT NULL DEFAULT '',
  "content" text NOT NULL,
  PRIMARY KEY ("taxonomy_id","language_id")
);

CREATE INDEX "taxonomy_content_name" ON taxonomy_content ("name");