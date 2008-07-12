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

require_once realpath(dirname(__FILE__) . '/../prepare.php');
require_once 'PHPUnit.php';
require_once 'Piece/ORM.php';
require_once 'Piece/ORM/Mapper/Factory.php';
require_once 'Piece/ORM/Error.php';
require_once 'Piece/ORM/Metadata/Factory.php';
require_once 'Piece/ORM/Context.php';
require_once 'Cache/Lite.php';
require_once 'Piece/ORM/Config.php';

if (version_compare(phpversion(), '5.0.0', '<')) {
    require_once 'spyc.php';
} else {
    require_once 'spyc.php5';
}

// {{{ Piece_ORMTestCase

/**
 * TestCase for Piece_ORM
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_ORMTestCase extends PHPUnit_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_cacheDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        $this->_cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
    }

    function tearDown()
    {
        $GLOBALS['PIECE_ORM_Configured'] = false;
        Piece_ORM_Metadata_Factory::clearInstances();
        Piece_ORM_Mapper_Factory::clearInstances();
        Piece_ORM_Context::clear();
        $cache = &new Cache_Lite(array('cacheDir' => "{$this->_cacheDirectory}/",
                                       'automaticSerialization' => true,
                                       'errorHandlingAPIBreak' => true)
                                 );
        $cache->clean();
        Piece_ORM_Error::clearErrors();
    }

    function testConfigure()
    {
        $yaml = Spyc::YAMLLoad("{$this->_cacheDirectory}/piece-orm-config.yaml");

        $this->assertTrue(count($yaml));

        Piece_ORM::configure($this->_cacheDirectory,
                             $this->_cacheDirectory,
                             $this->_cacheDirectory
                             );
        $context = &Piece_ORM_Context::singleton();
        $config = &$context->getConfiguration();

        foreach ($yaml as $configuration) {
            $this->assertEquals($configuration['dsn'], $config->getDSN($configuration['name']));
            $this->assertEquals($configuration['options'], $config->getOptions($configuration['name']));
        }

        $this->assertEquals($this->_cacheDirectory, $GLOBALS['PIECE_ORM_Mapper_ConfigDirectory']);
        $this->assertEquals($this->_cacheDirectory, $GLOBALS['PIECE_ORM_Mapper_CacheDirectory']);
        $this->assertEquals($this->_cacheDirectory, $GLOBALS['PIECE_ORM_Metadata_CacheDirectory']);

        Piece_ORM_Mapper_Factory::setConfigDirectory('./foo');
        Piece_ORM_Mapper_Factory::setCacheDirectory('./bar');
        Piece_ORM_Metadata_Factory::setCacheDirectory('./baz');

        $this->assertEquals('./foo', $GLOBALS['PIECE_ORM_Mapper_ConfigDirectory']);
        $this->assertEquals('./bar', $GLOBALS['PIECE_ORM_Mapper_CacheDirectory']);
        $this->assertEquals('./baz', $GLOBALS['PIECE_ORM_Metadata_CacheDirectory']);
    }

    function testDynamicConfiguration()
    {
        $yaml = Spyc::YAMLLoad("{$this->_cacheDirectory}/piece-orm-config.yaml");

        $this->assertTrue(count($yaml));

        Piece_ORM::configure($this->_cacheDirectory,
                             $this->_cacheDirectory,
                             $this->_cacheDirectory
                             );
        $config = &Piece_ORM::getConfiguration();
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

    function testGetMapper()
    {
        Piece_ORM::configure($this->_cacheDirectory,
                             $this->_cacheDirectory,
                             $this->_cacheDirectory
                             );
        $mapper = &Piece_ORM::getMapper('Employees');

        $this->assertTrue(is_subclass_of($mapper, 'Piece_ORM_Mapper_Common'));
    }

    function testGetMapperBeforeCallingConfigure()
    {
        Piece_ORM_Error::disableCallback();
        $mapper = &Piece_ORM::getMapper('Employees');
        Piece_ORM_Error::enableCallback();

        $this->assertNull($mapper);
        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_INVALID_OPERATION, $error['code']);
    }

    function testGetConfigurationBeforeCallingConfigure()
    {
        Piece_ORM_Error::disableCallback();
        $config = &Piece_ORM::getConfiguration();
        Piece_ORM_Error::enableCallback();

        $this->assertNull($config);
        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_INVALID_OPERATION, $error['code']);
    }

    function testSetDatabase()
    {
        $cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php') . '/SetDatabase';
        Piece_ORM::configure($cacheDirectory,
                             $cacheDirectory,
                             $cacheDirectory
                             );

        $this->assertEquals("$cacheDirectory/database1", $GLOBALS['PIECE_ORM_Mapper_ConfigDirectory']);

        Piece_ORM_Mapper_Factory::setConfigDirectory('./foo');
        Piece_ORM::setDatabase('database2');

        $this->assertEquals("$cacheDirectory/database2", $GLOBALS['PIECE_ORM_Mapper_ConfigDirectory']);

        $cache = &new Cache_Lite(array('cacheDir' => "$cacheDirectory/",
                                       'automaticSerialization' => true,
                                       'errorHandlingAPIBreak' => true)
                                 );
        $cache->clean();
    }

    function testCreateObject()
    {
        Piece_ORM::configure($this->_cacheDirectory,
                             $this->_cacheDirectory,
                             $this->_cacheDirectory
                             );

        $this->assertNotNull(Piece_ORM::createObject('Employees'));
    }

    function testDressObject()
    {
        Piece_ORM::configure($this->_cacheDirectory,
                             $this->_cacheDirectory,
                             $this->_cacheDirectory
                             );
        $employee = &Piece_ORM::createObject('Employees');
        $employee->firstName = 'Foo';
        $employee->lastName = 'Bar';
        $employee->object = &new stdClass();
        $realEmployee = &Piece_ORM::dressObject($employee, new Piece_ORMTestCase_Employee());

        $this->assertEquals(strtolower('Piece_ORMTestCase_Employee'), strtolower(get_class($realEmployee)));
        $this->assertTrue(method_exists($realEmployee, 'generatePassword'));
        $this->assertEquals('Foo', $realEmployee->firstName);
        $this->assertTrue(array_key_exists('object', $realEmployee));
        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($realEmployee->object)));

        $employee->object->foo = 'bar';

        $this->assertTrue(array_key_exists('foo', $realEmployee->object));
        $this->assertEquals('bar', $realEmployee->object->foo);
    }

    /**
     * @since Method available since Release 0.3.0
     */
    function testSetDatabaseBeforeCallingConfigure()
    {
        Piece_ORM_Error::disableCallback();
        Piece_ORM::setDatabase('database2');
        Piece_ORM_Error::enableCallback();

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_INVALID_OPERATION, $error['code']);
    }

    /**
     * @since Method available since Release 0.3.0
     */
    function testCreateObjectBeforeCallingConfigure()
    {
        Piece_ORM_Error::disableCallback();
        $employee = &Piece_ORM::createObject('Employees');
        Piece_ORM_Error::enableCallback();

        $this->assertNull($employee);
        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_INVALID_OPERATION, $error['code']);
    }

    /**
     * @since Method available since Release 0.7.0
     */
    function testDSNShouldBeAbleToSetByArray()
    {
        Piece_ORM::configure($this->_cacheDirectory,
                             $this->_cacheDirectory,
                             $this->_cacheDirectory
                             );
        Piece_ORM::setDatabase('database3');
        $mapper = &Piece_ORM::getMapper('Employees');
        $count = $mapper->findOneWithQuery('SELECT COUNT(*) FROM employees');

        $this->assertNotNull($count);
        $this->assertEquals(0, $count);
    }

    /**
     * @since Method available since Release 0.7.0
     */
    function testNotFoundExceptionShouldBeRaisedWhenUndefinedDatabaseIsGiven()
    {
        Piece_ORM::configure($this->_cacheDirectory,
                             $this->_cacheDirectory,
                             $this->_cacheDirectory
                             );
        Piece_ORM_Error::disableCallback();
        Piece_ORM::setDatabase('foo');
        Piece_ORM_Error::enableCallback();

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_NOT_FOUND, $error['code']);
    }

    /**
     * @since Method available since Release 1.0.0
     */
    function testShouldUseAMapperNameAsATableNameIfEnabled()
    {
        Piece_ORM::configure($this->_cacheDirectory,
                             $this->_cacheDirectory,
                             $this->_cacheDirectory
                             );
        Piece_ORM::setDatabase('caseSensitive');
        $mapper = &Piece_ORM::getMapper('Case_Sensitive');
        $mapper->findAll();

        $this->assertTrue(preg_match('/FROM ["\[]?Case_Sensitive["\[]?/', $mapper->getLastQuery()));
    }

    /**
     * @since Method available since Release 1.0.0
     */
    function testShouldOverwriteTheDirectorySuffixBySetdirectorysuffixMethod()
    {
        $cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php') . '/SetDatabase';
        Piece_ORM::configure($cacheDirectory,
                             $cacheDirectory,
                             $cacheDirectory
                             );
        $config = &Piece_ORM::getConfiguration();

        $this->assertEquals('database1', $config->getDirectorySuffix('database1'));

        $config->setDirectorySuffix('database1', 'foo');

        $this->assertEquals('foo', $config->getDirectorySuffix('database1'));

        $cache = &new Cache_Lite(array('cacheDir' => "$cacheDirectory/",
                                       'automaticSerialization' => true,
                                       'errorHandlingAPIBreak' => true)
                                 );
        $cache->clean();
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    // }}}
}

// }}}
// {{{ Piece_ORMTestCase_Employee

class Piece_ORMTestCase_Employee
{
    function generatePassword() {}
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
