DROP TABLE IF EXISTS product_image;

DROP SEQUENCE IF EXISTS product_image_seq;
CREATE SEQUENCE product_image_seq;


CREATE TABLE product_image (
  "product_image_id" int check ("product_image_id" > 0) NOT NULL DEFAULT NEXTVAL ('product_image_seq'),
  "product_id" int check ("product_id" > 0) NOT NULL,
  "image" varchar(191) NOT NULL,
  "sort_order" int NOT NULL DEFAULT 0,
  PRIMARY KEY ("product_image_id")
);

CREATE INDEX "product_image_product_id" ON product_image ("product_id");