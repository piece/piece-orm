-- $Id$

CREATE TABLE emails (
  emails_id int IDENTITY (1,1) NOT NULL,
  email varchar (255) COLLATE Japanese_CI_AS NOT NULL,
  created_at datetime NOT NULL CONSTRAINT DF_emails_created_at DEFAULT (getdate()),
  updated_at datetime NOT NULL CONSTRAINT DF_emails_updated_at DEFAULT (getdate()),
  CONSTRAINT PK_emails PRIMARY KEY CLUSTERED (emails_id ASC) WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY],
  CONSTRAINT IX_emails UNIQUE NONCLUSTERED (email ASC) WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
)

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
