DROP TABLE IF EXISTS order_status;

DROP SEQUENCE IF EXISTS order_status_seq;
CREATE SEQUENCE order_status_seq;


CREATE TABLE order_status (
  "order_status_id" int check ("order_status_id" > 0) NOT NULL DEFAULT NEXTVAL ('order_status_seq'),
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(32) NOT NULL,
  PRIMARY KEY ("order_status_id","language_id")
);