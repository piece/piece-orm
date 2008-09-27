<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
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

namespace Piece;

use Piece::ORM;
use Piece::ORM::Metadata::MetadataFactory;
use Piece::ORM::Mapper::MapperFactory;
use Piece::ORMTest::Employee;
use Piece::ORM::Context::Registry;

require_once 'spyc.php5';

// {{{ Piece::ORMTest

/**
 * Some tests for Piece_ORM.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class ORMTest extends ::PHPUnit_Framework_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    protected $backupGlobals = false;

    /**#@-*/

    /**#@+
     * @access private
     */

    private $_cacheDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    public function setUp()
    {
        $this->_cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
    }

    public function tearDown()
    {
        try {
            ORM::clearCache();
        } catch (Exception $e) {
        }

        Registry::clear();
    }

    public function testShouldConfigureWithAGivenConfigurationFile()
    {
        $yaml = ::Spyc::YAMLLoad("{$this->_cacheDirectory}/piece-orm-config.yaml");

        $this->assertEquals(4, count($yaml));

        ORM::configure($this->_cacheDirectory,
                       $this->_cacheDirectory,
                       $this->_cacheDirectory
                       );
        $config = Registry::getContext()->getConfiguration();

        foreach ($yaml as $configuration) {
            $this->assertEquals($configuration['dsn'], $config->getDSN($configuration['name']));
            $this->assertEquals($configuration['options'], $config->getOptions($configuration['name']));
        }

        $this->assertEquals($this->_cacheDirectory,
                            MapperFactory::getConfigDirectory()
                            );
        $this->assertEquals($this->_cacheDirectory,
                            Registry::getContext()->getCacheDirectory()
                            );

        MapperFactory::setConfigDirectory('./foo');
        Registry::getContext()->setCacheDirectory('./bar');

        $this->assertEquals('./foo', MapperFactory::getConfigDirectory());
        $this->assertEquals('./bar', Registry::getContext()->getCacheDirectory());

        Registry::getContext()->restoreCacheDirectory();
    }

    public function testShouldConfigureDynamicallyWithAGivenConfigurationFile()
    {
        $yaml = ::Spyc::YAMLLoad("{$this->_cacheDirectory}/piece-orm-config.yaml");

        $this->assertEquals(4, count($yaml));

        ORM::configure($this->_cacheDirectory,
                       $this->_cacheDirectory,
                       $this->_cacheDirectory
                       );
        $config = ORM::getConfiguration();
        $config->setOptions('database1', array('debug' => 5));
        $config->setDSN('database2', 'pgsql://piece:piece@pieceorm/piece_test');
        $config->setOptions('database2', array('debug' => 0, 'result_buffering' => false));
        $config->setDSN('piece', 'pgsql://piece:piece@pieceorm/piece');
        $config->setOptions('piece', array('debug' => 0, 'result_buffering' => false));
        $config->setDSN('database3', 'pgsql://piece:piece@pieceorm/piece');
        $config->setDSN('database3', 'pgsql://piece:piece@pieceorm/piece');
        $config->setUseMapperNameAsTableName('database3', true);
        $config->setUseMapperNameAsTableName('caseSensitive', false);
        $config->setDirectorySuffix('caseSensitive', 'foo');

        $this->assertEquals($yaml[0]['dsn'], $config->getDSN('database1'));
        $this->assertTrue($yaml[0]['options'] != $config->getOptions('database1'));
        $this->assertEquals(array('debug' => 5), $config->getOptions('database1'));
        $this->assertTrue($yaml[1]['dsn'] != $config->getDSN('database2'));
        $this->assertEquals('pgsql://piece:piece@pieceorm/piece_test', $config->getDSN('database2'));
        $this->assertEquals($yaml[1]['options'], $config->getOptions('database2'));
        $this->assertEquals('pgsql://piece:piece@pieceorm/piece', $config->getDSN('piece'));
        $this->assertEquals(array('debug' => 0, 'result_buffering' => false), $config->getOptions('piece'));
        $this->assertEquals('pgsql://piece:piece@pieceorm/piece', $config->getDSN('database3'));
        $this->assertTrue($config->getUseMapperNameAsTableName('database3'));
        $this->assertFalse($config->getUseMapperNameAsTableName('caseSensitive'));
        $this->assertEquals('foo', $config->getDirectorySuffix('caseSensitive'));
    }

    public function testShouldGetAMapper()
    {
        ORM::configure($this->_cacheDirectory,
                       $this->_cacheDirectory,
                       $this->_cacheDirectory
                       );

        $this->assertType('Piece::ORM::Mapper::Common', ORM::getMapper('Employees'));
    }

    /**
     * @expectedException Piece::ORM::Exception
     */
    public function testShouldRaiseAnExceptionWhenGetmapperIsCalledBeforeCallingConfigure()
    {
        ORM::getMapper('Employees');
    }

    /**
     * @expectedException Piece::ORM::Exception
     */
    public function testShouldRaiseAnExceptionWhenGetconfigurationIsCalledBeforeCallingConfigure()
    {
        ORM::getConfiguration();
    }

    public function testShouldSetADatabase()
    {
        $cacheDirectory =
            dirname(__FILE__) . '/' . basename(__FILE__, '.php') . '/SetDatabase';
        ORM::configure($cacheDirectory, $cacheDirectory, $cacheDirectory);

        $this->assertEquals("$cacheDirectory/database1",
                            MapperFactory::getConfigDirectory()
                            );

        MapperFactory::setConfigDirectory('./foo');
        ORM::setDatabase('database2');

        $this->assertEquals("$cacheDirectory/database2",
                            MapperFactory::getConfigDirectory()
                            );
    }

    public function testShouldCreateAnObjectByAGivenMapper()
    {
        ORM::configure($this->_cacheDirectory,
                       $this->_cacheDirectory,
                       $this->_cacheDirectory
                       );

        $this->assertNotNull(ORM::createObject('Employees'));
    }

    public function testShouldDressAnObject()
    {
        ORM::configure($this->_cacheDirectory,
                       $this->_cacheDirectory,
                       $this->_cacheDirectory
                       );
        $employee = ORM::createObject('Employees');
        $employee->firstName = 'Foo';
        $employee->lastName = 'Bar';
        $employee->object = new stdClass();
        $realEmployee = ORM::dressObject($employee, new Employee());

        $this->assertType('Piece::ORMTest::Employee', $realEmployee);
        $this->assertEquals('Baz', $realEmployee->generatePassword());
        $this->assertEquals('Foo', $realEmployee->firstName);
        $this->assertObjectHasAttribute('object', $realEmployee);
        $this->assertType('stdClass', $realEmployee->object);

        $employee->object->foo = 'bar';

        $this->assertObjectHasAttribute('foo', $realEmployee->object);
        $this->assertEquals('bar', $realEmployee->object->foo);
    }

    /**
     * @expectedException Piece::ORM::Exception
     * @since Method available since Release 0.3.0
     */
    public function testShouldRaiseAnExceptionWhenSetdatabaseIsCalledBeforeCallingConfigure()
    {
        ORM::setDatabase('database2');
    }

    /**
     * @expectedException Piece::ORM::Exception
     * @since Method available since Release 0.3.0
     */
    public function testShouldRaiseAnExceptionWhenCreateobjectIsCalledBeforeCallingConfigure()
    {
        ORM::createObject('Employees');
    }

    /**
     * @since Method available since Release 0.7.0
     */
    public function testShouldTreatDsnByArray()
    {
        ORM::configure($this->_cacheDirectory,
                       $this->_cacheDirectory,
                       $this->_cacheDirectory
                       );
        ORM::setDatabase('database3');

        $this->assertEquals(0, ORM::getMapper('Employees')->findOneWithQuery('SELECT COUNT(*) FROM employees'));
    }

    /**
     * @expectedException Piece::ORM::Exception
     * @since Method available since Release 0.7.0
     */
    public function testShouldRaiseAnExceptionWhenUndefinedDatabaseIsGiven()
    {
        ORM::configure($this->_cacheDirectory,
                       $this->_cacheDirectory,
                       $this->_cacheDirectory
                       );
        ORM::setDatabase('foo');
    }

    /**
     * @since Method available since Release 1.0.0
     */
    public function testShouldUseAMapperNameAsATableNameIfEnabled()
    {
        ORM::configure($this->_cacheDirectory,
                       $this->_cacheDirectory,
                       $this->_cacheDirectory
                       );
        ORM::setDatabase('caseSensitive');
        $mapper = ORM::getMapper('Case_Sensitive');
        $mapper->findAll();

        $this->assertRegexp('/FROM ["\[]?Case_Sensitive["\[]?/',
                            $mapper->getLastQuery()
                            );
    }

    /**
     * @since Method available since Release 1.0.0
     */
    public function testShouldOverwriteTheDirectorySuffixBySetdirectorysuffixMethod()
    {
        $cacheDirectory =
            dirname(__FILE__) . '/' . basename(__FILE__, '.php') . '/SetDatabase';
        ORM::configure($cacheDirectory, $cacheDirectory, $cacheDirectory);
        $config = ORM::getConfiguration();

        $this->assertEquals('database1', $config->getDirectorySuffix('database1'));

        $config->setDirectorySuffix('database1', 'foo');

        $this->assertEquals('foo', $config->getDirectorySuffix('database1'));
    }

    /**
     * @since Method available since Release 2.0.0
     */
    public function testShouldRestoreThePreviousDatabaseSettings()
    {
        $cacheDirectory =
            dirname(__FILE__) . '/' . basename(__FILE__, '.php') . '/SetDatabase';
        ORM::configure($cacheDirectory, $cacheDirectory, $cacheDirectory);

        $this->assertEquals('database1', Registry::getContext()->getDatabase());
        $this->assertEquals("$cacheDirectory/database1",
                            MapperFactory::getConfigDirectory()
                            );

        ORM::setDatabase('database2');

        $this->assertEquals('database2', Registry::getContext()->getDatabase());
        $this->assertEquals("$cacheDirectory/database2",
                            MapperFactory::getConfigDirectory()
                            );

        ORM::restoreDatabase();

        $this->assertEquals('database1', Registry::getContext()->getDatabase());
        $this->assertEquals("$cacheDirectory/database1",
                            MapperFactory::getConfigDirectory()
                            );
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    // }}}
}

// }}}

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
