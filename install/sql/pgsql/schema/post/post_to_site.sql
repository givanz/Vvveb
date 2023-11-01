DROP TABLE IF EXISTS post_to_site;

CREATE TABLE post_to_site (
  "post_id" int check ("post_id" > 0) NOT NULL,
  "site_id" smallint NOT NULL DEFAULT 0,
  PRIMARY KEY ("post_id","site_id")
);
