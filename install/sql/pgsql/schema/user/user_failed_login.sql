DROP TABLE IF EXISTS "user_failed_login";

CREATE TABLE "user_failed_login" (
  "user_id" int check ("user_id" > 0) NOT NULL,
  "count" int check ("count" > 0) NOT NULL DEFAULT 1,
  "last_ip" varchar(16) NOT NULL DEFAULT '',
  "updated_at" timestamp(0) NOT NULL DEFAULT now(),
  PRIMARY KEY ("user_id", "updated_at")
);