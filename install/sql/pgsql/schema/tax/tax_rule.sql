DROP TABLE IF EXISTS tax_rule;

DROP SEQUENCE IF EXISTS tax_rule_seq;
CREATE SEQUENCE tax_rule_seq;

CREATE TABLE tax_rule (
  "tax_rule_id" int check ("tax_rule_id" > 0) NOT NULL DEFAULT NEXTVAL ('tax_rule_seq'),
  "tax_type_id" int check ("tax_type_id" > 0) NOT NULL,
  "tax_rate_id" int check ("tax_rate_id" > 0) NOT NULL,
  "based" varchar(10) NOT NULL,
  "priority" int NOT NULL DEFAULT 1,
  PRIMARY KEY ("tax_rule_id")
);