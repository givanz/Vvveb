DROP TABLE IF EXISTS attribute;

DROP SEQUENCE IF EXISTS attribute_seq;
CREATE SEQUENCE attribute_seq;

CREATE TABLE "attribute" (
  "attribute_id" int check ("attribute_id" > 0) NOT NULL DEFAULT NEXTVAL ('attribute_seq'),
  "attribute_group_id" int check ("attribute_group_id" > 0) NOT NULL DEFAULT 0,
  "sort_order" INT NOT NULL,
 PRIMARY KEY ("attribute_id")
);
