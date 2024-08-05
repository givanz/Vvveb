DROP TABLE IF EXISTS "order";

DROP SEQUENCE IF EXISTS order_seq;
CREATE SEQUENCE order_seq;
-- SELECT setval('order_seq', 0, true); -- last inserted id by sample data

CREATE TYPE shipping_status AS ENUM
    ('not_fulfilled', 'partially_fulfilled', 'fulfilled', 'partially_shipped', 'shipped', 'partially_returned', 'returned', 'canceled', 'requires_action');

CREATE TYPE shipping_status AS ENUM
    ('not_paid', 'awaiting', 'captured', 'partially_refunded', 'refunded', 'canceled', 'requires_action');


CREATE TABLE "order" (
  "order_id" int check ("order_id" > 0) NOT NULL DEFAULT NEXTVAL ('order_seq'),
  "invoice_no" varchar(64) NOT NULL DEFAULT '0',
  "customer_order_id" varchar(64) NOT NULL DEFAULT '0',
  "invoice_prefix" varchar(26) NOT NULL DEFAULT 'I-',
  "site_id" smallint NOT NULL DEFAULT 0,
  "site_name" varchar(64) NOT NULL,
  "site_url" varchar(191) NOT NULL,
  "user_id" int NOT NULL DEFAULT 0,
  "user_group_id" int check ("user_group_id" > 0) NOT NULL DEFAULT 1,
  "first_name" varchar(32) NOT NULL,
  "last_name" varchar(32) NOT NULL,
  "email" varchar(96) NOT NULL,
  "phone_number" varchar(32) NOT NULL DEFAULT '',
  "billing_first_name" varchar(32) NOT NULL,
  "billing_last_name" varchar(32) NOT NULL,
  "billing_company" varchar(60) NOT NULL DEFAULT '',
  "billing_address_1" varchar(128) NOT NULL,
  "billing_address_2" varchar(128) NOT NULL DEFAULT '',
  "billing_city" varchar(128) NOT NULL DEFAULT '',
  "billing_post_code" varchar(10) NOT NULL DEFAULT '',
  "billing_country" varchar(128) NOT NULL DEFAULT '',
  "billing_country_id" int check ("billing_country_id" > 0) NOT NULL,
  "billing_region" varchar(128) NOT NULL  DEFAULT '',
  "billing_region_id" int check ("billing_region_id" > 0) NOT NULL,
--  "billing_fields" text NOT NULL,
  "payment_method" varchar(128) NOT NULL DEFAULT '',
  "payment_data" text NOT NULL DEFAULT '',
  "payment_status_id" int check ("order_status_id" > 0) NOT NULL DEFAULT 1,
  "shipping_first_name" varchar(32) NOT NULL DEFAULT '',
  "shipping_last_name" varchar(32) NOT NULL DEFAULT '',
  "shipping_company" varchar(60) NOT NULL DEFAULT '',
  "shipping_address_1" varchar(128) NOT NULL DEFAULT '',
  "shipping_address_2" varchar(128) NOT NULL DEFAULT '',
  "shipping_city" varchar(128) NOT NULL DEFAULT '',
  "shipping_post_code" varchar(10) NOT NULL DEFAULT '',
  "shipping_country" varchar(128) NOT NULL DEFAULT '',
  "shipping_country_id" int check ("shipping_country_id" > 0) NOT NULL,
  "shipping_region" varchar(128) NOT NULL DEFAULT '',
  "shipping_region_id" int check ("shipping_region_id" > 0) NOT NULL,
--  "shipping_fields" text NOT NULL,
  "shipping_method" varchar(128) NOT NULL DEFAULT '',
  "shipping_data" text NOT NULL DEFAULT '',
  "shipping_status_id" int check ("order_status_id" > 0) NOT NULL DEFAULT 1,
  "total" decimal(15,4) NOT NULL DEFAULT 0.0000,
  "order_status_id" int check ("order_status_id" > 0) NOT NULL DEFAULT 1,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "currency_id" int check ("currency_id" > 0) NOT NULL,
  "currency" varchar(3) NOT NULL DEFAULT '',
  "currency_value" decimal(15,8) NOT NULL DEFAULT 1.00000000,
  "notes" text NOT NULL DEFAULT '',
  "remote_ip" varchar(40) NOT NULL DEFAULT '',
  "forwarded_for_ip" varchar(40) NOT NULL DEFAULT '',
  "user_agent" varchar(191) NOT NULL DEFAULT '',
  "created_at" timestamp(0) NOT NULL DEFAULT now(),
  "updated_at" timestamp(0) NOT NULL DEFAULT now(),
  PRIMARY KEY ("order_id")
);

CREATE INDEX "order_order_status_id" ON order ("site_id","order_status_id","created_at");
CREATE INDEX "order_customer_order_id" ON order ("customer_order_id","order_status_id","created_at");