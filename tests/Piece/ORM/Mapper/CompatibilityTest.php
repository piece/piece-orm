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
 * @author     MATSUFUJI Hideharu <matsufuji@users.sourceforge.net>
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
 * @author     MATSUFUJI Hideharu <matsufuji@users.sourceforge.net>
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
    var $_targetsForRemoval = array();

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
        $config->setDSN('piece', $this->_dsn);
        $config->setOptions('piece', array('debug' => 2, 'result_buffering' => false));
        $context = &Piece_ORM_Context::singleton();
        $context->setConfiguration($config);
        $context->setDatabase('piece');
        $this->_oldCacheDirectory = Piece_ORM_Mapper_Factory::setConfigDirectory($this->_cacheDirectory);
        Piece_ORM_Mapper_Factory::setCacheDirectory($this->_cacheDirectory);
        $this->_oldMetadataCacheDirectory = Piece_ORM_Metadata_Factory::setCacheDirectory($this->_cacheDirectory);
    }

    function tearDown()
    {
        foreach ($this->_targetsForRemoval as $mapperName => $targets) {
            foreach ($targets as $id) {
                $mapper = &Piece_ORM_Mapper_Factory::factory($mapperName);
                $mapper->delete($id);
            }
        }
        $cache = &new Cache_Lite(array('cacheDir' => "{$this->_cacheDirectory}/",
                                       'automaticSerialization' => true,
                                       'errorHandlingAPIBreak' => true)
                                 );
        $cache->clean();
        Piece_ORM_Metadata_Factory::setCacheDirectory($this->_oldMetadataCacheDirectory);
        Piece_ORM_Metadata_Factory::clearInstances();
        Piece_ORM_Mapper_Factory::setCacheDirectory($this->_oldCacheDirectory);
        Piece_ORM_Mapper_Factory::setConfigDirectory($this->_oldCacheDirectory);
        Piece_ORM_Mapper_Factory::clearInstances();
        Piece_ORM_Context::clear();
        Piece_ORM_Error::clearErrors();
        Piece_ORM_Error::popCallback();
    }

    function testFind()
    {
        $id = $this->_insert();
        $this->_targetsForRemoval['Person'][] = $id;
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
        $this->_targetsForRemoval['Person'][] = $id;
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
    }

    function testFindWithUserDefineMethod()
    {
        $id = $this->_insert();
        $this->_targetsForRemoval['Person'][] = $id;
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
        $id = $this->_insert();
        $this->_targetsForRemoval['Person'][] = $id;
        $id = $this->_insert();
        $this->_targetsForRemoval['Person'][] = $id;
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
    }

    function testFindAllWithCriteria()
    {
        $id = $this->_insert();
        $this->_targetsForRemoval['Person'][] = $id;
        $id = $this->_insert();
        $this->_targetsForRemoval['Person'][] = $id;
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
    }

    function testUpdate()
    {
        $id = $this->_insert();
        $this->_targetsForRemoval['Person'][] = $id;
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $person1 = &$mapper->findById($id);
        $person1->firstName = 'Seven';
        $affectedRows = $mapper->update($person1);

        $this->assertEquals("UPDATE person SET first_name = '{$person1->firstName}', last_name = '{$person1->lastName}', service_id = {$person1->serviceId}, version = {$person1->version}, rdate = '{$person1->rdate}', mdate = '{$person1->mdate}' WHERE id = $id", $mapper->getLastQuery());
        $this->assertEquals(1, $affectedRows);

        $person2 = &$mapper->findById($id);

        $this->assertEquals('Seven', $person2->firstName);
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
        $this->_configure('Overwrite');
        $id = $this->_insert();
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');

        $this->_assertQueryForTestOverwriteInsertQuery($mapper->getLastQuery());
        $this->assertNotNull($id);

        $person = &$mapper->findById($id);

        $this->assertEquals('Taro', $person->firstName);
        $this->assertEquals('ITEMAN', $person->lastName);
        $this->assertEquals(1, $person->serviceId);

        $mapper->delete((object)array('id' => $person->id, 'serviceId' => 1));

        $this->assertEquals("DELETE FROM person WHERE id = {$person->id} AND service_id = 1", $mapper->getLastQuery());
    }

    function testOverwriteUpdateQuery()
    {
        $this->_configure('Overwrite');
        $id = $this->_insert();
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $person1 = &$mapper->findById($id);
        $person1->firstName = 'Seven';
        $affectedRows = $mapper->update($person1);

        $this->_assertQueryForTestOverwriteUpdateQuery($mapper->getLastQuery(), $person1);
        $this->assertEquals(1, $affectedRows);

        $person2 = &$mapper->findById($id);

        $this->assertEquals('Seven', $person2->firstName);

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

    function testManyToManyRelationships()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $employees = $mapper->findAllWithSkills2();

        $this->assertTrue(is_array($employees));
        $this->assertEquals(4, count($employees));
        $this->_assertManyToManyRelationships($employees);
        $this->assertEquals($employees, $mapper->findAllWithSkills1());
    }

    function testManyToManyRelationshipsWithBuiltinMethod()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $employees = $mapper->findAllByName('Qux');

        $this->assertTrue(is_array($employees));
        $this->assertEquals(1, count($employees));
        $this->_assertManyToManyRelationships($employees);
        $this->assertEquals(2, count($employees[0]->skills));
        $this->assertEquals($employees, $mapper->findAllByName((object)array('name' => 'Qux')));
    }

    function testManyToManyRelationshipsWithUserDefinedMethod()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $employees = $mapper->findAllWithSkillsByName('Qux');

        $this->assertTrue(is_array($employees));
        $this->assertEquals(1, count($employees));
        $this->_assertManyToManyRelationships($employees);
        $this->assertEquals(2, count($employees[0]->skills));
        $this->assertEquals($employees, $mapper->findAllWithSkillsByName((object)array('name' => 'Qux')));
    }

    function testManyToManyRelationshipsWithFind()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $employee1 = &$mapper->findWithSkillsByName('Qux');

        $this->assertFalse(is_array($employee1));
        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($employee1)));
        $this->_assertManyToManyRelationships(array($employee1));
        $this->assertEquals(2, count($employee1->skills));

        $employee2 = &$mapper->findWithSkillsByName((object)array('name' => 'Qux'));

        $this->assertEquals(2, count($employee1->skills));
        $this->assertEquals(2, count($employee2->skills));
        $this->assertEquals($employee1, $employee2);
    }

    function testOneToManyRelationships()
    {
        $this->_configure('OneToManyRelationships');
        $this->_setupOneToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Artist');
        $artists = $mapper->findAllWithAlbums2();

        $this->assertTrue(is_array($artists));
        $this->assertEquals(3, count($artists));
        $this->_assertOneToManyRelationships($artists);
        $this->assertEquals($artists, $mapper->findAllWithAlbums1());
    }

    function testManyToOneRelationships()
    {
        $this->_configure('OneToManyRelationships');
        $this->_setupOneToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Album');
        $albums = $mapper->findAllWithArtist2();

        $this->assertTrue(is_array($albums));
        $this->assertEquals(3, count($albums));
        $this->_assertManyToOneRelationships($albums);
        $this->assertEquals($albums, $mapper->findAllWithArtist1());
    }

    function testOneToOneRelationships()
    {
        $this->_configure('OneToOneRelationships');
        $this->_setupOneToOneRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Place');
        $places = $mapper->findAllWithRestaurant2();

        $this->assertTrue(is_array($places));
        $this->assertEquals(3, count($places));
        $this->_assertOneToOneRelationships($places);
        $this->assertEquals($places, $mapper->findAllWithRestaurant1());
    }

    function testLimit()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $mapper->setLimit(2);
        $employees = $mapper->findAllWithSkills1();

        $this->assertTrue(is_array($employees));
        $this->assertEquals(2, count($employees));

        $employees = $mapper->findAllWithSkills1();

        $this->assertEquals(4, count($employees));
    }

    function testOffset()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $mapper->setLimit(2, 2);
        $employees = $mapper->findAllWithSkills1();

        $this->assertTrue(is_array($employees));
        $this->assertEquals(2, count($employees));
        $this->assertEquals('Baz', $employees[0]->name);
        $this->assertEquals('Qux', $employees[1]->name);
    }

    function testLimitFailure()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $mapper->setLimit(-1);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_INVOCATION_FAILED, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testOffsetFailure()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $mapper->setLimit(2, -1);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_INVOCATION_FAILED, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testOrder()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $mapper->addOrder('name');
        $mapper->addOrder('id');
        $employees = $mapper->findAllWithSkills1();

        $this->assertTrue(is_array($employees));
        $this->assertEquals(4, count($employees));

        $this->assertEquals('Bar', $employees[0]->name);
        $this->assertEquals('Baz', $employees[1]->name);
        $this->assertEquals('Foo', $employees[2]->name);
        $this->assertEquals('Qux', $employees[3]->name);

        $mapper->addOrder('name', true);
        $mapper->addOrder('id');

        $employees = $mapper->findAllWithSkills1();

        $this->assertTrue(is_array($employees));
        $this->assertEquals(4, count($employees));

        $this->assertEquals('Bar', $employees[3]->name);
        $this->assertEquals('Baz', $employees[2]->name);
        $this->assertEquals('Foo', $employees[1]->name);
        $this->assertEquals('Qux', $employees[0]->name);
    }

    function testIdentityMap()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $mapper->addOrder('id');
        $employees = $mapper->findAllWithSkills1();

        $this->assertTrue(is_array($employees));
        $this->assertEquals(4, count($employees));

        $this->assertEquals('Bar', $employees[1]->name);
        $this->assertEquals('PHP', $employees[1]->skills[0]->name);
        $this->assertFalse(array_key_exists('foo', $employees[1]->skills[0]));
        $this->assertEquals('Qux', $employees[3]->name);
        $this->assertEquals('PHP', $employees[3]->skills[0]->name);
        $this->assertFalse(array_key_exists('foo', $employees[3]->skills[0]));

        $employees[1]->skills[0]->foo = 'bar';

        $this->assertTrue(array_key_exists('foo', $employees[1]->skills[0]));
        $this->assertTrue(array_key_exists('foo', $employees[3]->skills[0]));
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

    function _setupManyToManyRelationships()
    {
        $employee1 = &new stdClass();
        $employee1->name = 'Foo';
        $this->_addMissingPropertyForInsert($employee1);
        $employeeMapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $employee1Id = $employeeMapper->insert($employee1);
        $this->_targetsForRemoval['Employee'][] = $employee1Id;

        $employee2 = &new stdClass();
        $employee2->name = 'Bar';
        $this->_addMissingPropertyForInsert($employee2);
        $employeeMapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $employee2Id = $employeeMapper->insert($employee2);
        $this->_targetsForRemoval['Employee'][] = $employee2Id;

        $employee3 = &new stdClass();
        $employee3->name = 'Baz';
        $this->_addMissingPropertyForInsert($employee3);
        $employeeMapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $employee3Id = $employeeMapper->insert($employee3);
        $this->_targetsForRemoval['Employee'][] = $employee3Id;

        $employee4 = &new stdClass();
        $employee4->name = 'Qux';
        $this->_addMissingPropertyForInsert($employee4);
        $employeeMapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $employee4Id = $employeeMapper->insert($employee4);
        $this->_targetsForRemoval['Employee'][] = $employee4Id;

        $skill1 = &new stdClass();
        $skill1->name = 'PHP';
        $this->_addMissingPropertyForInsert($skill1);
        $skillMapper = &Piece_ORM_Mapper_Factory::factory('Skill');
        $skill1Id = $skillMapper->insert($skill1);
        $this->_targetsForRemoval['Skill'][] = $skill1Id;

        $skill2 = &new stdClass();
        $skill2->name = 'OOP';
        $this->_addMissingPropertyForInsert($skill2);
        $skillMapper = &Piece_ORM_Mapper_Factory::factory('Skill');
        $skill2Id = $skillMapper->insert($skill2);
        $this->_targetsForRemoval['Skill'][] = $skill2Id;

        $employeeSkill = &new stdClass();
        $employeeSkill->employeeId = $employee2Id;
        $employeeSkill->skillId = $skill1Id;
        $this->_addMissingPropertyForInsert($employeeSkill);
        $employeeSkillMapper = &Piece_ORM_Mapper_Factory::factory('EmployeeSkill');
        $employeeSkillId1 = $employeeSkillMapper->insert($employeeSkill);
        $this->_targetsForRemoval['EmployeeSkill'][] = $employeeSkillId1;

        $employeeSkill = &new stdClass();
        $employeeSkill->employeeId = $employee3Id;
        $employeeSkill->skillId = $skill2Id;
        $this->_addMissingPropertyForInsert($employeeSkill);
        $employeeSkillMapper = &Piece_ORM_Mapper_Factory::factory('EmployeeSkill');
        $employeeSkillId2 = $employeeSkillMapper->insert($employeeSkill);
        $this->_targetsForRemoval['EmployeeSkill'][] = $employeeSkillId2;

        $employeeSkill = &new stdClass();
        $employeeSkill->employeeId = $employee4Id;
        $employeeSkill->skillId = $skill1Id;
        $this->_addMissingPropertyForInsert($employeeSkill);
        $employeeSkillMapper = &Piece_ORM_Mapper_Factory::factory('EmployeeSkill');
        $employeeSkillId3 = $employeeSkillMapper->insert($employeeSkill);
        $this->_targetsForRemoval['EmployeeSkill'][] = $employeeSkillId3;

        $employeeSkill = &new stdClass();
        $employeeSkill->employeeId = $employee4Id;
        $employeeSkill->skillId = $skill2Id;
        $this->_addMissingPropertyForInsert($employeeSkill);
        $employeeSkillMapper = &Piece_ORM_Mapper_Factory::factory('EmployeeSkill');
        $employeeSkillId4 = $employeeSkillMapper->insert($employeeSkill);
        $this->_targetsForRemoval['EmployeeSkill'][] = $employeeSkillId4;
    }

    function _assertManyToManyRelationships($employees)
    {
        foreach ($employees as $employee) {
            foreach (array('id', 'name', 'version', 'rdate', 'mdate', 'skills') as $property) {
                $this->assertTrue(array_key_exists($property, $employee), $property);
            }

            $this->assertTrue(is_array($employee->skills));

            switch ($employee->name) {
            case 'Foo':
                $this->assertEquals(0, count($employee->skills));
                break;
            case 'Bar':
                $this->assertEquals(1, count($employee->skills));
                $this->assertEquals('PHP', $employee->skills[0]->name);
                break;
            case 'Baz':
                $this->assertEquals(1, count($employee->skills));
                $this->assertEquals('OOP', $employee->skills[0]->name);
                break;
            case 'Qux':
                $this->assertEquals(2, count($employee->skills));
                $this->assertEquals('PHP', $employee->skills[0]->name);
                $this->assertEquals('OOP', $employee->skills[1]->name);
                break;
            default:
                $this->fail('Unknown name for Employee.');
            }

            foreach ($employee->skills as $skill) {
                foreach (array('id', 'name', 'version', 'rdate', 'mdate') as $property) {
                    $this->assertTrue(array_key_exists($property, $skill), $property);
                }
            }
        }
    }

    function _configure($cacheDirectory)
    {
        $this->_cacheDirectory = "{$this->_cacheDirectory}/$cacheDirectory";
        Piece_ORM_Mapper_Factory::setConfigDirectory($this->_cacheDirectory);
        Piece_ORM_Mapper_Factory::setCacheDirectory($this->_cacheDirectory);
        Piece_ORM_Metadata_Factory::setCacheDirectory($this->_cacheDirectory);
    }

    function _setupOneToManyRelationships()
    {
        $artist1 = &new stdClass();
        $artist1->name = 'Foo';
        $this->_addMissingPropertyForInsert($artist1);
        $artistMapper = &Piece_ORM_Mapper_Factory::factory('Artist');
        $artist1Id = $artistMapper->insert($artist1);
        $this->_targetsForRemoval['Artist'][] = $artist1Id;

        $artist2 = &new stdClass();
        $artist2->name = 'Bar';
        $this->_addMissingPropertyForInsert($artist2);
        $artistMapper = &Piece_ORM_Mapper_Factory::factory('Artist');
        $artist2Id = $artistMapper->insert($artist2);
        $this->_targetsForRemoval['Artist'][] = $artist2Id;

        $artist3 = &new stdClass();
        $artist3->name = 'Baz';
        $this->_addMissingPropertyForInsert($artist3);
        $artistMapper = &Piece_ORM_Mapper_Factory::factory('Artist');
        $artist3Id = $artistMapper->insert($artist3);
        $this->_targetsForRemoval['Artist'][] = $artist3Id;

        $album1 = &new stdClass();
        $album1->name = 'The first album of the artist2';
        $album1->artistId = $artist2Id;
        $this->_addMissingPropertyForInsert($album1);
        $albumMapper = &Piece_ORM_Mapper_Factory::factory('Album');
        $album1Id = $albumMapper->insert($album1);
        $this->_targetsForRemoval['Album'][] = $album1Id;

        $album2 = &new stdClass();
        $album2->name = 'The first album of the artist3';
        $album2->artistId = $artist3Id;
        $this->_addMissingPropertyForInsert($album2);
        $albumMapper = &Piece_ORM_Mapper_Factory::factory('Album');
        $album2Id = $albumMapper->insert($album2);
        $this->_targetsForRemoval['Album'][] = $album2Id;

        $album3 = &new stdClass();
        $album3->name = 'The second album of the artist3';
        $album3->artistId = $artist3Id;
        $this->_addMissingPropertyForInsert($album3);
        $albumMapper = &Piece_ORM_Mapper_Factory::factory('Album');
        $album3Id = $albumMapper->insert($album3);
        $this->_targetsForRemoval['Album'][] = $album3Id;
    }

    function _assertOneToManyRelationships($artists)
    {
        foreach ($artists as $artist) {
            foreach (array('id', 'name', 'version', 'rdate', 'mdate', 'albums') as $property) {
                $this->assertTrue(array_key_exists($property, $artist), $property);
            }

            $this->assertTrue(is_array($artist->albums));

            switch ($artist->name) {
            case 'Foo':
                $this->assertEquals(0, count($artist->albums));
                break;
            case 'Bar':
                $this->assertEquals(1, count($artist->albums));
                break;
            case 'Baz':
                $this->assertEquals(2, count($artist->albums));
                break;
            default:
                $this->fail('Unknown name for Artist.');
            }

            foreach ($artist->albums as $album) {
                foreach (array('id', 'name', 'version', 'rdate', 'mdate') as $property) {
                    $this->assertTrue(array_key_exists($property, $album), $property);
                }
            }
        }
    }

    function _assertManyToOneRelationships($albums)
    {
        foreach ($albums as $album) {
            foreach (array('id', 'artistId', 'name', 'version', 'rdate', 'mdate', 'artist') as $property) {
                $this->assertTrue(array_key_exists($property, $album), $property);
            }

            $this->assertTrue(strtolower('stdClass'), strtolower(get_class($album->artist)));
            foreach (array('id', 'name', 'version', 'rdate', 'mdate') as $property) {
                $this->assertTrue(array_key_exists($property, $album->artist), $property);
            }
        }
    }

    function _setupOneToOneRelationships()
    {
        $place1 = &new stdClass();
        $place1->name = 'Foo';
        $this->_addMissingPropertyForInsert($place1);
        $placeMapper = &Piece_ORM_Mapper_Factory::factory('Place');
        $place1Id = $placeMapper->insert($place1);
        $this->_targetsForRemoval['Place'][] = $place1Id;

        $place2 = &new stdClass();
        $place2->name = 'Bar';
        $this->_addMissingPropertyForInsert($place2);
        $placeMapper = &Piece_ORM_Mapper_Factory::factory('Place');
        $place2Id = $placeMapper->insert($place2);
        $this->_targetsForRemoval['Place'][] = $place2Id;

        $place3 = &new stdClass();
        $place3->name = 'Baz';
        $this->_addMissingPropertyForInsert($place3);
        $placeMapper = &Piece_ORM_Mapper_Factory::factory('Place');
        $place3Id = $placeMapper->insert($place3);
        $this->_targetsForRemoval['Place'][] = $place3Id;

        $restaurant1 = &new stdClass();
        $restaurant1->name = 'The restaurant on the place2';
        $restaurant1->placeId = $place2Id;
        $this->_addMissingPropertyForInsert($restaurant1);
        $restaurantMapper = &Piece_ORM_Mapper_Factory::factory('Restaurant');
        $restaurant1Id = $restaurantMapper->insert($restaurant1);
        $this->_targetsForRemoval['Restaurant'][] = $restaurant1Id;

        $restaurant2 = &new stdClass();
        $restaurant2->name = 'The restaurant on the place3';
        $restaurant2->placeId = $place3Id;
        $this->_addMissingPropertyForInsert($restaurant2);
        $restaurantMapper = &Piece_ORM_Mapper_Factory::factory('Restaurant');
        $restaurant2Id = $restaurantMapper->insert($restaurant2);
        $this->_targetsForRemoval['Restaurant'][] = $restaurant2Id;
    }

    function _assertOneToOneRelationships($places)
    {
        foreach ($places as $place) {
            foreach (array('id', 'name', 'version', 'rdate', 'mdate', 'restaurant') as $property) {
                $this->assertTrue(array_key_exists($property, $place), $property);
            }

            if (!is_null($place->restaurant)) {
                $this->assertTrue(strtolower('stdClass'), strtolower(get_class($place->restaurant)));
                foreach (array('id', 'name', 'version', 'rdate', 'mdate') as $property) {
                    $this->assertTrue(array_key_exists($property, $place->restaurant), $property);
                }
            } else {
                $this->assertEquals('Foo', $place->name);
            }
        }
    }

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
