DROP TABLE IF EXISTS return_resolution;

DROP SEQUENCE IF EXISTS return_resolution_seq;
CREATE SEQUENCE return_resolution_seq;

CREATE TABLE return_resolution (
  "return_resolution_id" int check ("return_resolution_id" > 0) NOT NULL DEFAULT NEXTVAL ('return_resolution_seq'),
  "language_id" int check ("language_id" > 0) NOT NULL DEFAULT 0,
  "name" varchar(64) NOT NULL,
  PRIMARY KEY ("return_resolution_id","language_id")
);