-- $Id$

CREATE TABLE nonprimarykeys (
  member_id int(11) NOT NULL,
  service_id int(11) NOT NULL,
  point int(11) NOT NULL DEFAULT '0',
  version int(11) NOT NULL DEFAULT '0',
  rdate datetime NOT NULL,
  mdate timestamp,
  UNIQUE(member_id, service_id)
);

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
