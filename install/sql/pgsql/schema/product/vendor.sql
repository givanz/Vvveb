DROP TABLE IF EXISTS vendor;

DROP SEQUENCE IF EXISTS vendor_seq;
CREATE SEQUENCE vendor_seq;


CREATE TABLE vendor (
  "vendor_id" int check ("vendor_id" > 0) NOT NULL DEFAULT NEXTVAL ('vendor_seq'),
  "name" varchar(191) NOT NULL DEFAULT '',
  "slug" varchar(191) NOT NULL DEFAULT '',
  "image" varchar(191) NOT NULL,
  "sort_order" int NOT NULL DEFAULT 0,
  PRIMARY KEY ("vendor_id")
);