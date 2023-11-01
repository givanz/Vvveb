DROP TABLE IF EXISTS subscription_plan;

DROP SEQUENCE IF EXISTS subscription_plan_seq;
CREATE SEQUENCE subscription_plan_seq;


CREATE TABLE "subscription_plan" (
  "subscription_plan_id" int check ("subscription_plan_id" > 0) NOT NULL DEFAULT NEXTVAL ('subscription_plan_seq'),
  "period" TEXT CHECK( period IN ('day','week','month','year') ) NOT NULL DEFAULT 'month',
  "length" INT NOT NULL,
  "cycle" INT NOT NULL,
  "trial_period" TEXT CHECK( trial_period IN ('day','week','month','year') ) NOT NULL DEFAULT 'month',
  "trial_length" INT NOT NULL,
  "trial_cycle" INT NOT NULL,
  "trial_status" smallint NOT NULL,
  "status" smallint NOT NULL,
  "sort_order" INT NOT NULL,
PRIMARY KEY ("subscription_plan_id")
);
