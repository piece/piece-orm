-- $Id$

CREATE TABLE person (
  id int IDENTITY(1,1) NOT NULL,
  first_name varchar(255) COLLATE Japanese_CI_AS NOT NULL,
  last_name varchar(255) COLLATE Japanese_CI_AS NOT NULL,
  service_id int NOT NULL,
  version int NOT NULL CONSTRAINT DF_person_version DEFAULT ((0)),
  rdate datetime NOT NULL CONSTRAINT DF_person_rdate DEFAULT (getdate()),
  mdate datetime NOT NULL CONSTRAINT DF_person_mdate DEFAULT (getdate()),
  CONSTRAINT PK_person PRIMARY KEY CLUSTERED (id ASC) WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
)

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
