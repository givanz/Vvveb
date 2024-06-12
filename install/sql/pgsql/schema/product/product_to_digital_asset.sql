DROP TABLE IF EXISTS product_to_digital_asset;

CREATE TABLE product_to_digital_asset (
  "product_id" int check ("product_id" > 0) NOT NULL,
  "digital_asset_id" int check ("digital_asset_id" > 0) NOT NULL,
  PRIMARY KEY ("product_id","digital_asset_id")
);
