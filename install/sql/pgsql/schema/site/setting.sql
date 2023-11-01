DROP TABLE IF EXISTS setting;

CREATE TABLE setting (
  "site_id" smallint check ("site_id" > 0) NOT NULL DEFAULT 0,
  "namespace" varchar(128) NOT NULL,
  "key" varchar(128) NOT NULL,
  "value" text NOT NULL,
  PRIMARY KEY ("site_id","key")
);
