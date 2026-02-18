DROP TABLE IF EXISTS "subscription_plan_content";

CREATE TABLE "subscription_plan_content" (
  "subscription_plan_id" INT NOT NULL,
  "language_id" INT NOT NULL,
  "name" varchar(191) NOT NULL DEFAULT '',
  "content" text DEFAULT NULL,
  PRIMARY KEY("subscription_plan_id","language_id")
);
