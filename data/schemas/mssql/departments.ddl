-- $Id$

CREATE TABLE departments (
  id int IDENTITY (1,1) NOT NULL,
  name varchar (255) COLLATE Japanese_CI_AS NOT NULL,
  created_at datetime NOT NULL CONSTRAINT DF_departments_created_at DEFAULT (getdate()),
  updated_at datetime NOT NULL CONSTRAINT DF_departments_updated_at DEFAULT (getdate()),
  CONSTRAINT PK_departments PRIMARY KEY CLUSTERED (id ASC) WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
)

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
