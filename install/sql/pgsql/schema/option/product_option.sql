DROP TABLE IF EXISTS product_option;

DROP SEQUENCE IF EXISTS product_option_seq;
CREATE SEQUENCE product_option_seq;


CREATE TABLE product_option (
  "product_option_id" int check ("product_option_id" > 0) NOT NULL DEFAULT NEXTVAL ('product_option_seq'),
  "product_id" int check ("product_id" > 0) NOT NULL,
  "option_id" int check ("option_id" > 0) NOT NULL,
  "value" text NOT NULL,
  "required" smallint NOT NULL DEFAULT 0,
  PRIMARY KEY ("product_option_id")
);
