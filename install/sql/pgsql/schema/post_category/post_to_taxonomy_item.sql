DROP TABLE IF EXISTS post_to_taxonomy_item;
CREATE TABLE post_to_taxonomy_item (
  "post_id" int check ("post_id" > 0) NOT NULL,
  "taxonomy_item_id" int check ("taxonomy_item_id" > 0) NOT NULL,
  PRIMARY KEY("post_id","taxonomy_item_id")
);

CREATE INDEX "post_to_taxonomy_item_taxonomy_item_id" ON post_to_taxonomy_item ("taxonomy_item_id");
