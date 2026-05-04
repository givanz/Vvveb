-- DROP TABLE IF EXISTS "newsletter_mail";

CREATE TABLE IF NOT EXISTS "newsletter_mail" (
  "newsletter_list_id" bigint,
  "email" varchar(254) NOT NULL,
  "first_name" varchar(254) NOT NULL DEFAULT '',
  "last_name" varchar(254) NOT NULL DEFAULT '',
  "data" text NOT NULL,
  "status" smallint NOT NULL DEFAULT 0, -- inactive = 0, active = 1, unsubsubscribed = 2 
  "created_at" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX "newsletter_mail_email_newsletter_list_id" ON newsletter_mail ("email","newsletter_list_id");
