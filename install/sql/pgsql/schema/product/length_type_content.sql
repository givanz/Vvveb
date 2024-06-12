DROP TABLE IF EXISTS length_type_content;
CREATE TABLE length_type_content (
  "length_type_id" int check ("length_type_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(32) NOT NULL,
  "unit" varchar(4) NOT NULL,
  PRIMARY KEY ("length_type_id","language_id")
);