DROP TABLE IF EXISTS product_promotion;

DROP SEQUENCE IF EXISTS product_promotion_seq;
CREATE SEQUENCE product_promotion_seq;


CREATE TABLE product_promotion (
  "product_promotion_id" int check ("product_promotion_id" > 0) NOT NULL DEFAULT NEXTVAL ('product_promotion_seq'),
  "product_id" int check ("product_id" > 0) NOT NULL,
  "user_group_id" int check ("user_group_id" > 0) NOT NULL,
  "priority" int NOT NULL DEFAULT 1,
  "price" decimal(15,4) NOT NULL DEFAULT 0.0000,
  "from_date" date NOT NULL DEFAULT '1000-01-01',
  "to_date" date NOT NULL DEFAULT '1000-01-01',
  PRIMARY KEY ("product_promotion_id")
);

CREATE INDEX "product_promotion_product_id" ON product_promotion ("product_id");