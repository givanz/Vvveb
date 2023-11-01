DROP TABLE IF EXISTS digital_asset;

DROP SEQUENCE IF EXISTS digital_asset_seq;
CREATE SEQUENCE digital_asset_seq;


CREATE TABLE digital_asset (
  "digital_asset_id" int check ("digital_asset_id" > 0) NOT NULL DEFAULT NEXTVAL ('digital_asset_seq'),
  "file" varchar(160) NOT NULL,
  "public" varchar(128) NOT NULL,
  "created_at" timestamp(0) NOT NULL,
  PRIMARY KEY ("digital_asset_id")
);
