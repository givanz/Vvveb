DROP TABLE IF EXISTS option;

DROP SEQUENCE IF EXISTS option_seq;
CREATE SEQUENCE option_seq;

CREATE TABLE "option" (
  "option_id" int check ("option_id" > 0) NOT NULL DEFAULT NEXTVAL ('option_seq'),
  "type" TEXT NOT NULL,
  "sort_order" INT NOT NULL,
 PRIMARY KEY ("option_id", "type")
);
