DROP TABLE IF EXISTS digital_asset_stats;

DROP SEQUENCE IF EXISTS digital_asset_stats_seq;
CREATE SEQUENCE digital_asset_stats_seq;
-- SELECT setval('digital_asset_stats_seq', 0, true); -- last inserted id by sample data

CREATE TABLE digital_asset_stats (
  "digital_asset_stats_id" int check ("digital_asset_stats_id" > 0) NOT NULL DEFAULT NEXTVAL ('digital_asset_stats_seq'),
  "digital_asset_id" int check ("digital_asset_id" > 0) NOT NULL,
  "site_id" smallint NOT NULL,
  "ip" varchar(40) NOT NULL,
  "country" varchar(2) NOT NULL,
  "created_at" timestamp(0) NOT NULL DEFAULT now(),
  PRIMARY KEY ("digital_asset_stats_id")
);
