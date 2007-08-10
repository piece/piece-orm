<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2007 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 0.1.0
 */

require_once 'PEAR/PackageFileManager2.php';

PEAR::staticPushErrorHandling(PEAR_ERROR_CALLBACK, create_function('$error', 'var_dump($error); exit();'));

$releaseVersion = '0.5.0';
$releaseStability = 'beta';
$apiVersion = '0.3.0';
$apiStability = 'beta';
$notes = 'A new release of Piece_ORM is now available.

What\'s New in Piece_ORM 0.5.0

 * insertXXX()/updateXXX()/deleteXXX(): Any methods for data manipulation can be defined by mapper configuration files.
 * Update and Delete With No Primary Key Values: Primary key values are not required when executing a update or delete query.
 * Static Queries: Executing static queries with any findXXX, findAllXXX, findOneXXX, insertXXX, and updateXXX methods are now supported.
 * Unique Constraint Error Detection: A PIECE_ORM_ERROR_CONSTRAINT exception is thrown when unique constraint error is occurred.
 * Environment Settings: A configuration file, a mapper definition file, and the metadata for a table be always read when the current environment is not production.
 * A few Defect Fixes: A serious defect that caused invalid objects to return when executing findAllXxx() with no primary keys in SQL has been fixed. And other defects have been fixed.

See the following release notes for details.

Enhancements
============ 

- Changed update() so as to restore the previous identity map setting. (Piece_ORM_Mapper_AssociatedObjectPersister_OneToMany, Piece_ORM_Mapper_AssociatedObjectPersister_OneToOne, Piece_ORM_Mapper_Common)
- Added support for insertXXX(), updateXXX() and deleteXXX(). (Ticket #43) (Piece_ORM_Mapper_Common, Piece_ORM_Mapper_Generator, Piece_ORM_Mapper_ObjectPersister)
- Changed code so that the primary key values are not required when executing a update or delete query. (Ticket #46) (Piece_ORM_Mapper_ObjectPersister)
- Changed update() so as to remove the object from the list of the loaded objects only if the primary key is contained in the given object. (Piece_ORM_Mapper_ObjectPersister)
- Added support for executing static queries with any findXXX, findAllXXX, findOneXXX, insertXXX, and updateXXX methods. (Ticket #42) (Piece_ORM_Mapper_Common, Piece_ORM_Mapper_Generator, Piece_ORM_Mapper_ObjectPersister)
- Added code so that a PIECE_ORM_ERROR_CONSTRAINT exception is thrown when unique constraint error is occurred. (Ticket #44)
- Updated code so that a configuration file, a mapper definition file, and the metadata for a table be always read when the current environment is not production. (Ticket #45)

Defect Fixes
============

- Fixed a defect that caused invalid objects to return when executing findAllXxx() with no primary keys in SQL. (Piece_ORM_Mapper_ObjectLoader)
- Fixed the problem that getCount() could not work with addOrder(). (Piece_ORM_Mapper_Common)
- Fixed the regexp for findOne. (Piece_ORM_Mapper_Generator)';

$package = new PEAR_PackageFileManager2();
$package->setOptions(array('filelistgenerator' => 'svn',
                           'changelogoldtonew' => false,
                           'simpleoutput'      => true,
                           'baseinstalldir'    => '/',
                           'packagefile'       => 'package.xml',
                           'packagedirectory'  => '.',
                           'dir_roles'         => array('data' => 'data',
                                                        'tests' => 'test',
                                                        'docs' => 'doc'),
                           'ignore'            => array('package.php', 'package.xml'))
                     );

$package->setPackage('Piece_ORM');
$package->setPackageType('php');
$package->setSummary('An object-relational mapping framework for PHP');
$package->setDescription('Piece_ORM is an object-relational mapping framework for PHP.

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
$package->addPackageDepWithChannel('optional', 'Stagehand_TestRunner', 'pear.piece-framework.com', '0.5.0');
$package->addPackageDepWithChannel('optional', 'PHPUnit', 'pear.phpunit.de', '1.3.2');
$package->addMaintainer('lead', 'iteman', 'KUBO Atsuhiro', 'iteman@users.sourceforge.net');
$package->addMaintainer('developer', 'matsufuji', 'MATSUFUJI Hideharu', 'matsufuji@users.sourceforge.net');
$package->addMaintainer('developer', 'sekky', 'SEKIYAMA Ryusuke', 'sekky@users.sourceforge.net');
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
?>
