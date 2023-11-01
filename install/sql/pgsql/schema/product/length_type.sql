DROP TABLE IF EXISTS length_type;

DROP SEQUENCE IF EXISTS length_type_seq;
CREATE SEQUENCE length_type_seq;


CREATE TABLE length_type (
  "length_type_id" int check ("length_type_id" > 0) NOT NULL DEFAULT NEXTVAL ('length_type_seq'),
  "value" decimal(15,8) NOT NULL,
  PRIMARY KEY ("length_type_id")
);