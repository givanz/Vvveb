DROP TABLE IF EXISTS post_meta;

CREATE TABLE post_meta (
  "post_id" smallint check ("post_id" > 0) NOT NULL DEFAULT 0,
  "namespace" varchar(128) NOT NULL,
  "key" varchar(128) NOT NULL,
  "value" text NOT NULL,
  PRIMARY KEY ("post_id","namespace","key")
);
