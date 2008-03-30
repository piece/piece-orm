-- $Id$

CREATE TABLE unusualname12 (
  id serial,
  name varchar (255) NOT NULL,
  PRIMARY KEY (id)
);

ALTER TABLE unusualname12 OWNER TO piece;

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
