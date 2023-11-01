DROP TABLE IF EXISTS field_group_content;

CREATE TABLE field_group_content (
  "field_group_id" int NOT NULL,
  "language_id" int NOT NULL,
  "name" varchar(128) NOT NULL,
  PRIMARY KEY ("field_group_id","language_id")
);
