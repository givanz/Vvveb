DROP TABLE IF EXISTS voucher;

DROP SEQUENCE IF EXISTS voucher_seq;
CREATE SEQUENCE voucher_seq;


CREATE TABLE voucher (
  "voucher_id" int check ("voucher_id" > 0) NOT NULL DEFAULT NEXTVAL ('voucher_seq'),
  "order_id" int check ("order_id" > 0) NOT NULL,
  "code" varchar(10) NOT NULL,
  "from_name" varchar(64) NOT NULL,
  "from_email" varchar(96) NOT NULL,
  "to_name" varchar(64) NOT NULL,
  "to_email" varchar(96) NOT NULL,
  "message" text NOT NULL,
  "credit" decimal(15,4) NOT NULL,
  "status" smallint NOT NULL,
  "created_at" timestamp(0) NOT NULL,
  PRIMARY KEY ("voucher_id")
);