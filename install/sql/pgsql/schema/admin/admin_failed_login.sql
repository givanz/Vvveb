DROP TABLE IF EXISTS "admin_failed_login";

CREATE TABLE "admin_failed_login" (
  "admin_id" int check ("admin_id" > 0) NOT NULL,
  "count" int check ("count" > 0) NOT NULL DEFAULT 1,
  "last_ip" varchar(16) NOT NULL DEFAULT '',
  "updated_at" timestamp(0) NOT NULL DEFAULT now(),
  PRIMARY KEY ("admin_id", "updated_at")
);