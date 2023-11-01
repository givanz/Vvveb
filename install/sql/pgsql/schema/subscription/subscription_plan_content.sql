DROP TABLE IF EXISTS "subscription_plan_content";

CREATE TABLE "subscription_plan_content" (
  "subscription_plan_id" INT NOT NULL,
  "language_id" INT NOT NULL,
  "name" TEXT NOT NULL,
  PRIMARY KEY ("subscription_plan_id","language_id")
);
