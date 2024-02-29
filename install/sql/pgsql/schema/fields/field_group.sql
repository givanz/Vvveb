DROP TABLE IF EXISTS field_group;

DROP SEQUENCE IF EXISTS field_group_seq;
CREATE SEQUENCE field_group_seq;
-- SELECT setval('field_group_seq', 0, true); -- last inserted id by sample data


CREATE TABLE field_group (
  "field_group_id" int NOT NULL DEFAULT NEXTVAL ('field_group_seq'),
  "type" varchar(128) NOT NULL DEFAULT 'post', -- post, product, user, taxonomy item
  "status" smallint NOT NULL DEFAULT 1,
  "sort_order" int NOT NULL DEFAULT 0,
  PRIMARY KEY ("field_group_id")
);
