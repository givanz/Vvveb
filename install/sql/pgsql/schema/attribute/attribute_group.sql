DROP TABLE IF EXISTS attribute_group;

DROP SEQUENCE IF EXISTS attribute_group_seq;
CREATE SEQUENCE attribute_group_seq;

CREATE TABLE "attribute_group" (
 "attribute_group_id" int check ("attribute_group_id" > 0) NOT NULL DEFAULT NEXTVAL ('attribute_group_seq'),
 "sort_order" INT NOT NULL,
 PRIMARY KEY ("attribute_group_id")
);
