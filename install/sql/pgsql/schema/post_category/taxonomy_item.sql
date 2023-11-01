DROP TABLE IF EXISTS taxonomy_item;

DROP SEQUENCE IF EXISTS taxonomy_item_seq;
CREATE SEQUENCE taxonomy_item_seq;


CREATE TABLE taxonomy_item (
  "taxonomy_item_id" int check ("taxonomy_item_id" > 0) NOT NULL DEFAULT NEXTVAL ('taxonomy_item_seq'),
  "taxonomy_id" int check ("taxonomy_id" > 0) NOT NULL,
  "image" varchar(191) NOT NULL DEFAULT '',
  "template" varchar(191) NOT NULL DEFAULT '',
  "parent_id" int check ("parent_id" >= 0) NOT NULL DEFAULT 0,
  "item_id" int check ("item_id" > 0) DEFAULT NULL,
  "sort_order" int NOT NULL DEFAULT 0,
  "status" smallint NOT NULL DEFAULT 0,
  PRIMARY KEY ("taxonomy_item_id")
);

CREATE INDEX "taxonomy_item_parent_id" ON taxonomy_item ("parent_id");