DROP TABLE IF EXISTS subscription_status;

DROP SEQUENCE IF EXISTS subscription_status_seq;
CREATE SEQUENCE subscription_status_seq;
SELECT setval('subscription_status_seq', 6, true); -- last inserted id by sample data


CREATE TABLE subscription_status (
  "subscription_status_id" int check ("subscription_status_id" > 0) NOT NULL DEFAULT NEXTVAL ('subscription_status_seq'),
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(32) NOT NULL,
  PRIMARY KEY ("subscription_status_id","language_id")
);
