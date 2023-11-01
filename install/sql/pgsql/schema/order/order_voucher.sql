DROP TABLE IF EXISTS order_voucher;

DROP SEQUENCE IF EXISTS order_voucher_seq;
CREATE SEQUENCE order_voucher_seq;


CREATE TABLE order_voucher (
  "order_voucher_id" int check ("order_voucher_id" > 0) NOT NULL DEFAULT NEXTVAL ('order_voucher_seq'),
  "order_id" int check ("order_id" > 0) NOT NULL,
  "voucher_id" int check ("voucher_id" > 0) NOT NULL,
  "content" varchar(191) NOT NULL,
  "voucher" varchar(10) NOT NULL,
  "from_name" varchar(64) NOT NULL,
  "from_email" varchar(96) NOT NULL,
  "to_name" varchar(64) NOT NULL,
  "to_email" varchar(96) NOT NULL,
  "message" text NOT NULL,
  "amount" decimal(15,4) NOT NULL,
  PRIMARY KEY ("order_voucher_id")
);