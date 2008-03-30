-- $Id$

CREATE TABLE employees_emails (
  id serial,
  employees_id int4 NOT NULL,
  emails_id int4 NOT NULL,
  created_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  updated_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (id)
);

ALTER TABLE employees_emails OWNER TO piece;

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
