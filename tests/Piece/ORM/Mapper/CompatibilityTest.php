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
 * @see        Piece_ORM_Mapper_Common
 * @since      File available since Release 0.1.0
 */

require_once 'PHPUnit.php';
require_once 'Piece/ORM/Mapper/Factory.php';
require_once 'Piece/ORM/Error.php';
require_once 'Cache/Lite.php';
require_once 'Piece/ORM/Context.php';
require_once 'Piece/ORM/Config.php';

// {{{ Piece_ORM_Mapper_APITestCase

/**
 * The base class for compatibility test. This class provides test cases to
 * check compatibility for various DB implementations.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
 * @see        Piece_ORM_Mapper_Common
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Mapper_CompatibilityTest extends PHPUnit_TestCase
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
    var $_oldCacheDirectory;
    var $_oldMetadataCacheDirectory;
    var $_dsn;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        preg_match('/^.+_(.+)testcase$/i', get_class($this), $matches);
        $this->_cacheDirectory = dirname(__FILE__) . '/' . ucwords($matches[1]) . 'TestCase';
        $config = &new Piece_ORM_Config();
        $config->addConfiguration('piece',
                                  $this->_dsn,
                                  array('debug' => 2, 'result_buffering' => false)
                                  );
        $context = &Piece_ORM_Context::singleton();
        $context->setConfiguration($config);
        $this->_oldCacheDirectory = Piece_ORM_Mapper_Factory::setConfigDirectory($this->_cacheDirectory);
        Piece_ORM_Mapper_Factory::setCacheDirectory($this->_cacheDirectory);
        $this->_oldMetadataCacheDirectory = Piece_ORM_Metadata_Factory::setCacheDirectory($this->_cacheDirectory);
    }

    function tearDown()
    {
        Piece_ORM_Metadata_Factory::setCacheDirectory($this->_oldMetadataCacheDirectory);
        Piece_ORM_Metadata_Factory::clearInstances();
        Piece_ORM_Mapper_Factory::setCacheDirectory($this->_oldCacheDirectory);
        Piece_ORM_Mapper_Factory::setConfigDirectory($this->_oldCacheDirectory);
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

    function testFind()
    {
        $id = $this->_insert();
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');

        $this->_assertQueryForTestInsert($mapper->getLastQuery());

        $person = &$mapper->findById($id);

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($person)));
        $this->assertTrue(array_key_exists('id', $person));
        $this->assertTrue(array_key_exists('firstName', $person));
        $this->assertTrue(array_key_exists('lastName', $person));
        $this->assertTrue(array_key_exists('version', $person));
        $this->assertTrue(array_key_exists('rdate', $person));
        $this->assertTrue(array_key_exists('mdate', $person));

        $mapper->delete($id);
    }

    function testFindWithNull()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $person = &$mapper->findById(null);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testBuiltinMethods()
    {
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');

        $this->assertTrue(method_exists($mapper, 'findById'));
        $this->assertTrue(method_exists($mapper, 'findByFirstName'));
        $this->assertTrue(method_exists($mapper, 'findByLastName'));
        $this->assertTrue(method_exists($mapper, 'findByVersion'));
        $this->assertFalse(method_exists($mapper, 'findByRdate'));
        $this->assertFalse(method_exists($mapper, 'findByMdate'));
        $this->assertTrue(method_exists($mapper, 'findAll'));
        $this->assertTrue(method_exists($mapper, 'findAllById'));
        $this->assertTrue(method_exists($mapper, 'findAllByFirstName'));
        $this->assertTrue(method_exists($mapper, 'findAllByLastName'));
        $this->assertTrue(method_exists($mapper, 'findAllByVersion'));
        $this->assertFalse(method_exists($mapper, 'findAllByRdate'));
        $this->assertFalse(method_exists($mapper, 'findAllByMdate'));
        $this->assertTrue(method_exists($mapper, 'insert'));
        $this->assertTrue(method_exists($mapper, 'delete'));
        $this->assertTrue(method_exists($mapper, 'update'));
    }

    function testFindWithCriteria()
    {
        $id = $this->_insert();
        $expectedQuery = "SELECT * FROM person WHERE id = $id";
        $criteria = &new stdClass();
        $criteria->id = $id;
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $person = &$mapper->findById($id);
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');

        $this->assertEquals($expectedQuery, $mapper->getLastQuery());

        $personWithCriteria = &$mapper->findById($criteria);

        $this->assertEquals($expectedQuery, $mapper->getLastQuery());

        foreach ($person as $key => $value)
        {
            $this->assertEquals($value, $personWithCriteria->$key);
        }

        $mapper->delete($id);
    }

    function testFindWithUserDefineMethod()
    {
        $id = $this->_insert();
        $criteria = &new stdClass();
        $criteria->id = $id;
        $criteria->serviceId = 3;
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $person = &$mapper->findByIdAndServiceId($criteria);

        $this->assertEquals("SELECT * FROM person WHERE id = $id AND service_id = 3", $mapper->getLastQuery());
        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($person)));
        $this->assertTrue(array_key_exists('id', $person));
        $this->assertTrue(array_key_exists('firstName', $person));
        $this->assertTrue(array_key_exists('lastName', $person));
        $this->assertTrue(array_key_exists('version', $person));
        $this->assertTrue(array_key_exists('rdate', $person));
        $this->assertTrue(array_key_exists('mdate', $person));

        $mapper->delete($id);
    }

    function testOverwriteBuiltinMethod()
    {
        $criteria = &new stdClass();
        $criteria->firstName = 'Atsuhiro';
        $criteria->serviceId = 1;
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $mapper->findByFirstName($criteria);

        $this->assertEquals("SELECT * FROM person WHERE first_name = 'Atsuhiro' AND service_id = 1", $mapper->getLastQuery());
    }

    function testFindAll()
    {
        $this->_insert();
        $this->_insert();
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $people = $mapper->findAll();

        $this->assertEquals('SELECT * FROM person', $mapper->getLastQuery());
        $this->assertTrue(is_array($people));
        $this->assertEquals(2, count($people));

        foreach ($people as $person) {
            $this->assertEquals(strtolower('stdClass'), strtolower(get_class($person)));
            $this->assertTrue(array_key_exists('id', $person));
            $this->assertTrue(array_key_exists('firstName', $person));
            $this->assertTrue(array_key_exists('lastName', $person));
            $this->assertTrue(array_key_exists('version', $person));
            $this->assertTrue(array_key_exists('rdate', $person));
            $this->assertTrue(array_key_exists('mdate', $person));
        }

        foreach ($people as $person) {
            $mapper->delete($person->id);
        }
    }

    function testFindAllWithCriteria()
    {
        $this->_insert();
        $this->_insert();
        $expectedQuery = 'SELECT * FROM person WHERE service_id = 3 AND version >= 0';
        $criteria = &new stdClass();
        $criteria->serviceId = 3;
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $people = $mapper->findAllByServiceId(3);

        $this->assertEquals($expectedQuery, $mapper->getLastQuery());

        $peopleWithCriteria = $mapper->findAllByServiceId($criteria);

        $this->assertEquals($expectedQuery, $mapper->getLastQuery());

        $this->assertTrue(is_array($people));
        $this->assertEquals(2, count($people));

        for ($i = 0; $i < count($people); ++$i) {
            foreach ($people[$i] as $key => $value)
            {
                $this->assertEquals($value, $peopleWithCriteria[$i]->$key);
            }
        }

        foreach ($people as $person) {
            $mapper->delete($person->id);
        }
    }

    function testUpdate()
    {
        $id = $this->_insert();
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $person1 = &$mapper->findById($id);
        $person1->firstName = 'Seven';
        $affectedRows = $mapper->update($person1);

        $this->assertEquals("UPDATE person SET first_name = '{$person1->firstName}', last_name = '{$person1->lastName}', service_id = {$person1->serviceId}, version = {$person1->version}, rdate = '{$person1->rdate}', mdate = '{$person1->mdate}' WHERE id = $id", $mapper->getLastQuery());
        $this->assertEquals(1, $affectedRows);

        $person2 = $mapper->findById($id);

        $this->assertEquals('Seven', $person2->firstName);

        $mapper->delete($id);
    }

    function testDeleteByNull()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $mapper->delete(null);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testDeleteByEmptyString()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $mapper->delete('');

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testDeleteByResource()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $mapper->delete(fopen(__FILE__, 'r'));

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testDeleteByInappropriatePrimaryKey()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $person = &new stdClass();
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $mapper->delete($person);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        $person = &new stdClass();
        $person->id = null;
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $mapper->delete($person);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testUpdateByInappropriatePrimaryKey()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $person = &new stdClass();
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $mapper->update($person);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        $person = &new stdClass();
        $person->id = null;
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $mapper->update($person);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testOverwriteInsertQuery()
    {
        $cacheDirectory = "{$this->_cacheDirectory}/Overwrite";
        Piece_ORM_Mapper_Factory::setConfigDirectory($cacheDirectory);
        Piece_ORM_Mapper_Factory::setCacheDirectory($cacheDirectory);

        $id = $this->_insert();
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');

        $this->_assertQueryForTestOverwriteInsertQuery($mapper->getLastQuery());
        $this->assertNotNull($id);

        $person = &$mapper->findById($id);

        $this->assertEquals('Taro', $person->firstName);
        $this->assertEquals('ITEMAN', $person->lastName);
        $this->assertEquals(1, $person->serviceId);

        $cache = &new Cache_Lite(array('cacheDir' => "$cacheDirectory/",
                                       'automaticSerialization' => true,
                                       'errorHandlingAPIBreak' => true)
                                 );
        $cache->clean();
        $mapper->delete((object)array('id' => $person->id, 'serviceId' => 1));

        $this->assertEquals("DELETE FROM person WHERE id = {$person->id} AND service_id = 1", $mapper->getLastQuery());
    }

    function testOverwriteUpdateQuery()
    {
        $cacheDirectory = "{$this->_cacheDirectory}/Overwrite";
        Piece_ORM_Mapper_Factory::setConfigDirectory($cacheDirectory);
        Piece_ORM_Mapper_Factory::setCacheDirectory($cacheDirectory);

        $id = $this->_insert();
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $person1 = &$mapper->findById($id);
        $person1->firstName = 'Seven';
        $affectedRows = $mapper->update($person1);

        $this->_assertQueryForTestOverwriteUpdateQuery($mapper->getLastQuery(), $person1);
        $this->assertEquals(1, $affectedRows);

        $person2 = $mapper->findById($id);

        $this->assertEquals('Seven', $person2->firstName);

        $cache = &new Cache_Lite(array('cacheDir' => "$cacheDirectory/",
                                       'automaticSerialization' => true,
                                       'errorHandlingAPIBreak' => true)
                                 );
        $cache->clean();
        $mapper->delete((object)array('id' => $person1->id, 'serviceId' => $person1->serviceId));

        $this->assertEquals("DELETE FROM person WHERE id = {$person1->id} AND service_id = {$person1->serviceId}", $mapper->getLastQuery());
    }

    function testReplaceEmptyStringWithNull()
    {
        $subject = &new stdClass();
        $subject->name = 'Foo';
        $subject->description = '';
        $this->_addMissingPropertyForInsert($subject);
        $mapper = &Piece_ORM_Mapper_Factory::factory('Service');
        $id = $mapper->insert($subject);

        $this->_assertQueryForReplaceEmptyStringWithNull($mapper->getLastQuery());

        $mapper->delete($id);
    }

    function testThrowExceptionIfDetectingProblemWhenBuildingQuery()
    {
        $id = $this->_insert();
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $person = &$mapper->findById($id);
        $person->firstName = 'Seven';
        unset($person->lastName);

        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $affectedRows = $mapper->update($person);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_INVOCATION_FAILED, $error['code']);

        Piece_ORM_Error::popCallback();

        $mapper->delete($id);
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    function _insert()
    {
        $subject = &new stdClass();
        $subject->firstName = 'Taro';
        $subject->lastName = 'ITEMAN';
        $subject->serviceId = 3;
        $this->_addMissingPropertyForInsert($subject);
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        return $mapper->insert($subject);
    }

    function _assertQueryForTestInsert($query) {}

    function _addMissingPropertyForInsert($subject) {}

    function _assertQueryForTestOverwriteInsertQuery($query) {}

    function _assertQueryForTestOverwriteUpdateQuery($query, $domainObject) {}

    function _assertQueryForReplaceEmptyStringWithNull($query) {}

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
?>
