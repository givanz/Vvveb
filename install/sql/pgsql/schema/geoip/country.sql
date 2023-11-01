DROP TABLE IF EXISTS country;

DROP SEQUENCE IF EXISTS country_seq;
CREATE SEQUENCE country_seq;


CREATE TABLE country (
  "country_id" int check ("country_id" > 0) NOT NULL DEFAULT NEXTVAL ('country_seq'),
  "name" varchar(128) NOT NULL,
  "iso_code_2" varchar(2) NOT NULL,
  "iso_code_3" varchar(3) NOT NULL,
  "status" smallint NOT NULL DEFAULT 1,
  PRIMARY KEY ("country_id")
);
