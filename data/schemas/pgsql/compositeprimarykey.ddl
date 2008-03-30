-- $Id$

CREATE TABLE compositeprimarykey (
  album varchar (255) NOT NULL,
  artist varchar (255) NOT NULL,
  track int4 NOT NULL,
  song varchar (255) NOT NULL,
  PRIMARY KEY (album, artist, track)
);

ALTER TABLE compositeprimarykey OWNER TO piece;

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
