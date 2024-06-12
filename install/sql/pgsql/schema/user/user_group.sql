DROP TABLE IF EXISTS user_group;

DROP SEQUENCE IF EXISTS user_group_seq;
CREATE SEQUENCE user_group_seq;
SELECT setval('user_group_seq', 1, true); -- last inserted id by sample data


CREATE TABLE user_group (
  "user_group_id" int check ("user_group_id" > 0) NOT NULL DEFAULT NEXTVAL ('user_group_seq'),
  "status" int NOT NULL,
  "sort_order" int NOT NULL DEFAULT 0,
  PRIMARY KEY ("user_group_id")
);
