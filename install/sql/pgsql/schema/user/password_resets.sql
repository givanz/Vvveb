DROP TABLE IF EXISTS "password_resets";

CREATE TABLE "password_resets" (
  "email" varchar(191) NOT NULL,
  "token" varchar(191) NOT NULL,
  "created_at" timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY ("email","token")
);

CREATE INDEX "password_resets_email" ON "password_resets" ("email");
