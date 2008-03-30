-- $Id$

CREATE TABLE geometric_types (
  id serial,
  point_field point NOT NULL,
  lseg_field lseg NOT NULL,
  box_field box NOT NULL,
  open_path_field path NOT NULL,
  closed_path_field path NOT NULL,
  polygon_field polygon NOT NULL,
  circle_field circle NOT NULL,
  created_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  updated_at timestamp with time zone NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (id)
);

ALTER TABLE geometric_types OWNER TO piece;

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
