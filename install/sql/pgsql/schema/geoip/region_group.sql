DROP TABLE IF EXISTS region_group;

DROP SEQUENCE IF EXISTS region_group_seq;
CREATE SEQUENCE region_group_seq;
SELECT setval('region_group_seq', 2, true); -- last inserted id by sample data


CREATE TABLE region_group (
  "region_group_id" int check ("region_group_id" > 0) NOT NULL DEFAULT NEXTVAL ('region_group_seq'),
  "name" varchar(32) NOT NULL,
  "content" varchar(191) NOT NULL,
  "created_at" timestamp(0) NOT NULL DEFAULT now(),
  PRIMARY KEY ("region_group_id")
);
