DROP TABLE IF EXISTS return_status;

DROP SEQUENCE IF EXISTS return_status_seq;
CREATE SEQUENCE return_status_seq;


CREATE TABLE return_status (
  "return_status_id" int check ("return_status_id" > 0) NOT NULL DEFAULT NEXTVAL ('return_status_seq'),
  "language_id" int check ("language_id" > 0) NOT NULL DEFAULT 0,
  "name" varchar(32) NOT NULL,
  PRIMARY KEY ("return_status_id","language_id")
);