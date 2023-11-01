DROP TABLE IF EXISTS user_points;

DROP SEQUENCE IF EXISTS user_points_seq;
CREATE SEQUENCE user_points_seq;


CREATE TABLE user_points (
  "user_points_id" int check ("user_points_id" > 0) NOT NULL DEFAULT NEXTVAL ('user_points_seq'),
  "user_id" int check ("user_id" > 0) NOT NULL DEFAULT 0,
  "order_id" int check ("order_id" > 0) NOT NULL DEFAULT 0,
  "content" text NOT NULL,
  "points" int NOT NULL DEFAULT 0,
  "created_at" timestamp(0) NOT NULL,
  PRIMARY KEY ("user_points_id")
);