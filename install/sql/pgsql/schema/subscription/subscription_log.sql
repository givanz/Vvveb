DROP TABLE IF EXISTS subscription_log;

DROP SEQUENCE IF EXISTS subscription_log_seq;
CREATE SEQUENCE subscription_log_seq;

CREATE TABLE "subscription_log" (
  "subscription_log_id" int check ("subscription_log_id" > 0) NOT NULL DEFAULT NEXTVAL ('subscription_log_seq'),
  "subscription_id" INT NOT NULL,
  "subscription_status_id" INT NOT NULL,
  "notify" smallint NOT NULL DEFAULT 0,
  "note" text NOT NULL,
  "created_at" timestamp(0) NOT NULL DEFAULT '2022-05-01 00:00:00',
PRIMARY KEY ("subscription_log_id")
);
