DROP TABLE IF EXISTS user_address;

DROP SEQUENCE IF EXISTS user_address_seq;
CREATE SEQUENCE user_address_seq;

CREATE TABLE user_address (
  "user_address_id" int check ("user_address_id" > 0) NOT NULL DEFAULT NEXTVAL ('user_address_seq'),
  "user_id" int check ("user_id" > 0) NOT NULL,
  "first_name" varchar(32) NOT NULL,
  "last_name" varchar(32) NOT NULL,
  "company" varchar(60) NOT NULL,
  "address_1" varchar(128) NOT NULL,
  "address_2" varchar(128) NOT NULL,
  "city" varchar(128) NOT NULL,
  "post_code" varchar(10) NOT NULL,
  "country_id" int check ("country_id" > 0) NOT NULL DEFAULT 0,
  "region_id" int check ("region_id" > 0) NOT NULL DEFAULT 0,
  "default_address" smallint check ("default_address" > 0) NOT NULL DEFAULT 0,
  "fields" text NOT NULL,
  PRIMARY KEY ("user_address_id")
);

CREATE INDEX "user_address_user_id" ON user_address ("user_id");
