DROP TABLE IF EXISTS queue_jobs;

CREATE TABLE queue_jobs (
  "id" bigserial PRIMARY KEY,
  "job_id" varchar(36) NOT NULL,
  "queue" varchar(128) NOT NULL DEFAULT 'default',
  "job" varchar(255) NOT NULL,
  "payload" text,
  "attempts" integer NOT NULL DEFAULT 0,
  "max_attempts" integer NOT NULL DEFAULT 3,
  "available_at" bigint NOT NULL DEFAULT 0,
  "reserved_at" bigint DEFAULT NULL,
  "created_at" bigint NOT NULL DEFAULT 0
);

CREATE UNIQUE INDEX u_queue_jobs_job_id ON queue_jobs ("job_id");
CREATE INDEX idx_queue_jobs_queue_available ON queue_jobs ("queue", "available_at");
CREATE INDEX idx_queue_jobs_reserved ON queue_jobs ("reserved_at");

DROP TABLE IF EXISTS failed_jobs;

CREATE TABLE failed_jobs (
  "job_id" varchar(36) NOT NULL PRIMARY KEY,
  "queue" varchar(128) NOT NULL DEFAULT 'default',
  "job" varchar(255) NOT NULL,
  "payload" text,
  "error" text,
  "attempts" integer NOT NULL DEFAULT 0,
  "max_attempts" integer NOT NULL DEFAULT 3,
  "failed_at" bigint NOT NULL DEFAULT 0
);

CREATE INDEX idx_failed_jobs_queue_failed ON failed_jobs ("queue", "failed_at");
