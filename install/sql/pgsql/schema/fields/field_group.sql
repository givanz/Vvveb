DROP TABLE IF EXISTS field_group;

DROP SEQUENCE IF EXISTS field_group_seq;
CREATE SEQUENCE field_group_seq;


CREATE TABLE field_group (
  "field_group_id" int NOT NULL DEFAULT NEXTVAL ('field_group_seq'),
  "name" text NOT NULL,
  "status" smallint NOT NULL,
  "sort_order" int NOT NULL,
  PRIMARY KEY ("field_group_id")
);