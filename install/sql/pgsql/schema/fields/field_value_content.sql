DROP TABLE IF EXISTS field_value_content;

CREATE TABLE field_value_content (
  "field_value_id" int NOT NULL,
  "language_id" int NOT NULL,
  "field_id" int NOT NULL,
  "name" varchar(128) NOT NULL,
  PRIMARY KEY ("field_value_id","language_id")
);
