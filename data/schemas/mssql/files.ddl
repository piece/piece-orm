-- $Id$

CREATE TABLE files (
  id int IDENTITY (1,1) NOT NULL,
  document_body varchar (MAX) COLLATE Japanese_CI_AS,
  picture varbinary (MAX),
  large_picture varbinary (MAX),
  created_at datetime NOT NULL CONSTRAINT DF_files_created_at DEFAULT (getdate()),
  updated_at datetime NOT NULL CONSTRAINT DF_files_updated_at DEFAULT (getdate()),
  CONSTRAINT PK_files PRIMARY KEY CLUSTERED (id ASC) WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
)

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
