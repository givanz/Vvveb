DROP TABLE IF EXISTS return;

DROP SEQUENCE IF EXISTS return_seq;
CREATE SEQUENCE return_seq;


CREATE TABLE return (
  "return_id" int check ("return_id" > 0) NOT NULL DEFAULT NEXTVAL ('return_seq'),
  "order_id" int check ("order_id" > 0) NOT NULL,
  "product_id" int check ("product_id" > 0) NOT NULL,
  "user_id" int check ("user_id" > 0) NOT NULL,
  "first_name" varchar(32) NOT NULL,
  "last_name" varchar(32) NOT NULL,
  "email" varchar(96) NOT NULL,
  "phone_number" varchar(32) NOT NULL,
  "product" varchar(191) NOT NULL,
  "model" varchar(64) NOT NULL,
  "quantity" int NOT NULL,
  "opened" smallint NOT NULL,
  "return_reason_id" int check ("return_reason_id" > 0) NOT NULL,
  "return_resolution_id" int check ("return_resolution_id" > 0) NOT NULL,
  "return_status_id" int check ("return_status_id" > 0) NOT NULL,
  "note" text NOT NULL,
  "date_ordered" date NOT NULL,
  "created_at" timestamp(0) NOT NULL,
  "updated_at" timestamp(0) NOT NULL,
  PRIMARY KEY ("return_id")
);