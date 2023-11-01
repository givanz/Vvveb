DROP TABLE IF EXISTS product_points;

DROP SEQUENCE IF EXISTS product_points_seq;
CREATE SEQUENCE product_points_seq;


CREATE TABLE product_points (
  "product_points_id" int check ("product_points_id" > 0) NOT NULL DEFAULT NEXTVAL ('product_points_seq'),
  "product_id" int check ("product_id" > 0) NOT NULL DEFAULT 0,
  "user_group_id" int check ("user_group_id" > 0) NOT NULL DEFAULT 0,
  "points" int NOT NULL DEFAULT 0,
  PRIMARY KEY ("product_points_id")
);