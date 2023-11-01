DROP TABLE IF EXISTS role;

DROP SEQUENCE IF EXISTS role_seq;
CREATE SEQUENCE role_seq;

CREATE TABLE role (
  "role_id" int check ("role_id" > 0) NOT NULL DEFAULT NEXTVAL ('role_seq'),
  "name" varchar(191) NOT NULL,
  "display_name" varchar(191) NOT NULL,
  "permissions" text NOT NULL,
  PRIMARY KEY ("role_id")
);