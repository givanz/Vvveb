DROP TABLE IF EXISTS comment;

DROP SEQUENCE IF EXISTS comment_seq;
CREATE SEQUENCE comment_seq;


CREATE TABLE comment (
  "comment_id" int check ("comment_id" > 0) NOT NULL DEFAULT NEXTVAL ('comment_seq'),
  "post_id" int check ("post_id" > 0) NOT NULL DEFAULT 0,
  "user_id" int check ("user_id" > 0) NOT NULL DEFAULT 0,
  "author" varchar(100) NOT NULL,
  "email" varchar(100) NOT NULL DEFAULT '',
  "url" varchar(200) NOT NULL DEFAULT '',
  "ip" varchar(100) NOT NULL DEFAULT '',
  "content" text NOT NULL,
  "status" smallint check ("status" >= 0) NOT NULL DEFAULT 0,
  "votes" smallint check ("votes" >= 0) NOT NULL DEFAULT 0,
  "type" varchar(20) NOT NULL DEFAULT '',
  "parent_id" int check ("parent_id" >= 0) NOT NULL DEFAULT 0,
  "created_at" timestamp(0) NOT NULL,
  "updated_at" timestamp(0) NOT NULL,
  PRIMARY KEY ("comment_id")
);

CREATE INDEX "post_id" ON comment ("post_id","status");
CREATE INDEX "comment_parent" ON comment ("parent_id");
CREATE INDEX "comment_email" ON comment ("email");