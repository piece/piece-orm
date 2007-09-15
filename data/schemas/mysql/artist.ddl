-- $Id$

CREATE TABLE artist (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  version int(11) NOT NULL DEFAULT '0',
  rdate datetime NOT NULL,
  mdate timestamp,
  PRIMARY KEY(id)
);

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
