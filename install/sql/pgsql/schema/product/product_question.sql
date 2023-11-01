DROP TABLE IF EXISTS product_question;

DROP SEQUENCE IF EXISTS product_question_seq;
CREATE SEQUENCE product_question_seq;


CREATE TABLE product_question (
  "product_question_id" int check ("product_question_id" > 0) NOT NULL DEFAULT NEXTVAL ('product_question_seq'),
  "product_id" int check ("product_id" > 0) NOT NULL,
  "user_id" int check ("user_id" > 0) NOT NULL,
  "author" varchar(64) NOT NULL,
  "content" text NOT NULL,
  "status" smallint NOT NULL DEFAULT 0,
  "parent_id" int check ("parent_id" >= 0) NOT NULL DEFAULT 0,
  "created_at" timestamp(0) NOT NULL,
  "updated_at" timestamp(0) NOT NULL,
  PRIMARY KEY ("product_question_id")
);

CREATE INDEX "product_question_product_id" ON product_question ("product_id");
