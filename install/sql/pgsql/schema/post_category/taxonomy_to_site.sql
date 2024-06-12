DROP TABLE IF EXISTS taxonomy_to_site;
CREATE TABLE taxonomy_to_site (
  "taxonomy_item_id" int check ("taxonomy_item_id" > 0) NOT NULL,
  "site_id" smallint NOT NULL,
  PRIMARY KEY ("taxonomy_item_id","site_id")
);