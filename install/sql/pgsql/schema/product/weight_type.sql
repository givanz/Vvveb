DROP TABLE IF EXISTS weight_type;

DROP SEQUENCE IF EXISTS weight_type_seq;
CREATE SEQUENCE weight_type_seq;

CREATE TABLE weight_type (
  "weight_type_id" int check ("weight_type_id" > 0) NOT NULL DEFAULT NEXTVAL ('weight_type_seq'),
  "value" decimal(15,8) NOT NULL DEFAULT 0.00000000,
  PRIMARY KEY ("weight_type_id")
);
