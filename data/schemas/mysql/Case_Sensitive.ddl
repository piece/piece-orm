-- $Id$

CREATE TABLE Case_Sensitive (
  ID int (11) NOT NULL AUTO_INCREMENT,
  FIRST_NAME varchar (255) NOT NULL,
  LAST_NAME varchar (255) NOT NULL,
  NOTE varchar (255),
  DEPARTMENTS_ID int (11),
  CREATED_AT datetime NOT NULL,
  UPDATED_AT timestamp,
  LOCK_VERSION int NOT NULL DEFAULT '0', 
  PRIMARY KEY (ID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
 * Local Variables:
 * mode: sql
 * coding: iso-8859-1
 * tab-width: 2
 * indent-tabs-mode: nil
 * End:
 */
