DROP TABLE IF EXISTS field_content;

CREATE TABLE field_content (
  "field_id" int NOT NULL,
  "language_id" int NOT NULL,
  "name" varchar(128) NOT NULL,
  PRIMARY KEY ("field_id","language_id")
);
