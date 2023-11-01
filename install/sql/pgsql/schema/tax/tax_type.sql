DROP TABLE IF EXISTS tax_type;


DROP SEQUENCE IF EXISTS tax_type_seq;
CREATE SEQUENCE tax_type_seq;

CREATE TABLE tax_type (
  "tax_type_id" int check ("tax_type_id" > 0) NOT NULL DEFAULT NEXTVAL ('tax_type_seq'),
  "name" varchar(32) NOT NULL,
  "content" varchar(191) NOT NULL,
  "created_at" timestamp(0) NOT NULL DEFAULT current_timestamp,
  "updated_at" timestamp(0) NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY ("tax_type_id")
);