DROP TABLE IF EXISTS user_group_content;

CREATE TABLE user_group_content (
  "user_group_id" int check ("user_group_id" > 0) NOT NULL,
  "language_id" int check ("language_id" > 0) NOT NULL,
  "name" varchar(32) NOT NULL,
  "content" text NOT NULL,
  PRIMARY KEY ("user_group_id","language_id")
);
