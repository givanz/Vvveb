DROP TABLE IF EXISTS taxonomy;

DROP SEQUENCE IF EXISTS taxonomy_seq;
CREATE SEQUENCE taxonomy_seq;


CREATE TABLE taxonomy (
  "taxonomy_id" int check ("taxonomy_id" > 0) NOT NULL DEFAULT NEXTVAL ('taxonomy_seq'),
  "name" varchar(191) NOT NULL DEFAULT '',
  "post_type" varchar(191) NOT NULL DEFAULT '',
  "type" varchar(50) NOT NULL DEFAULT 'categories',
  "site_id" smallint NOT NULL DEFAULT 0,
  PRIMARY KEY ("taxonomy_id")
);

CREATE INDEX "taxonomy_id" ON taxonomy ("taxonomy_id");
