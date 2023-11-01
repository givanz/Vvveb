DROP TABLE IF EXISTS region;

DROP SEQUENCE IF EXISTS region_seq;
CREATE SEQUENCE region_seq;


CREATE TABLE region (
  "region_id" int check ("region_id" > 0) NOT NULL DEFAULT NEXTVAL ('region_seq'),
  "country_id" int check ("country_id" > 0) NOT NULL,
  "name" varchar(128) NOT NULL,
  "code" varchar(32) NOT NULL,
  "status" smallint NOT NULL DEFAULT 1,
  PRIMARY KEY ("region_id")
);