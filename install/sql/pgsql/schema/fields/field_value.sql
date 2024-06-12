DROP TABLE IF EXISTS field_value;

DROP SEQUENCE IF EXISTS field_value_seq;
CREATE SEQUENCE field_value_seq;
-- SELECT setval('field_value_seq', 0, true); -- last inserted id by sample data


CREATE TABLE field_value (
  "field_value_id" int NOT NULL DEFAULT NEXTVAL ('field_value_seq'),
  "field_id" int NOT NULL,
  "sort_order" int NOT NULL DEFAULT 0,
  PRIMARY KEY ("field_value_id")
);
