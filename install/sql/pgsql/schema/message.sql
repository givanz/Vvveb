DROP TABLE IF EXISTS message;

DROP SEQUENCE IF EXISTS message_seq;
CREATE SEQUENCE message_seq;

-- CREATE TABLE IF NOT EXISTS message (
CREATE TABLE message (
  "message_id" int check ("message_id" > 0) NOT NULL DEFAULT NEXTVAL ('message_seq'),
  "type" varchar(20) NOT NULL DEFAULT 'message',
  "data" text DEFAULT NULL,
  "meta" text DEFAULT NULL,
  "status" smallint NOT NULL DEFAULT 0, -- unread = 0, read = 1
  "created_at" timestamp(0) NOT NULL DEFAULT '2022-05-01 00:00:00',
  "updated_at" timestamp(0) NOT NULL DEFAULT '2022-05-01 00:00:00',
  PRIMARY KEY ("message_id")
);


DROP INDEX IF EXISTS "message_type_status_date";

CREATE INDEX "message_type_status_date" ON message ("type","created_at","message_id");
