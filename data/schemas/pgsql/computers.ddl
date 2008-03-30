-- $Id$

CREATE TABLE computers (
  id serial,
  name varchar (255) NOT NULL,
  employees_id int4,
  created_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  updated_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (id)
);

ALTER TABLE computers OWNER TO piece;

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
