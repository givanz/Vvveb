DROP TABLE IF EXISTS post_field_value;

-- DROP SEQUENCE IF EXISTS post_field_value_field_value_id_seq;
-- CREATE SEQUENCE post_field_value_field_value_id_seq;


CREATE TABLE post_field_value (
  "post_id" int NOT NULL,
  "field_id" int NOT NULL,
  "language_id" INT NOT NULL,
  "value" TEXT NOT NULL,
  PRIMARY KEY("post_id","field_id","language_id")
);

-- SELECT setval('post_field_value_field_value_id_seq', 0, true); -- last inserted id by sample data

CREATE INDEX IF NOT EXISTS "post_field_value_field" ON post_field_value ("field_id");
