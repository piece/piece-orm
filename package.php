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
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://piece-framework.com/piece-orm/
 * @see        PEAR_PackageFileManager2
 * @since      File available since Release 0.1.0
 */

require_once 'PEAR/PackageFileManager2.php';

PEAR::staticPushErrorHandling(PEAR_ERROR_CALLBACK, create_function('$error', 'var_dump($error); exit();'));

$version = '0.2.0';
$apiVersion = '0.2.0';
$apiStability = 'beta';
$releaseStability = 'beta';
$notes = 'This is the first beta release of Piece_ORM.

What\'s New in Piece_ORM 0.2.0

* Relationships: Many-to-Many, One-to-Many, Many-to-One, and One-to-One relationships are supported on object loading/saving.
* Simple configuration: Piece_ORM::configure() requires only three arguments.
* Limit and offset: LIMIT and OFFSET are supported by Piece_ORM_Mapper_XXX::setLimit().
* Sorting orders: ORDER BY clause is supported by Piece_ORM_Mapper_XXX::addOrder().
* Object creation: Piece_ORM::createObject() and Piece_ORM_Mapper_XXX::createObject() can be used to create an object from metadata.
* Object conversion: Piece_ORM::dressObject() can be used to convert an object into a specified object.

See the following release notes for details.

Enhancements
============ 

Mappers:

- Added a feature so as to throw an exception if detecting problem when building a query. (Ticket #9)
- Changed the method naming convention to not require "By" for findXXX/findAllXXX methods like "findByXXX", "findAllByXXX".
- Added support for relationships.
- Added support for LIMIT and OFFSET with setLimit(). (Ticket #14)
- Added support ORDER BY clause with addOrder(). (Ticket #21)
- Added support for Identity Map. (Ticket #23)
- Added createObject() to create an object from metadata.
- Moved _executeQuery() to public.
- Added findWithQuery() to find an object with a query.
- Added method name validation.
- Changed delete() so that its argument can only be specified an object.
- Added createObject() to create an object from metadata.
- Changed behavior for creating/getting mapper objects so that the factory sets the current database handle to a mapper object every time it is called.
- Removed the method element.

Entry Point:

- Removed a Piece_ORM_Config object from the arguments of configure().
- Added getConfiguration() to get the Piece_ORM_Config object after calling configure().
- Updated getMapper()/getConfiguration() so as to return null if calling their methods before calling configure().
- Removed two the cache directories for mappers/metadata from the arguments of configure(). Now, the caches of mappers/metadata are stored in the same directory where a Piece_ORM_Config object is cached in.
- Updated configure() so as to set the current database explicitly.
- Added setDatabase() for setting a database as the current database.
- Added createObject() to create an object from metadata.
- Added dressObject() to convert an object into a specified object.

Metadata:

- Added hasField() for checking whether a table has the given field.

Kernel:

- Removed the default value from getDSN()/getOptions(). (Piece_ORM_Config)
- Changed the way of configuration. Now setDSN(), setOptions(), and setDirectorySuffix() methods are used for configuration instead of addConfiguration(). (Piece_ORM_Config)
- Removed addConfiguration(). (Piece_ORM_Config)
- Added getDefaultDatabase() for getting the default database. (Piece_ORM_Config)
- Adjusted to the new way of configuration. (Piece_ORM_Config_Factory)
- Added setMapperConfigDirectory() for setting the configuration directory for the mapper configuration. (Piece_ORM_Context)
- Updated setDatabase() so as to set the configuration directory with a directory suffix for the mapper configuration. (Piece_ORM_Context)
- Added an error code PIECE_ORM_ERROR_INVALID_CONFIGURATION. (Piece_ORM_Error)
- Added support for PostgreSQL "timestamp with time zone".

Defect Fixes
============ 

Mappers:

- Fixed a problem that a built-in query is overwritten if only the relationship element is given for a built-in method.
- Fixed a problem that a literal as a criterion cannot use with user-defined method.';

$package = new PEAR_PackageFileManager2();
$package->setOptions(array('filelistgenerator' => 'svn',
                           'changelogoldtonew' => false,
                           'simpleoutput'      => true,
                           'baseinstalldir'    => '/',
                           'packagefile'       => 'package2.xml',
                           'packagedirectory'  => '.',
                           'dir_roles'         => array('data' => 'data',
                                                        'tests' => 'test',
                                                        'docs' => 'doc'))
                     );

$package->setPackage('Piece_ORM');
$package->setPackageType('php');
$package->setSummary('An object-relational mapping framework for PHP');
$package->setDescription('Piece_ORM is an object-relational mapping framework for PHP.

Piece_ORM is a framework against the background of Data Mapper.
A mapper is automatically generated from a configuration file and the metadata of a table.
Piece_ORM uses the stdClass as a domain object for a mapper, and uses a stdClass object as criteria.');
$package->setChannel('pear.piece-framework.com');
$package->setLicense('BSD License (revised)',
                     'http://www.opensource.org/licenses/bsd-license.php'
                     );
$package->setAPIVersion($apiVersion);
$package->setAPIStability($apiStability);
$package->setReleaseVersion($version);
$package->setReleaseStability($releaseStability);
$package->setNotes($notes);
$package->setPhpDep('4.3.0');
$package->setPearinstallerDep('1.4.3');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.3.0');
$package->addPackageDepWithChannel('required', 'Cache_Lite', 'pear.php.net', '1.7.0');
$package->addPackageDepWithChannel('required', 'PEAR', 'pear.php.net', '1.4.3');
$package->addPackageDepWithChannel('optional', 'Stagehand_TestRunner', 'pear.piece-framework.com', '0.4.0');
$package->addMaintainer('lead', 'iteman', 'KUBO Atsuhiro', 'iteman@users.sourceforge.net');
$package->addMaintainer('developer', 'matsufuji', 'MATSUFUJI Hideharu', 'matsufuji@users.sourceforge.net');
$package->addIgnore(array('package.php', 'package.xml', 'package2.xml'));
$package->addGlobalReplacement('package-info', '@package_version@', 'version');
$package->generateContents();
$package1 = &$package->exportCompatiblePackageFile1();

if (array_key_exists(1, $_SERVER['argv'])
    && $_SERVER['argv'][1] == 'make'
    ) {
    $package->writePackageFile();
    $package1->writePackageFile();
} else {
    $package->debugPackageFile();
    $package1->debugPackageFile();
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
