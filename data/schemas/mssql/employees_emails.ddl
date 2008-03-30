-- $Id$

CREATE TABLE employees_emails (
  id int IDENTITY (1,1) NOT NULL,
  employees_id int NOT NULL,
  emails_id int NOT NULL,
  created_at datetime NOT NULL CONSTRAINT DF_employees_emails_created_at DEFAULT (getdate()),
  updated_at datetime NOT NULL CONSTRAINT DF_employees_emails_updated_at DEFAULT (getdate()),
  CONSTRAINT PK_employees_emails PRIMARY KEY CLUSTERED (id ASC) WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
)

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
