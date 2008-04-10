-- $Id$

CREATE TABLE Case_Sensitive (
  id int IDENTITY (1,1) NOT NULL,
  name varchar (255) COLLATE Japanese_CI_AS NOT NULL,
  CONSTRAINT PK_Case_Sensitive PRIMARY KEY CLUSTERED (id ASC) WITH (PAD_INDEX  = OFF, IGNORE_DUP_KEY = OFF) ON [PRIMARY]
)

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */

