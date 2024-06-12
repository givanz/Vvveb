DROP TABLE IF EXISTS field;

DROP SEQUENCE IF EXISTS field_seq;
CREATE SEQUENCE field_seq;
-- SELECT setval('field_seq', 0, true); -- last inserted id by sample data


CREATE TABLE field (
  "field_id" int NOT NULL DEFAULT NEXTVAL ('field_seq'),
  "field_group_id" int NOT NULL,
  "type" varchar(32) NOT NULL,
  "value" text NOT NULL,
  "status" smallint NOT NULL,
  "sort_order" int NOT NULL DEFAULT 0,
  PRIMARY KEY ("field_id")
);
