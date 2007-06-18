-- $Id$

CREATE TABLE employee_skill (
  id int(11) NOT NULL AUTO_INCREMENT,
  employee_id int(11) NOT NULL,
  skill_id int(11) NOT NULL,
  version int4 NOT NULL DEFAULT '0',
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
