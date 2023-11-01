DROP TABLE IF EXISTS product_option_value;

DROP SEQUENCE IF EXISTS product_option_value_seq;
CREATE SEQUENCE product_option_value_seq;


CREATE TABLE product_option_value (
  "product_option_value_id" int check ("product_option_value_id" > 0) NOT NULL DEFAULT NEXTVAL ('product_option_value_seq'),
  "product_option_id" int check ("product_option_id" > 0) NOT NULL,
  "product_id" int check ("product_id" > 0) NOT NULL,
  "option_id" int check ("option_id" > 0) NOT NULL,
  "option_value_id" int check ("option_value_id" > 0) NOT NULL,
  "quantity" int NOT NULL DEFAULT 0,
  "subtract" smallint NOT NULL DEFAULT 0,
  "price_operator" varchar(1) NOT NULL DEFAULT '+',
  "price" decimal(15,4) NOT NULL DEFAULT 0,
  "points_operator" varchar(1) NOT NULL DEFAULT '+',
  "points" int NOT NULL DEFAULT 0,
  "weight_operator" varchar(1) NOT NULL DEFAULT '+',
  "weight" decimal(15,8) NOT NULL DEFAULT 0,
  PRIMARY KEY ("product_option_value_id")
);
