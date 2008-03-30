-- $Id$

CREATE TABLE employees_emails (
  id int (11) NOT NULL AUTO_INCREMENT,
  employees_id int (11) NOT NULL,
  emails_id int (11) NOT NULL,
  created_at datetime NOT NULL,
  updated_at timestamp,
  PRIMARY KEY (id)
);

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
