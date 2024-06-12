DROP TABLE IF EXISTS "attribute_group_content";

CREATE TABLE "attribute_group_content" (
  "attribute_group_id" INT NOT NULL,
  "language_id" INT NOT NULL,
  "name" TEXT NOT NULL,
  PRIMARY KEY ("attribute_group_id","language_id")
);
