DROP TABLE IF EXISTS region_to_region_group;

DROP SEQUENCE IF EXISTS region_to_region_group_seq;
CREATE SEQUENCE region_to_region_group_seq;

CREATE TABLE region_to_region_group (
  "region_to_region_group_id" int check ("region_to_region_group_id" > 0) NOT NULL DEFAULT NEXTVAL ('region_to_region_group_seq'),
  "country_id" int check ("country_id" > 0) NOT NULL,
  "region_id" int check ("region_id" > 0) NOT NULL DEFAULT 0,
  "region_group_id" int check ("region_group_id" > 0) NOT NULL,
  "created_at" timestamp(0) NOT NULL,
  PRIMARY KEY ("region_to_region_group_id")
);