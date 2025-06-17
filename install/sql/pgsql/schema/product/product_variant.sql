DROP TABLE IF EXISTS product_variant;

DROP SEQUENCE IF EXISTS product_variant_seq;
CREATE SEQUENCE product_variant_seq;

CREATE TABLE product_variant (
  "product_variant_id" int check ("product_variant_id" > 0) NOT NULL DEFAULT NEXTVAL ('product_variant_seq'),
  "product_id" int check ("product_id" > 0) NOT NULL,
  "options" varchar(191) NOT NULL DEFAULT '',
  "image" varchar(191) NOT NULL DEFAULT '',
  "price" decimal(15,4) NOT NULL DEFAULT 0.0000,
  "old_price" decimal(15,4) NOT NULL DEFAULT 0.0000,
  "stock_quantity" int NOT NULL DEFAULT 0,
  "weight" decimal(15,8) NOT NULL DEFAULT '0.00000000',
  "sku" varchar(64) NOT NULL DEFAULT '',
  "barcode" varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY ("product_variant_id")
);

CREATE INDEX "product_variant_product_id_product_option" ON "product_variant" ("product_id","product_variant_id", "options");
