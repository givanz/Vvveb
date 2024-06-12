DROP TABLE IF EXISTS digital_asset_content;

CREATE TABLE digital_asset_content (
  "digital_asset_id" int check ("digital_asset_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(64) NOT NULL,
  PRIMARY KEY ("digital_asset_id","language_id")
);
