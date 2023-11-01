DROP TABLE IF EXISTS return_reason;

DROP SEQUENCE IF EXISTS return_reason_seq;
CREATE SEQUENCE return_reason_seq;


CREATE TABLE return_reason (
  "return_reason_id" int check ("return_reason_id" > 0) NOT NULL DEFAULT NEXTVAL ('return_reason_seq'),
  "language_id" int check ("language_id" > 0) NOT NULL DEFAULT 0,
  "name" varchar(128) NOT NULL,
  PRIMARY KEY ("return_reason_id","language_id")
);