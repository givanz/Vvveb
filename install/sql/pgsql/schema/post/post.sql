DROP TABLE IF EXISTS post;

DROP SEQUENCE IF EXISTS post_seq;
CREATE SEQUENCE post_seq;


CREATE TABLE post (
  "post_id" int check ("post_id" > 0) NOT NULL DEFAULT NEXTVAL ('post_seq'),
  "admin_id" int check ("admin_id" > 0) NOT NULL DEFAULT 0,
  "status" varchar(20) NOT NULL DEFAULT 'publish',
  "image" varchar(191) NOT NULL DEFAULT '',
  "comment_status" varchar(20) NOT NULL DEFAULT 'open',
  "password" varchar(191) NOT NULL DEFAULT '',
  "parent" int check ("parent" >= 0) NOT NULL DEFAULT 0,
  "sort_order" int check ("sort_order" >= 0) NOT NULL DEFAULT 0,
  "type" varchar(20) NOT NULL DEFAULT 'post',
  "template" varchar(191) NOT NULL DEFAULT '',
  "comment_count" int NOT NULL DEFAULT 0,
  "views" int NOT NULL DEFAULT 0,
  "created_at" timestamp(0) NOT NULL DEFAULT '2022-05-01 00:00:00',
  "updated_at" timestamp(0) NOT NULL DEFAULT '2022-05-01 00:00:00',
  PRIMARY KEY ("post_id")
);

CREATE INDEX "post_type_status_date" ON post ("type","status","sort_order","created_at","post_id");
CREATE INDEX "post_parent" ON post ("parent");
CREATE INDEX "post_author" ON post ("admin_id");