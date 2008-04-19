-- $Id$

CREATE TABLE employees (
  id int IDENTITY (1,1) NOT NULL,
  first_name varchar (255) COLLATE Japanese_CI_AS NOT NULL,
  last_name varchar (255) COLLATE Japanese_CI_AS NOT NULL,
  note varchar (255) NULL,
  departments_id int NULL,
  created_at datetime NOT NULL CONSTRAINT DF_employees_created_at DEFAULT (getdate()),
  updated_at datetime NOT NULL CONSTRAINT DF_employees_updated_at DEFAULT (getdate()),
  lock_version int NOT NULL CONSTRAINT DF_employees_lock_version DEFAULT ((0)),
  CONSTRAINT PK_employees PRIMARY KEY CLUSTERED (id ASC) WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
)

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
