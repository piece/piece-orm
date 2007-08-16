-- $Id$

CREATE TABLE phone (
  phone_id int IDENTITY(1,1) NOT NULL,
  phone_number varchar(255) COLLATE Japanese_CI_AS NOT NULL,
  version int NOT NULL CONSTRAINT DF_phone_version DEFAULT ((0)),
  rdate datetime NOT NULL CONSTRAINT DF_phone_rdate DEFAULT (getdate()),
  mdate datetime NOT NULL CONSTRAINT DF_phone_mdate DEFAULT (getdate()),
  CONSTRAINT PK_phone PRIMARY KEY CLUSTERED (phone_id ASC) WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
)

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
