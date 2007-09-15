-- $Id$

CREATE TABLE nonprimarykeys (
  member_id int NOT NULL,
  service_id int NOT NULL,
  point int NOT NULL CONSTRAINT DF_nonprimarykeys_point DEFAULT ((0)),
  version int NOT NULL CONSTRAINT DF_nonprimarykeys_version DEFAULT ((0)),
  rdate datetime NOT NULL CONSTRAINT DF_nonprimarykeys_rdate DEFAULT (getdate()),
  mdate datetime NOT NULL CONSTRAINT DF_nonprimarykeys_mdate DEFAULT (getdate()),
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
