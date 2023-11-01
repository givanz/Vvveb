DROP TABLE IF EXISTS product_review;

DROP SEQUENCE IF EXISTS product_review_seq;
CREATE SEQUENCE product_review_seq;


CREATE TABLE product_review (
  "product_review_id" int check ("product_review_id" > 0) NOT NULL DEFAULT NEXTVAL ('product_review_seq'),
  "product_id" int check ("product_id" > 0) NOT NULL,
  "user_id" int check ("user_id" > 0) NOT NULL,
  "author" varchar(64) NOT NULL,
  "content" text NOT NULL,
  "rating" smallint check ("rating" > 0) NOT NULL,
  "status" smallint NOT NULL DEFAULT 0,
  "parent_id" int check ("parent_id" >= 0) NOT NULL DEFAULT 0,
  "created_at" timestamp(0) NOT NULL,
  "updated_at" timestamp(0) NOT NULL,
  PRIMARY KEY ("product_review_id")
);

CREATE INDEX "product_review_product_id" ON product_review ("product_id");