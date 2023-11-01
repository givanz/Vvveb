DROP TABLE IF EXISTS product_attribute;
CREATE TABLE product_attribute (
  "product_id" int check ("product_id" > 0) NOT NULL,
  "attribute_id" int check ("attribute_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "value" text NOT NULL,
  PRIMARY KEY ("product_id","attribute_id","language_id")
);