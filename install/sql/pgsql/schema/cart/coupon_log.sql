DROP TABLE IF EXISTS coupon_log;

DROP SEQUENCE IF EXISTS coupon_log_seq;
CREATE SEQUENCE coupon_log_seq;

CREATE TABLE coupon_log (
  "coupon_log_id" int check ("coupon_log_id" > 0) NOT NULL DEFAULT NEXTVAL ('coupon_log_seq'),
  "coupon_id" int check ("coupon_id" > 0) NOT NULL,
  "order_id" int check ("order_id" > 0) NOT NULL,
  "user_id" int check ("user_id" > 0) NOT NULL,
  "discount" decimal(15,4) NOT NULL,
  "created_at" timestamp(0) NOT NULL,
  PRIMARY KEY ("coupon_log_id")
);
