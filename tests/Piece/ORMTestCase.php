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
 * @see        Piece_ORM
 * @since      File available since Release 0.1.0
 */

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
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
 * @see        Piece_ORM
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
        Piece_ORM_Error::pushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
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
        Piece_ORM_Error::popCallback();
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

        $this->assertEquals($this->_cacheDirectory, Piece_ORM_Mapper_Factory::setConfigDirectory('./foo'));
        $this->assertEquals($this->_cacheDirectory, Piece_ORM_Mapper_Factory::setCacheDirectory('./bar'));
        $this->assertEquals($this->_cacheDirectory, Piece_ORM_Metadata_Factory::setCacheDirectory('./baz'));
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
        $config->setDSN('database1', 'pgsql://piece:piece@localhost/piece');
        $config->setOptions('database1', array('debug' => 5));
        $config->setDSN('database2', 'pgsql://piece:piece@localhost/piece_test');
        $config->setOptions('database2', array('debug' => 0, 'result_buffering' => false));
        $config->setDSN('piece', 'pgsql://piece:piece@localhost/piece');
        $config->setOptions('piece', array('debug' => 0, 'result_buffering' => false));

        $this->assertEquals($yaml[0]['dsn'], $config->getDSN('database1'));
        $this->assertTrue($yaml[0]['options'] != $config->getOptions('database1'));
        $this->assertEquals(array('debug' => 5), $config->getOptions('database1'));
        $this->assertTrue($yaml[1]['dsn'] != $config->getDSN('database2'));
        $this->assertEquals('pgsql://piece:piece@localhost/piece_test', $config->getDSN('database2'));
        $this->assertEquals($yaml[1]['options'], $config->getOptions('database2'));
        $this->assertEquals('pgsql://piece:piece@localhost/piece', $config->getDSN('piece'));
        $this->assertEquals(array('debug' => 0, 'result_buffering' => false), $config->getOptions('piece'));
    }

    function testGetMapper()
    {
        Piece_ORM::configure($this->_cacheDirectory,
                             $this->_cacheDirectory,
                             $this->_cacheDirectory
                             );
        $mapper = &Piece_ORM::getMapper('Person');

        $this->assertTrue(is_subclass_of($mapper, 'Piece_ORM_Mapper_Common'));
    }

    function testGetMapperBeforeCallingConfigure()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $mapper = &Piece_ORM::getMapper('Person');

        $this->assertNull($mapper);
        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_INVALID_OPERATION, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testGetConfigurationBeforeCallingConfigure()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $config = &Piece_ORM::getConfiguration();

        $this->assertNull($config);
        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_INVALID_OPERATION, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testSetDatabase()
    {
        $cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php') . '/SetDatabase';
        Piece_ORM::configure($cacheDirectory,
                             $cacheDirectory,
                             $cacheDirectory
                             );

        $this->assertEquals("$cacheDirectory/database1", Piece_ORM_Mapper_Factory::setConfigDirectory('./foo'));

        Piece_ORM::setDatabase('database2');

        $this->assertEquals("$cacheDirectory/database2", Piece_ORM_Mapper_Factory::setConfigDirectory('./foo'));

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
        $person = &Piece_ORM::createObject('Person');

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($person)));
        $this->assertEquals(7, count(array_keys((array)($person))));
        $this->assertTrue(array_key_exists('id', $person));
        $this->assertTrue(array_key_exists('firstName', $person));
        $this->assertTrue(array_key_exists('lastName', $person));
        $this->assertTrue(array_key_exists('serviceId', $person));
        $this->assertTrue(array_key_exists('version', $person));
        $this->assertTrue(array_key_exists('rdate', $person));
        $this->assertTrue(array_key_exists('mdate', $person));
    }

    function testDressObject()
    {
        Piece_ORM::configure($this->_cacheDirectory,
                             $this->_cacheDirectory,
                             $this->_cacheDirectory
                             );
        $person = &Piece_ORM::createObject('Person');
        $person->firstName = 'Foo';
        $person->lastName = 'Bar';
        $person->object = &new stdClass();
        $realPerson = &Piece_ORM::dressObject($person, new Piece_ORMTestCase_Person());

        $this->assertEquals(strtolower('Piece_ORMTestCase_Person'), strtolower(get_class($realPerson)));
        $this->assertTrue(method_exists($realPerson, 'generatePassword'));
        $this->assertEquals('Foo', $realPerson->firstName);
        $this->assertTrue(array_key_exists('object', $realPerson));
        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($realPerson->object)));

        $person->object->foo = 'bar';

        $this->assertTrue(array_key_exists('foo', $realPerson->object));
        $this->assertEquals('bar', $realPerson->object->foo);
    }

    function testSetDatabaseBeforeCallingConfigure()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        Piece_ORM::setDatabase('database2');

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_INVALID_OPERATION, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testCreateObjectBeforeCallingConfigure()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $person = &Piece_ORM::createObject('Person');

        $this->assertNull($person);
        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_INVALID_OPERATION, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    // }}}
}

// }}}
// {{{ Piece_ORMTestCase_Person

class Piece_ORMTestCase_Person
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
?>
