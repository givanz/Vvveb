DROP TABLE IF EXISTS subscription;

DROP SEQUENCE IF EXISTS subscription_seq;
CREATE SEQUENCE subscription_seq;

CREATE TABLE "subscription" (
  "subscription_id" int check ("subscription_status_id" > 0) NOT NULL DEFAULT NEXTVAL ('subscription_seq'),
  "order_id" INT NOT NULL,
  "order_product_id" INT NOT NULL,
  "site_id" INT NOT NULL,
  "user_id" INT NOT NULL,
  "payment_address_id" INT NOT NULL,
  "payment_method" text NOT NULL,
  "shipping_address_id" INT NOT NULL,
  "shipping_method" text NOT NULL,
  "product_id" INT NOT NULL,
  "quantity" INT NOT NULL,
  "subscription_plan_id" INT NOT NULL,
  "price" decimal(10,4) NOT NULL,
  "period" TEXT CHECK( period IN ('day','week','month','year') ) NOT NULL DEFAULT 'month',
  "cycle" smallint NOT NULL,
  "length" smallint NOT NULL,
  "left" smallint NOT NULL,
  "trial_price" decimal(10,4) NOT NULL,
  "trial_period" TEXT CHECK( trial_period IN ('day','week','month','year') ) NOT NULL DEFAULT 'month',
  "trial_cycle" smallint NOT NULL,
  "trial_length" smallint NOT NULL,
  "trial_left" smallint NOT NULL,
  "trial_status" smallint NOT NULL,
  "date_next" timestamp(0) NOT NULL DEFAULT '2022-05-01 00:00:00',
  "note" text NOT NULL,
  "subscription_status_id" INT NOT NULL,
  "language_id" INT NOT NULL,
  "currency_id" INT NOT NULL,
  "ip" TEXT NOT NULL,
  "forwarded_ip" TEXT NOT NULL,
  "user_agent" TEXT NOT NULL,
  "created_at" timestamp(0) NOT NULL DEFAULT '2022-05-01 00:00:00',
  "updated_at" timestamp(0) NOT NULL DEFAULT '2022-05-01 00:00:00',
PRIMARY KEY ("subscription_id")
);

CREATE INDEX "subscription_order_id" ON "subscription" ("order_id");
