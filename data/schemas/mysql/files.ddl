-- $Id$

CREATE TABLE files (
  id int (11) NOT NULL AUTO_INCREMENT,
  document_body longtext,
  picture longblob,
  large_picture longblob,
  created_at datetime NOT NULL,
  updated_at timestamp,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
