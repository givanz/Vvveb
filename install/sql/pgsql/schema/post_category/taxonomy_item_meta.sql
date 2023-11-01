DROP TABLE IF EXISTS taxonomy_item_meta;

DROP SEQUENCE IF EXISTS taxonomy_item_meta_seq;
CREATE SEQUENCE taxonomy_item_meta_seq;

CREATE TABLE taxonomy_item_meta (
  "meta_id" int check ("meta_id" > 0) NOT NULL DEFAULT NEXTVAL ('taxonomy_item_meta_seq'),
  "taxonomy_item_id" int check ("taxonomy_item_id" > 0) NOT NULL DEFAULT 0,
  "key" varchar(191) DEFAULT NULL,
  "value" text DEFAULT NULL,
  PRIMARY KEY ("meta_id")
);

CREATE INDEX "taxonomy_item_meta_taxonomy_item_id" ON taxonomy_item_meta ("taxonomy_item_id");
CREATE INDEX "taxonomy_item_meta_key" ON taxonomy_item_meta ("key");
