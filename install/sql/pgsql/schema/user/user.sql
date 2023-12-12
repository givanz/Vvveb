DROP TABLE IF EXISTS "user";

DROP SEQUENCE IF EXISTS "user_seq";
CREATE SEQUENCE "user_seq";


CREATE TABLE "user" (
  "user_id" int check ("user_id" > 0) NOT NULL DEFAULT NEXTVAL ('user_seq'),
  "user_group_id" int check ("user_group_id" > 0) NOT NULL DEFAULT 1,
  "username" varchar(60) NOT NULL DEFAULT '',
  "first_name" varchar(32) NOT NULL DEFAULT '',
  "last_name" varchar(32) NOT NULL DEFAULT '',
  "password" varchar(191) NOT NULL DEFAULT '',
  "email" varchar(100) NOT NULL DEFAULT '',
  "phone_number" varchar(32) NOT NULL DEFAULT '',
  "url" varchar(100) NOT NULL DEFAULT '',
  "status" int check ("status" >= 0) NOT NULL DEFAULT 0,
  "display_name" varchar(250) NOT NULL DEFAULT '',
  "avatar" varchar(250) NOT NULL DEFAULT '',
  "bio" varchar(250) NOT NULL DEFAULT '',
  "token" varchar(32) NOT NULL DEFAULT '',
  "created_at" timestamp(0) NOT NULL DEFAULT '2022-05-01 00:00:00',
  "updated_at" timestamp(0) NOT NULL DEFAULT '2022-05-01 00:00:00',
  PRIMARY KEY ("user_id")
);

CREATE INDEX "user_username" ON "user" ("username");
CREATE INDEX "user_email" ON "user" ("email");
