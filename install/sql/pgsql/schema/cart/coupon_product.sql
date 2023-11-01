DROP TABLE IF EXISTS coupon_product;

DROP SEQUENCE IF EXISTS coupon_product_seq;
CREATE SEQUENCE coupon_product_seq;


CREATE TABLE coupon_product (
  "coupon_product_id" int check ("coupon_product_id" > 0) NOT NULL DEFAULT NEXTVAL ('coupon_product_seq'),
  "coupon_id" int check ("coupon_id" > 0) NOT NULL,
  "product_id" int check ("product_id" > 0) NOT NULL,
  PRIMARY KEY ("coupon_product_id")
);