DROP TABLE IF EXISTS manufacturer;

DROP SEQUENCE IF EXISTS manufacturer_seq;
CREATE SEQUENCE manufacturer_seq;

CREATE TABLE manufacturer (
  "manufacturer_id" int check ("manufacturer_id" > 0) NOT NULL DEFAULT NEXTVAL ('manufacturer_seq'),
  "name" varchar(191) NOT NULL DEFAULT '',
  "slug" varchar(191) NOT NULL DEFAULT '',
  "image" varchar(191) NOT NULL,
  "sort_order" int NOT NULL DEFAULT 0,
  PRIMARY KEY ("manufacturer_id")
);