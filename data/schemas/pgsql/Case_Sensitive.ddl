-- $Id$

CREATE TABLE "Case_Sensitive" (
  id serial,
  first_name varchar (255) NOT NULL,
  last_name varchar (255) NOT NULL,
  note varchar (255),
  departments_id int4,
  created_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  updated_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  lock_version int4 NOT NULL DEFAULT '0', 
  PRIMARY KEY (id)
);

ALTER TABLE "Case_Sensitive" OWNER TO piece;

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
