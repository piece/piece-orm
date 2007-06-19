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
 * @see        PEAR_PackageFileManager2
 * @since      File available since Release 0.1.0
 */

require_once 'PEAR/PackageFileManager2.php';

PEAR::staticPushErrorHandling(PEAR_ERROR_CALLBACK, create_function('$error', 'var_dump($error); exit();'));

$version = '0.4.0';
$apiVersion = '0.3.0';
$apiStability = 'beta';
$releaseStability = 'beta';
$notes = 'A new release of Piece_ORM is now available.

What\'s New in Piece_ORM 0.4.0

* Microsoft SQL Server Support: Microsoft SQL Server is supported.
* A Defect Fix: A defect that getCount() could not work with findAll() is fixed.

See the following release notes for details.

Enhancements
============ 

Mappers:

- Changed a portion of the query building process so as to quote based on only real values instead of metadata. (Piece_ORM_Mapper_Common)
- Added support for Microsoft SQL Server. (Ticket #38)

Kernel:

- Changed factory() to throw an exception if the configuration directory or the configuration file not found or the configuration file is not readable. (Ticket #37) (Piece_ORM_Config_Factory)

Defect Fixes
============

Mappers:

- Fixed a defect so that getCount() could not work with findAll(). (Piece_ORM_Mapper_Common)';

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

Piece_ORM is a simple framework based on the DataMapper pattern, and features stdClass centered approach.');
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
