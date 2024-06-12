DROP TABLE IF EXISTS weight_type_content;

CREATE TABLE weight_type_content (
  "weight_type_id" int check ("weight_type_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(32) NOT NULL,
  "unit" varchar(4) NOT NULL,
  PRIMARY KEY ("weight_type_id","language_id")
);
