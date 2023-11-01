DROP TABLE IF EXISTS product_discount;

DROP SEQUENCE IF EXISTS product_discount_seq;
CREATE SEQUENCE product_discount_seq;


CREATE TABLE product_discount (
  "product_discount_id" int check ("product_discount_id" > 0) NOT NULL DEFAULT NEXTVAL ('product_discount_seq'),
  "product_id" int check ("product_id" > 0) NOT NULL,
  "user_group_id" int check ("user_group_id" > 0) NOT NULL,
  "quantity" int NOT NULL DEFAULT 0,
  "priority" int NOT NULL DEFAULT 1,
  "price" decimal(15,4) NOT NULL DEFAULT 0.0000,
  "from_date" date NOT NULL DEFAULT '1000-01-01',
  "to_date" date NOT NULL DEFAULT '1000-01-01',
  PRIMARY KEY ("product_discount_id")
);

CREATE INDEX "product_discount_product_id" ON product_discount ("product_id");