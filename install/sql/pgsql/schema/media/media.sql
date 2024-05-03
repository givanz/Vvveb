DROP TABLE IF EXISTS media;

DROP SEQUENCE IF EXISTS media_seq;
CREATE SEQUENCE media_seq;
-- SELECT setval('media_seq', 14, true); -- last inserted id by sample data


CREATE TABLE media (
  "media_id" int check ("media_id" > 0) NOT NULL DEFAULT NEXTVAL ('media_seq'),
  "file" varchar(191) NOT NULL,
  "type" varchar(30) NOT NULL default 'image/png',
  "meta" TEXT,
  PRIMARY KEY ("media_id")
);

CREATE INDEX "file_media_id" ON media ("file", "media_id");
