DROP TABLE IF EXISTS order_subscription;

DROP SEQUENCE IF EXISTS order_subscription_seq;
CREATE SEQUENCE order_subscription_seq;

CREATE TABLE "order_subscription" (
  "order_subscription_id" int check ("order_subscription_id" > 0) NOT NULL DEFAULT NEXTVAL ('order_subscription_seq'),
  "order_product_id" INT NOT NULL,
  "order_id" INT NOT NULL,
  "product_id" INT NOT NULL,
  "subscription_plan_id" INT NOT NULL,
  "trial_price" decimal(10,4) NOT NULL,
  "trial_tax" decimal(15,4) NOT NULL,
  "trial_period" TEXT CHECK( trial_period IN ('day','week','month','year') ) NOT NULL DEFAULT 'month',
  "trial_cycle" smallint NOT NULL,
  "trial_length" smallint NOT NULL,
  "trial_left" smallint NOT NULL,
  "trial_status" smallint NOT NULL,
  "price" decimal(10,4) NOT NULL,
  "tax" decimal(15,4) NOT NULL,
  "period" TEXT CHECK( period IN ('day','week','month','year') ) NOT NULL DEFAULT 'month',
  "cycle" smallint NOT NULL,
  "length" smallint NOT NULL,
PRIMARY KEY ("order_subscription_id")
--  KEY "order_id" ("order_id")
);

-- CREATE INDEX "order_subscription_order_id" ON "order_subscription" ("order_id");
