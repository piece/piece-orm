-- $Id$

CREATE TABLE nonprimarykeys (
  member_id int (11) NOT NULL,
  service_id int (11) NOT NULL,
  point int (11) NOT NULL DEFAULT '0',
  created_at datetime NOT NULL,
  updated_at timestamp,
  UNIQUE (member_id, service_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
