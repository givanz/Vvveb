DROP TABLE IF EXISTS order_meta;

DROP SEQUENCE IF EXISTS order_meta_seq;
CREATE SEQUENCE order_meta_seq;


CREATE TABLE order_meta (
  "meta_id" int check ("meta_id" > 0) NOT NULL DEFAULT NEXTVAL ('order_meta_seq'),
  "order_id" int check ("order_id" > 0) NOT NULL DEFAULT 0,
  "key" varchar(191) DEFAULT NULL,
  "value" text DEFAULT NULL,
  PRIMARY KEY ("meta_id")
);

CREATE INDEX "order_meta_order_id" ON order_meta ("order_id");
CREATE INDEX "order_meta_key" ON order_meta ("key");