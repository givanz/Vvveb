DROP TABLE IF EXISTS subscription_status;

DROP SEQUENCE IF EXISTS subscription_status_seq;
CREATE SEQUENCE subscription_status_seq;


CREATE TABLE subscription_status (
  "subscription_status_id" int check ("subscription_status_id" > 0) NOT NULL DEFAULT NEXTVAL ('subscription_status_seq'),
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(32) NOT NULL,
  PRIMARY KEY ("subscription_status_id","language_id")
);