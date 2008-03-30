-- $Id$

CREATE TABLE nonprimarykeys (
  member_id int NOT NULL,
  service_id int NOT NULL,
  point int NOT NULL CONSTRAINT DF_nonprimarykeys_point DEFAULT ((0)),
  created_at datetime NOT NULL CONSTRAINT DF_nonprimarykeys_created_at DEFAULT (getdate()),
  updated_at datetime NOT NULL CONSTRAINT DF_nonprimarykeys_updated_at DEFAULT (getdate()),
  CONSTRAINT IX_nonprimarykeys UNIQUE NONCLUSTERED (member_id ASC, service_id ASC) WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
)

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
