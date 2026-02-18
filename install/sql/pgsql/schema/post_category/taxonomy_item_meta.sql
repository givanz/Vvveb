DROP TABLE IF EXISTS taxonomy_item_meta;

-- DROP SEQUENCE IF EXISTS taxonomy_item_meta_seq;
-- CREATE SEQUENCE taxonomy_item_meta_seq;
-- SELECT setval('taxonomy_item_meta_taxonomy_item_meta_id_seq', 0, true); -- last inserted id by sample data

CREATE TABLE taxonomy_item_meta (
  "taxonomy_item_id" int check ("taxonomy_item_id" > 0) NOT NULL,
  "namespace" varchar(32) NOT NULL DEFAULT '',
  "key" varchar(191) NOT NULL,
  "value" text DEFAULT NULL,
  PRIMARY KEY("taxonomy_item_id","namespace","key")
);

-- CREATE UNIQUE INDEX "taxonomy_item_meta_taxonomy_item_id" ON taxonomy_item_meta ("taxonomy_item_id","namespace","key");
