DROP TABLE IF EXISTS voucher_log;

DROP SEQUENCE IF EXISTS voucher_log_seq;
CREATE SEQUENCE voucher_log_seq;


CREATE TABLE voucher_log (
  "voucher_log_id" int check ("voucher_log_id" > 0) NOT NULL DEFAULT NEXTVAL ('voucher_log_seq'),
  "voucher_id" int check ("voucher_id" > 0) NOT NULL,
  "order_id" int check ("order_id" > 0) NOT NULL,
  "credit" decimal(15,4) NOT NULL,
  "created_at" timestamp(0) NOT NULL,
  PRIMARY KEY ("voucher_log_id")
);
