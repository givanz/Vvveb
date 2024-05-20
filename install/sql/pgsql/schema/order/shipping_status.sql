DROP TABLE IF EXISTS shipping_status;

DROP SEQUENCE IF EXISTS shipping_status_seq;
CREATE SEQUENCE shipping_status_seq;
SELECT setval('shipping_status_seq', 20, true); -- last inserted id by sample data


CREATE TABLE shipping_status (
  "shipping_status_id" int check ("shipping_status_id" > 0) NOT NULL DEFAULT NEXTVAL ('shipping_status_seq'),
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(32) NOT NULL,
  PRIMARY KEY ("shipping_status_id","language_id")
);
