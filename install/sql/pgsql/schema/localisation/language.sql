DROP TABLE IF EXISTS language;

DROP SEQUENCE IF EXISTS language_seq;
CREATE SEQUENCE language_seq;


CREATE TABLE language (
  "language_id" int check ("language_id" > 0) NOT NULL DEFAULT NEXTVAL ('language_seq'),
  "name" varchar(32) NOT NULL,
  "code" varchar(5) NOT NULL,
  "locale" varchar(20) NOT NULL,
  "sort_order" int NOT NULL DEFAULT 0,
  "status" smallint NOT NULL,
  "default" smallint NOT NULL DEFAULT 0,
  PRIMARY KEY ("language_id")
);

CREATE INDEX "language_name" ON language ("name");