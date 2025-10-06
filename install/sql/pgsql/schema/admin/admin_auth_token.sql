DROP TABLE IF EXISTS "admin_auth_token";

-- DROP SEQUENCE IF EXISTS admin_admin_id_seq;
-- CREATE SEQUENCE admin_admin_id_seq;

CREATE TABLE "admin_auth_token" (
  "admin_auth_token_id" SERIAL PRIMARY KEY,
  "admin_id" int check ("admin_id" > 0) NOT NULL,
  "token" varchar(191) NOT NULL DEFAULT '',
  "description" varchar(191) NOT NULL DEFAULT '',
  "created_at" timestamp(0) NOT NULL DEFAULT now(),
  "updated_at" timestamp(0) NOT NULL DEFAULT now()
);

CREATE INDEX "admin_auth_token_token" ON admin ("token", "admin_id");

