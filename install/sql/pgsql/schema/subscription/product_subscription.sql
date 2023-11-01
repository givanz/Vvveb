DROP TABLE IF EXISTS product_subscription;

CREATE TABLE product_subscription (
  "product_id" int check ("product_id" > 0) NOT NULL,
  "subscription_plan_id" int check ("subscription_plan_id" > 0) NOT NULL,
  "user_group_id" int check ("user_group_id" > 0) NOT NULL,
  "price" decimal(15,4) NOT NULL DEFAULT 0.0000,
  "trial_price" decimal(15,4) NOT NULL DEFAULT 0.0000,
  PRIMARY KEY ("product_id","subscription_plan_id","user_group_id")
);
