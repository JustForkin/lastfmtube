DROP TABLE IF EXISTS trackplay
;

CREATE TABLE trackplay (
  trackid     INTEGER PRIMARY KEY,
  artist      VARCHAR(500) NOT NULL,
  title       VARCHAR(500) NOT NULL,
  playcount   INTEGER      NOT NULL DEFAULT 0,
  lastplayed  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  lastplay_ip VARCHAR(50)  NULL,
  url         VARCHAR(500) NULL,

  CHECK (playcount >= 0)
)
;


DROP TABLE IF EXISTS lfmuserplay
;

CREATE TABLE lfmuserplay (
  lfmuser    VARCHAR(250) NOT NULL,
  lastplayed TIMESTAMP    NOT NULL,
  playcount  INTEGER      NOT NULL,
  PRIMARY KEY (lfmuser)
)
;

DROP TABLE IF EXISTS fimport
;

CREATE TABLE fimport (
  fname  VARCHAR(50),
  shasum VARCHAR(50),
  PRIMARY KEY (fname)
)
;


DROP TABLE IF EXISTS replacement
;

CREATE TABLE replacement (
  repltyp VARCHAR(10) DEFAULT ('TITLE'),
  orig    VARCHAR(500),
  repl    VARCHAR(500),

  PRIMARY KEY (repltyp, orig),
  CHECK (repltyp IN ('ARTIST', 'TITLE'))
)
;


DROP VIEW IF EXISTS v_trackplay
;

CREATE VIEW v_trackplay AS
  WITH artist_vars AS (
      SELECT orig, repl
        FROM replacement
        WHERE repltyp = 'ARTIST'
        ORDER BY LENGTH(orig) DESC
  ), title_vars AS (
      SELECT orig, repl
        FROM replacement
        WHERE repltyp = 'TITLE'
        ORDER BY LENGTH(orig) DESC
  )
  SELECT COALESCE(
           TRIM(REPLACE(mc.artist, av.orig, av.repl)),
           TRIM(artist))  AS artist,
         COALESCE(
           TRIM(REPLACE(mc.title, tv.orig, tv.repl)),
           TRIM(title))   AS title,
         SUM(playcount)   AS playcount,
         MAX(lastplayed)  AS lastplayed,
         MAX(lastplay_ip) AS lastplay_ip,
         MAX(url)         AS url
    FROM trackplay mc
           LEFT JOIN artist_vars av ON mc.artist LIKE "%" || av.orig || "%"
           LEFT JOIN title_vars tv ON mc.title LIKE "%" || tv.orig || "%"
    GROUP BY COALESCE(
               TRIM(REPLACE(mc.artist, av.orig, av.repl)),
               TRIM(artist)),
             COALESCE(
               TRIM(REPLACE(mc.title, tv.orig, tv.repl)),
               TRIM(title))
;
COMMIT
;