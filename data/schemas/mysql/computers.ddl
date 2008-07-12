-- $Id$

CREATE TABLE computers (
  id int (11) NOT NULL AUTO_INCREMENT,
  name varchar (255) NOT NULL,
  employees_id int (11),
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
