-- $Id$

CREATE TABLE employees_skills (
  id int IDENTITY (1,1) NOT NULL,
  employees_id int NOT NULL,
  skills_id int NOT NULL,
  created_at datetime NOT NULL CONSTRAINT DF_employees_skills_created_at DEFAULT (getdate()),
  updated_at datetime NOT NULL CONSTRAINT DF_employees_skills_updated_at DEFAULT (getdate()),
  CONSTRAINT PK_employees_skills PRIMARY KEY CLUSTERED (id ASC) WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
)

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
