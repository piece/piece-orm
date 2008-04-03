-- $Id$

CREATE TABLE employees (
  id int (11) NOT NULL AUTO_INCREMENT,
  first_name varchar (255) NOT NULL,
  last_name varchar (255) NOT NULL,
  note varchar (255),
  departments_id int (11),
  created_at datetime NOT NULL,
  updated_at timestamp,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
