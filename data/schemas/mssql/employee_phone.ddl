-- $Id$

CREATE TABLE employee_phone (
  id int IDENTITY(1,1) NOT NULL,
  employee_id int NOT NULL,
  phone_id int NOT NULL,
  version int NOT NULL CONSTRAINT DF_employee_phone_version DEFAULT ((0)),
  rdate datetime NOT NULL CONSTRAINT DF_employee_phone_rdate DEFAULT (getdate()),
  mdate datetime NOT NULL CONSTRAINT DF_employee_phone_mdate DEFAULT (getdate()),
  CONSTRAINT PK_employee_phone PRIMARY KEY CLUSTERED (id ASC) WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
)

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
