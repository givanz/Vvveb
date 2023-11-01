DROP TABLE IF EXISTS admin_password_resets;
CREATE TABLE admin_password_resets (
  "email" varchar(191) NOT NULL,
  "token" varchar(191) NOT NULL,
  "created_at" timestamp(0) NULL DEFAULT NULL
);