DROP TABLE IF EXISTS return_log;

DROP SEQUENCE IF EXISTS return_log_seq;
CREATE SEQUENCE return_log_seq;
-- SELECT setval('return_log_seq', 0, true); -- last inserted id by sample data


CREATE TABLE return_log (
  "return_log_id" int check ("return_log_id" > 0) NOT NULL DEFAULT NEXTVAL ('return_log_seq'),
  "return_id" int check ("return_id" > 0) NOT NULL,
  "return_status_id" int check ("return_status_id" > 0) NOT NULL,
  "notify" smallint NOT NULL,
  "note" text NOT NULL,
  "created_at" timestamp(0) NOT NULL DEFAULT now(),
  PRIMARY KEY ("return_log_id")
);
