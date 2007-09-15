-- $Id$

CREATE TABLE nonprimarykeys (
  member_id int4 NOT NULL,
  service_id int4 NOT NULL,
  point int4 NOT NULL DEFAULT '0',
  version int4 NOT NULL DEFAULT '0',
  rdate timestamp with time zone NOT NULL DEFAULT current_timestamp,
  mdate timestamp with time zone NOT NULL DEFAULT current_timestamp,
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
