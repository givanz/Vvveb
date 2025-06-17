DROP TABLE IF EXISTS product;

DROP SEQUENCE IF EXISTS product_seq;
CREATE SEQUENCE product_seq;
-- SELECT setval('product_seq', 19, true); -- last inserted id by sample data


CREATE TABLE product (
  "product_id" int check ("product_id" > 0) NOT NULL DEFAULT NEXTVAL ('product_seq'),
  "admin_id" int check ("admin_id" >= 0) NOT NULL DEFAULT 0,
  "model" varchar(64) NOT NULL DEFAULT '',
  "sku" varchar(64) NOT NULL DEFAULT '',
  "upc" varchar(12) NOT NULL DEFAULT '',
  "ean" varchar(14) NOT NULL DEFAULT '',
  "jan" varchar(13) NOT NULL DEFAULT '',
  "isbn" varchar(17) NOT NULL DEFAULT '',
  "mpn" varchar(64) NOT NULL DEFAULT '',
  "barcode" varchar(64) NOT NULL DEFAULT '',
  "hs_code" varchar(10) NOT NULL DEFAULT '',
  "origin_country" varchar(64) NOT NULL DEFAULT '',
  "mid_code" varchar(13) NOT NULL DEFAULT '',
  "location" varchar(128) NOT NULL DEFAULT '',
  "stock_quantity" int NOT NULL DEFAULT 1,
  "stock_status_id" int check ("stock_status_id" >= 0) NOT NULL DEFAULT 0,
  "image" varchar(191) NOT NULL DEFAULT '',
  "manufacturer_id" int check ("manufacturer_id" >= 0) NOT NULL DEFAULT 0,
  "vendor_id" int check ("vendor_id" >= 0) NOT NULL DEFAULT 0,
  "requires_shipping" smallint NOT NULL DEFAULT 1,
  "price" decimal(15,4) NOT NULL DEFAULT 0.0000,
  "old_price" decimal(15,4) NOT NULL DEFAULT 0.0000,
  "points" int NOT NULL DEFAULT 0,
  "tax_type_id" int check ("tax_type_id" >= 0) NOT NULL DEFAULT 0,
  "material" varchar(64) NOT NULL DEFAULT '',
  "weight" decimal(15,8) NOT NULL DEFAULT 0.00000000,
  "weight_type_id" int check ("weight_type_id" >= 0) NOT NULL DEFAULT 0,
  "length" decimal(15,8) NOT NULL DEFAULT 0.00000000,
  "width" decimal(15,8) NOT NULL DEFAULT 0.00000000,
  "height" decimal(15,8) NOT NULL DEFAULT 0.00000000,
  "length_type_id" int check ("length_type_id" >= 0) NOT NULL DEFAULT 0,
  "date_available" date NOT NULL DEFAULT '1000-01-01',
  "template" varchar(191) NOT NULL DEFAULT '',
  "views" int NOT NULL DEFAULT 0,
  "subtract_stock" smallint NOT NULL DEFAULT 1,
  "minimum_quantity" int check ("minimum_quantity" >= 0) NOT NULL DEFAULT 1,
  "subscription_onetime"  smallint NOT NULL DEFAULT 1,
  "type" varchar(20) NOT NULL DEFAULT 'product',
  "status" smallint NOT NULL DEFAULT 0,
  "sort_order" int check ("sort_order" >= 0) NOT NULL DEFAULT 0,
  "created_at" timestamp(0) NOT NULL DEFAULT now(),
  "updated_at" timestamp(0) NOT NULL DEFAULT now(),
  PRIMARY KEY ("product_id")
);

CREATE INDEX "product_type_status_date" ON product ("type","status","created_at","product_id");

-- SELECT setval('my_table_seq', (SELECT max(id) FROM my_table));
