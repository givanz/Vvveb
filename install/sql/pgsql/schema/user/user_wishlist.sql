DROP TABLE IF EXISTS user_wishlist;

CREATE TABLE user_wishlist (
  "user_id" int check ("user_id" > 0) NOT NULL,
  "product_id" int check ("product_id" > 0) NOT NULL,
  "created_at" timestamp(0) NOT NULL,
  PRIMARY KEY ("user_id","product_id")
);
