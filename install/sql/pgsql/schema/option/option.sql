DROP TABLE IF EXISTS option;

DROP SEQUENCE IF EXISTS option_seq;
CREATE SEQUENCE option_seq;
SELECT setval('option_seq', 14, true); -- last inserted id by sample data

CREATE TABLE "option" (
  "option_id" int check ("option_id" > 0) NOT NULL DEFAULT NEXTVAL ('option_seq'),
  "type" TEXT NOT NULL,
  "sort_order" INT NOT NULL DEFAULT 0,
 PRIMARY KEY ("option_id", "type")
);
