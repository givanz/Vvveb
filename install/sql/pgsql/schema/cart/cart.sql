DROP TABLE IF EXISTS "cart";

DROP SEQUENCE IF EXISTS cart_seq;
CREATE SEQUENCE cart_seq;

CREATE TABLE cart (
  "cart_id" int check ("cart_id" > 0) NOT NULL DEFAULT NEXTVAL ('cart_seq'),
  "user_id" int check ("user_id" >= -1) NOT NULL DEFAULT 0,
  "session_id" varchar(32) NOT NULL DEFAULT '',
  "data" text NOT NULL,
  "created_at"  timestamp(0) NOT NULL DEFAULT now(),
  PRIMARY KEY ("cart_id")
);

CREATE INDEX "cart_user_id" ON cart ("user_id");