DROP TABLE IF EXISTS product_variant;
CREATE TABLE product_variant (
  "product_id" int check ("product_id" > 0) NOT NULL,
  "product_variant_id" int check ("product_variant_id" > 0) NOT NULL,
  PRIMARY KEY ("product_id","product_variant_id")
);
