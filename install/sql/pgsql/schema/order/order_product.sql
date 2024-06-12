DROP TABLE IF EXISTS order_product;

DROP SEQUENCE IF EXISTS order_product_seq;
CREATE SEQUENCE order_product_seq;
-- SELECT setval('order_product_seq', 0, true); -- last inserted id by sample data


CREATE TABLE order_product (
  "order_product_id" int check ("order_product_id" > 0) NOT NULL DEFAULT NEXTVAL ('order_product_seq'),
  "order_id" int check ("order_id" > 0) NOT NULL,
  "product_id" int check ("product_id" > 0) NOT NULL,
  "name" varchar(191) NOT NULL,
  "model" varchar(64) NOT NULL,
  "quantity" int NOT NULL,
  "price" decimal(15,4) NOT NULL DEFAULT 0.0000,
  "total" decimal(15,4) NOT NULL DEFAULT 0.0000,
  "tax" decimal(15,4) NOT NULL DEFAULT 0.0000,
  "points" int NOT NULL DEFAULT 0,
  PRIMARY KEY ("order_product_id")
);

CREATE INDEX "order_product_order_id" ON order_product ("order_id");
