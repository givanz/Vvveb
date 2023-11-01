DROP TABLE IF EXISTS post_content_meta;

CREATE TABLE post_content_meta (
  "post_id" smallint check ("post_id" > 0) NOT NULL DEFAULT 0,
  "language_id" smallint check ("language_id" > 0) NOT NULL DEFAULT 0,
  "key" varchar(128) NOT NULL,
  "value" text NOT NULL,
  PRIMARY KEY ("post_id","language_id","key")
);
