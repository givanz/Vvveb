DROP TABLE IF EXISTS payment_status;

DROP SEQUENCE IF EXISTS payment_status_seq;
CREATE SEQUENCE payment_status_seq;
SELECT setval('payment_status_seq', 20, true); -- last inserted id by sample data


CREATE TABLE payment_status (
  "payment_status_id" int check ("payment_status_id" > 0) NOT NULL DEFAULT NEXTVAL ('payment_status_seq'),
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(32) NOT NULL,
  PRIMARY KEY ("payment_status_id","language_id")
);
