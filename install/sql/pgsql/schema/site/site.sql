DROP TABLE IF EXISTS site;

DROP SEQUENCE IF EXISTS site_seq;
CREATE SEQUENCE site_seq;

CREATE TABLE site (
  "site_id" smallint NOT NULL DEFAULT NEXTVAL ('site_seq'),
  "key" varchar(191) NOT NULL,
  "name" varchar(191) NOT NULL,
  "host" varchar(191) NOT NULL,
  "theme" varchar(191) NOT NULL,
  "template" varchar(191) NOT NULL DEFAULT '',
  "settings" text DEFAULT NULL,
  PRIMARY KEY ("site_id")
);