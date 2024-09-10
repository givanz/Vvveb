DROP TABLE IF EXISTS digital_asset_log;

DROP SEQUENCE IF EXISTS digital_asset_log_seq;
CREATE SEQUENCE digital_asset_log_seq;
-- SELECT setval('digital_asset_log_seq', 0, true); -- last inserted id by sample data

CREATE TABLE digital_asset_log (
  "digital_asset_log_id" int check ("digital_asset_log_id" > 0) NOT NULL DEFAULT NEXTVAL ('digital_asset_log_seq'),
  "digital_asset_id" int check ("digital_asset_id" > 0) NOT NULL,
  "user_id" int check ("user_id" > 0) NOT NULL,
  "site_id" smallint NOT NULL,
  "ip" varchar(40) NOT NULL,
  "country" varchar(2),
  "created_at" timestamp(0) NOT NULL DEFAULT now(),
  PRIMARY KEY ("digital_asset_log_id")
);

CREATE INDEX "digital_asset_log_user_id" ON digital_asset_log ("user_id");
CREATE INDEX "digital_asset_log_digital_asset_id" ON digital_asset_log ("digital_asset_id");
