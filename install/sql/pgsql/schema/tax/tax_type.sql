DROP TABLE IF EXISTS tax_type;


DROP SEQUENCE IF EXISTS tax_type_seq;
CREATE SEQUENCE tax_type_seq;
SELECT setval('tax_type_seq', 3, true); -- last inserted id by sample data

CREATE TABLE tax_type (
  "tax_type_id" int check ("tax_type_id" > 0) NOT NULL DEFAULT NEXTVAL ('tax_type_seq'),
  "name" varchar(32) NOT NULL,
  "content" varchar(191) NOT NULL,
  "created_at" timestamp(0) NOT NULL DEFAULT now(),
  "updated_at" timestamp(0) NOT NULL DEFAULT now(),
  PRIMARY KEY ("tax_type_id")
);
