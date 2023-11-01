DROP TABLE IF EXISTS coupon;

DROP SEQUENCE IF EXISTS coupon_seq;
CREATE SEQUENCE coupon_seq;


CREATE TABLE coupon (
  "coupon_id" int check ("coupon_id" > 0) NOT NULL DEFAULT NEXTVAL ('coupon_seq'),
  "name" varchar(128) NOT NULL,
  "code" varchar(20) NOT NULL,
  "type" char(1) NOT NULL,
  "discount" decimal(15,4) NOT NULL,
  "total" decimal(15,4) NOT NULL,
  "limit" int check ("limit" > 0) NOT NULL,
  "limit_user" varchar(11) NOT NULL,
  "logged_in" smallint NOT NULL,
  "free_shipping" smallint NOT NULL,
  "status" smallint NOT NULL,
  "from_date" date NOT NULL DEFAULT '1000-01-01',
  "to_date" date NOT NULL DEFAULT '1000-01-01',
  "created_at" timestamp(0) NOT NULL DEFAULT clock_timestamp(),
  "updated_at" timestamp(0) NOT NULL DEFAULT clock_timestamp(),
  PRIMARY KEY ("coupon_id")
);
