DROP TABLE IF EXISTS setting_content;

CREATE TABLE setting_content (
  "site_id" smallint check ("site_id" > 0) NOT NULL DEFAULT 0,
  "language_id" smallint check ("language_id" > 0) NOT NULL DEFAULT 0,
  "key" varchar(128) NOT NULL,
  "value" text NOT NULL,
  PRIMARY KEY ("site_id","language_id","key")
);
