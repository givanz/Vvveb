DROP TABLE IF EXISTS product_review_media;

DROP SEQUENCE IF EXISTS product_review_media_seq;
CREATE SEQUENCE product_review_media_seq;
-- SELECT setval('product_review_media_seq', 14, true); -- last inserted id by sample data


CREATE TABLE product_review_media (
  "product_review_media_id" int check ("product_review_media_id" > 0) NOT NULL DEFAULT NEXTVAL ('product_review_media_seq'),
  "product_review_id" int check ("product_id" > 0) NOT NULL,
  "product_id" int check ("product_id" > 0) NOT NULL,
  "user_id" int check ("user_id" > 0) NOT NULL,
  "image" varchar(191) NOT NULL,
  "sort_order" int NOT NULL DEFAULT 0,
  PRIMARY KEY ("product_review_media_id")
);

CREATE INDEX "product_review_media_product_id" ON product_review_media ("product_id", "user_id");
CREATE INDEX "product_review_media_product_review_id" ON product_review_media ("product_review_id");
