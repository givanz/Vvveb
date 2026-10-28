-- DROP TABLE IF EXISTS "newsletter_mail";

CREATE TABLE IF NOT EXISTS "newsletter_mail" (
  "newsletter_list_id" INT NOT NULL,
  "email" varchar(254) NOT NULL,
  "first_name" varchar(254) NOT NULL DEFAULT '',
  "last_name" varchar(254) NOT NULL DEFAULT '',
  "data" text NOT NULL,
  "status"  smallint NOT NULL DEFAULT '0', -- inactive = 0, active = 1, unsubsubscribed = 2 
  "created_at" timestamp(0) NOT NULL DEFAULT now(),
  "updated_at" timestamp(0) NOT NULL DEFAULT now(),
  PRIMARY KEY ("email", "newsletter_list_id")
);

CREATE INDEX "newsletter_mail_newsletter_list_id" ON newsletter_mail ("newsletter_list_id");
