DROP TABLE IF EXISTS stock_status;

DROP SEQUENCE IF EXISTS stock_status_seq;
CREATE SEQUENCE stock_status_seq;
SELECT setval('stock_status_seq', 7, true); -- last inserted id by sample data


CREATE TABLE stock_status (
  "stock_status_id" int check ("stock_status_id" > 0) NOT NULL DEFAULT NEXTVAL ('stock_status_seq'),
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(32) NOT NULL,
  PRIMARY KEY ("stock_status_id","language_id")
);
