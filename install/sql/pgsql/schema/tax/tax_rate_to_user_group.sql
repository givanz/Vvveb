DROP TABLE IF EXISTS tax_rate_to_user_group;

CREATE TABLE tax_rate_to_user_group (
  "tax_rate_id" int check ("tax_rate_id" > 0) NOT NULL,
  "user_group_id" int check ("user_group_id" > 0) NOT NULL,
  PRIMARY KEY ("tax_rate_id","user_group_id")
);
