-- $Id$

CREATE TABLE employee_skill (
  id int IDENTITY(1,1) NOT NULL,
  employee_id int NOT NULL,
  skill_id int NOT NULL,
  version int NOT NULL CONSTRAINT DF_employee_skill_version DEFAULT ((0)),
  rdate datetime NOT NULL CONSTRAINT DF_employee_skill_rdate DEFAULT (getdate()),
  mdate datetime NOT NULL CONSTRAINT DF_employee_skill_mdate DEFAULT (getdate()),
  CONSTRAINT PK_employee_skill PRIMARY KEY CLUSTERED (id ASC) WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
)

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
