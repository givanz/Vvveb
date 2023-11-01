DROP TABLE IF EXISTS order_total;

DROP SEQUENCE IF EXISTS order_total_seq;
CREATE SEQUENCE order_total_seq;


CREATE TABLE order_total (
  "order_total_id" int NOT NULL DEFAULT NEXTVAL ('order_total_seq'),
  "order_id" int check ("order_id" > 0) NOT NULL,
  "code" varchar(32) NOT NULL,
  "title" varchar(191) NOT NULL,
  "value" decimal(15,4) NOT NULL DEFAULT 0.0000,
  "sort_order" int NOT NULL,
  PRIMARY KEY ("order_total_id")
);

CREATE INDEX "order_id" ON order_total ("order_id");