-- $Id$

CREATE TABLE Case_Sensitive (
  ID int IDENTITY (1,1) NOT NULL,
  FIRST_NAME varchar (255) COLLATE Japanese_CI_AS NOT NULL,
  LAST_NAME varchar (255) COLLATE Japanese_CI_AS NOT NULL,
  NOTE varchar (255) NULL,
  DEPARTMENTS_ID int NULL,
  CREATED_AT datetime NOT NULL CONSTRAINT DF_Case_Sensitive_created_at DEFAULT (getdate()),
  UPDATED_AT datetime NOT NULL CONSTRAINT DF_Case_Sensitive_updated_at DEFAULT (getdate()),
  LOCK_VERSION int NOT NULL CONSTRAINT DF_Case_Sensitive_lock_version DEFAULT ((0)),
  CONSTRAINT PK_Case_Sensitive PRIMARY KEY CLUSTERED (ID ASC) WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
)

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */

