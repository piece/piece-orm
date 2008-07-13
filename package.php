<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 0.1.0
 */

require_once 'PEAR/PackageFileManager2.php';

PEAR::staticPushErrorHandling(PEAR_ERROR_CALLBACK, create_function('$error', 'var_dump($error); exit();'));

$releaseVersion = '1.1.0';
$releaseStability = 'stable';
$apiVersion = '0.3.0';
$apiStability = 'stable';
$notes = 'A new release of Piece_ORM is now available.

What\'s New in Piece_ORM 1.1.0

 * Improved error handling: The behavior of internal error handling has been changed so as to handle only own and "exception" level errors.
 * Improved Automatic Timestamp: The behavior of insert() has been changed so that the current timestamp to be set to the updated_at field.
 * Many defect fixes: Many defects related to LOB support and the useMapperNameAsTableName option and a few other defects have been fixed.

See the following release notes for details.

Enhancements
============ 

- Changed the behavior of object loading so that a field value of an object is always set null if the field value of the database is NULL.
- Renamed from PIECE_ORM_ERROR_INVOCATION_FAILED to PIECE_ORM_ERROR_CANNOT_INVOKE.
- Changed the behavior of internal error handling so as to handle only own and "exception" level errors.
- Changed the behavior of insert() so that the current timestamp to be set to the updated_at field. (Ticket #99)

Defect Fixes
============

- Fixed a defect that caused an fatal error "PHP Fatal error:  Call to a member function getSource() on a non-object" to be raised when insert()/update() call on an object which contains one or more LOB fields setting null. (Ticket #96) (Piece_ORM_Mapper_Common)
- Fixed a defect that a LOB field value to be null after invoking update() even if the value is not changed. (Ticket #95) (Piece_ORM_Mapper_Common, Piece_ORM_Mapper_LOB)
- Fixed the definition of "options". (Ticket #88) (data/piece-orm.yaml)
- Fixed a defect that caused a LOB value to be damaged after invoking update if the value is not changed on PostgreSQL. (Ticket #97) (Piece_ORM_Mapper_Common)
- Fixed a defect so that all blob values are set to the same value as the value of the last placeholder in a query if multiple blobs are included in the query. (Piece_ORM_Mapper_Common)
- Fixed a defect so that any queries with relationships do not work if the useMapperNameAsTableName option is enabled.
- Fixed a defect that caused an exception to be raised when inserting a record to a table including one or more datetime field which is NOT NULL and *not* has default value if Piece_ORM was used with MDB2_Driver_mysql 1.5.0b1. (Ticket #100) (Piece_ORM_Metadata_Factory)
- Fixed a defect that caused variable names in a default query to be broken if the useMapperNameAsTableName option was enabled. (Ticket #87) (Piece_ORM_Context)';

$package = new PEAR_PackageFileManager2();
$package->setOptions(array('filelistgenerator' => 'file',
                           'changelogoldtonew' => false,
                           'simpleoutput'      => true,
                           'baseinstalldir'    => '/',
                           'packagefile'       => 'package.xml',
                           'packagedirectory'  => '.',
                           'ignore'            => array('package.php'))
                     );

$package->setPackage('Piece_ORM');
$package->setPackageType('php');
$package->setSummary('An object-relational mapping framework');
$package->setDescription('Piece_ORM is an object-relational mapping framework.

Piece_ORM is a simple framework based on the DataMapper pattern, and features stdClass centered approach.');
$package->setChannel('pear.piece-framework.com');
$package->setLicense('BSD License (revised)', 'http://www.opensource.org/licenses/bsd-license.php');
$package->setAPIVersion($apiVersion);
$package->setAPIStability($apiStability);
$package->setReleaseVersion($releaseVersion);
$package->setReleaseStability($releaseStability);
$package->setNotes($notes);
$package->setPhpDep('4.3.0');
$package->setPearinstallerDep('1.4.3');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.3.0');
$package->addPackageDepWithChannel('required', 'Cache_Lite', 'pear.php.net', '1.7.0');
$package->addPackageDepWithChannel('required', 'PEAR', 'pear.php.net', '1.4.3');
$package->addMaintainer('lead', 'iteman', 'KUBO Atsuhiro', 'iteman@users.sourceforge.net');
$package->addGlobalReplacement('package-info', '@package_version@', 'version');
$package->generateContents();

if (array_key_exists(1, $_SERVER['argv']) && $_SERVER['argv'][1] == 'make') {
    $package->writePackageFile();
} else {
    $package->debugPackageFile();
}

exit();

/*
 * Local Variables:
 * mode: php
 * coding: iso-8859-1
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
