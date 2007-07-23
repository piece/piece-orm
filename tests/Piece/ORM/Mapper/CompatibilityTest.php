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

require_once realpath(dirname(__FILE__) . '/../../../prepare.php');
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
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
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
    var $_tables = array('album',
                         'artist',
                         'department',
                         'employee',
                         'employee_department',
                         'employee_skill',
                         'person',
                         'place',
                         'restaurant',
                         'service',
                         'skill'
                         );
    var $_type;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        $this->_cacheDirectory = dirname(__FILE__) . '/' . ucwords($this->_type) . 'TestCase';
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
        $context = &Piece_ORM_Context::singleton();
        $dbh = &$context->getConnection();
        foreach ($this->_tables as $table) {
            $dbh->exec("TRUNCATE TABLE $table");
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
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
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

        for ($i = 0, $count = count($people); $i < $count; ++$i) {
            foreach ($people[$i] as $key => $value)
            {
                $this->assertEquals($value, $peopleWithCriteria[$i]->$key);
            }
        }
    }

    function testUpdate()
    {
        $id = $this->_insert();
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $person1 = &$mapper->findById($id);
        $person1->firstName = 'Seven';
        $affectedRows = $mapper->update($person1);

        $this->assertEquals(1, $affectedRows);

        $person2 = &$mapper->findById($id);

        $this->assertEquals('Seven', $person2->firstName);

        $person1->foo = 'bar';

        $this->assertTrue(array_key_exists('foo', $person1));
        $this->assertEquals('bar', $person1->foo);
        $this->assertFalse(array_key_exists('foo', $person2));

    }

    function testDeleteByNull()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $subject = null;
        $mapper->delete($subject);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testDeleteByEmptyString()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $subject = '';
        $mapper->delete($subject);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testDeleteByResource()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $subject = fopen(__FILE__, 'r');
        $mapper->delete($subject);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testDeleteByInappropriatePrimaryKey()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $person = &$mapper->createObject();
        $mapper->delete($person);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        $person = &$mapper->createObject();
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

        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $person = &$mapper->createObject();
        $mapper->update($person);

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        $person = &$mapper->createObject();
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

        $this->assertNotNull($id);

        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $person = &$mapper->findById($id);

        $this->assertNotNull($person);
        $this->assertEquals('Taro', $person->firstName);
        $this->assertEquals('ITEMAN', $person->lastName);
        $this->assertEquals(1, $person->serviceId);

        $mapper->delete($person);

        $this->assertNull($mapper->findById($id));
    }

    function testOverwriteUpdateQuery()
    {
        $this->_configure('Overwrite');
        $id = $this->_insert();
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $person1 = &$mapper->findById($id);
        $person1->firstName = 'Seven';
        $affectedRows = $mapper->update($person1);

        $this->assertEquals(1, $affectedRows);

        $person2 = &$mapper->findById($id);

        $this->assertNotNull($person2);
        $this->assertEquals('Seven', $person2->firstName);

        $mapper->delete($person1);

        $this->assertNull($mapper->findById($id));
    }

    function testReplaceEmptyStringWithNull()
    {
        $mapper = &Piece_ORM_Mapper_Factory::factory('Service');
        $subject = &$mapper->createObject();
        $subject->name = 'Foo';
        $subject->description = '';
        $this->_addMissingPropertyForInsert($subject);
        $id = $mapper->insert($subject);

        $service = &$mapper->findById($id);

        $this->assertNotNull($service);
        $this->assertNull($service->description);
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

        $employee = $mapper->findWithDepartmentsByName('Bar');

        $this->assertTrue(array_key_exists('departments', $employee));
        $this->assertTrue(array_key_exists('skills', $employee));
        $this->assertEquals(1, count($employee->departments));
        $this->assertEquals('The Export Department', $employee->departments[0]->name);
        $this->assertEquals($employee, $employees[1]);
    }

    function testMultipleRelationships()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $mapper->addOrder('id');
        $employees = $mapper->findAllWithMultipleRelationships();

        $this->assertTrue(is_array($employees));
        $this->assertEquals(4, count($employees));

        $this->assertEquals('Bar', $employees[1]->name);
        $this->assertEquals('PHP', $employees[1]->skills[0]->name);
        $this->assertEquals('The Export Department', $employees[1]->departments[0]->name);
    }

    function testOrderOnManyToManyRelationships()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $mapper->addOrder('id');
        $employees = $mapper->findAllWithOrderedSkills();

        $this->assertTrue(is_array($employees));
        $this->assertEquals(4, count($employees));

        $this->assertEquals('OOP', $employees[3]->skills[0]->name);
        $this->assertEquals('PHP', $employees[3]->skills[1]->name);
    }

    function testOrderOnOneToManyRelationships()
    {
        $this->_configure('OneToManyRelationships');
        $this->_setupOneToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Artist');
        $mapper->addOrder('id');
        $artists = $mapper->findAllWithOrderedAlbums();

        $this->assertTrue(is_array($artists));
        $this->assertEquals(3, count($artists));

        $this->assertEquals('The second album of the artist3', $artists[2]->albums[0]->name);
        $this->assertEquals('The first album of the artist3', $artists[2]->albums[1]->name);
    }

    function testDelete()
    {
        $id = $this->_insert();
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $person1 = &$mapper->findById($id);

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($person1)));

        $mapper->delete($person1);
        $person2 = &$mapper->findById($id);

        $this->assertNull($person2);
    }

    function testCreateObject()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $employee = &$mapper->createObject();

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($employee)));
        $this->assertEquals(5, count(array_keys((array)($employee))));
        $this->assertTrue(array_key_exists('id', $employee));
        $this->assertTrue(array_key_exists('name', $employee));
        $this->assertTrue(array_key_exists('version', $employee));
        $this->assertTrue(array_key_exists('rdate', $employee));
        $this->assertTrue(array_key_exists('mdate', $employee));
    }

    function testCascadeUpdateOnManyToManyRelationships()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $skillMapper = &Piece_ORM_Mapper_Factory::factory('Skill');
        $skills = $skillMapper->findAll();
        $employeeMapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $foo1 = &$employeeMapper->findByName('Foo');

        $this->assertEquals(0, count($foo1->skills));

        $foo1->skills = $skills;
        $employeeMapper->update($foo1);
        $foo2 = &$employeeMapper->findByName('Foo');

        $this->assertEquals(2, count($foo2->skills));

        $foo1->foo = 'bar';

        $this->assertTrue(array_key_exists('foo', $foo1));
        $this->assertEquals('bar', $foo1->foo);
        $this->assertFalse(array_key_exists('foo', $foo2));
        $this->assertEquals($foo1->departments, $foo2->departments);

        unset($foo1->foo);
        unset($foo1->skills);
        unset($foo2->skills);
        unset($foo1->departments);
        unset($foo2->departments);

        $this->assertEquals($foo1, $foo2);
    }

    function testCascadeUpdateOnOneToManyRelationships()
    {
        $this->_configure('OneToManyRelationships');
        $this->_setupOneToManyRelationships();

        $artistMapper = &Piece_ORM_Mapper_Factory::factory('Artist');
        $baz1 = &$artistMapper->findByName('Baz');

        $this->assertEquals(2, count($baz1->albums));

        $albumMapper = &Piece_ORM_Mapper_Factory::factory('Album');

        $album1 = &$albumMapper->createObject();
        $album1->name = 'The 3rd album of the artist3';
        $baz1->albums[] = &$album1;
        array_shift($baz1->albums);

        $this->assertEquals('The first album of the artist3', $baz1->albums[0]->name);

        $baz1->albums[0]->name = 'The 1st album of the artist3';
        $album2 = &$albumMapper->createObject();
        $album2->id = '-1';
        $album2->name = 'The 4th album of the artist3';
        $baz1->albums[] = &$album2;
        $artistMapper->update($baz1);

        $baz2 = &$artistMapper->findByName('Baz');

        $this->assertEquals(3, count($baz2->albums));
        $this->assertEquals('The 4th album of the artist3', $baz2->albums[0]->name);
        $this->assertEquals('The 3rd album of the artist3', $baz2->albums[1]->name);
        $this->assertEquals('The 1st album of the artist3', $baz2->albums[2]->name);

        $baz1->foo = 'bar';

        $this->assertTrue(array_key_exists('foo', $baz1));
        $this->assertEquals('bar', $baz1->foo);
        $this->assertFalse(array_key_exists('foo', $baz2));

        unset($baz1->albums);
        unset($baz1->foo);
        unset($baz2->albums);

        $this->assertEquals($baz1, $baz2);
    }

    function testCascadeUpdateOnOneToOneRelationships()
    {
        $this->_configure('OneToOneRelationships');
        $this->_setupOneToOneRelationships();

        $placeMapper = &Piece_ORM_Mapper_Factory::factory('Place');
        $foo1 = &$placeMapper->findByName('Foo');

        $this->assertNull($foo1->restaurant);

        $restaurantMapper = &Piece_ORM_Mapper_Factory::factory('Restaurant');

        $restaurant1 = &$restaurantMapper->createObject();
        $restaurant1->name = 'The restaurant on the place Foo.';
        $foo1->restaurant = &$restaurant1;
        $placeMapper->update($foo1);

        $foo2 = &$placeMapper->findByName('Foo');

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($foo2->restaurant)));

        unset($foo1->restaurant);
        unset($foo2->restaurant);

        $this->assertEquals($foo1, $foo2);

        $bar1 = &$placeMapper->findByName('Bar');

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($bar1->restaurant)));

        $bar1->restaurant = null;
        $placeMapper->update($bar1);

        $bar2 = &$placeMapper->findByName('Bar');

        $this->assertNull($bar2->restaurant);

        unset($bar1->restaurant);
        unset($bar2->restaurant);

        $this->assertEquals($bar1, $bar2);

        $baz1 = &$placeMapper->findByName('Baz');

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($baz1->restaurant)));
        $this->assertEquals('The restaurant on the place Baz.', $baz1->restaurant->name);

        $baz1->restaurant->name = 'The restaurant on the place Baz. (updated)';
        $placeMapper->update($baz1);

        $baz2 = &$placeMapper->findByName('Baz');

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($baz2->restaurant)));
        $this->assertEquals('The restaurant on the place Baz. (updated)', $baz2->restaurant->name);

        $baz1->foo = 'bar';

        $this->assertTrue(array_key_exists('foo', $baz1));
        $this->assertEquals('bar', $baz1->foo);
        $this->assertFalse(array_key_exists('foo', $baz2));

        unset($bar1->restaurant);
        unset($bar1->foo);
        unset($bar2->restaurant);

        $this->assertEquals($bar1, $bar2);
    }

    function testCascadeDeleteManyToManyRelationships()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $employeeMapper = &Piece_ORM_Mapper_Factory::factory('Employee');
        $qux = &$employeeMapper->findByName('Qux');
        $employeeSkillMapper = &Piece_ORM_Mapper_Factory::factory('EmployeeSkill');

        $this->assertEquals(2, count($employeeSkillMapper->findAllByEmployeeId($qux->id)));

        $employeeMapper->delete($qux);

        $this->assertEquals(0, count($employeeSkillMapper->findAllByEmployeeId($qux->id)));
    }

    function testCascadeDeleteOnOneToManyRelationships()
    {
        $this->_configure('OneToManyRelationships');
        $this->_setupOneToManyRelationships();

        $artistMapper = &Piece_ORM_Mapper_Factory::factory('Artist');
        $baz = &$artistMapper->findByName('Baz');
        $albumMapper = &Piece_ORM_Mapper_Factory::factory('Album');

        $this->assertEquals(2, count($albumMapper->findAllByArtistId($baz->id)));

        $artistMapper->delete($baz);

        $this->assertEquals(0, count($albumMapper->findAllByArtistId($baz->id)));
    }

    function testCascadeDeleteOnOneToOneRelationships()
    {
        $this->_configure('OneToOneRelationships');
        $this->_setupOneToOneRelationships();

        $placeMapper = &Piece_ORM_Mapper_Factory::factory('Place');
        $baz = &$placeMapper->findByName('Baz');
        $restaurantMapper = &Piece_ORM_Mapper_Factory::factory('Restaurant');

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($restaurantMapper->findByPlaceId($baz->id))));

        $placeMapper->delete($baz);

        $this->assertNull($restaurantMapper->findByPlaceId($baz->id));
    }

    /**
     * @since Method available since Release 0.3.0
     */
    function testGetCount()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');

        $this->assertNull($mapper->getCount());

        $mapper->findAllWithMultipleRelationships();

        $this->assertEquals(4, $mapper->getCount());

        $mapper->setLimit(2);
        $people = $mapper->findAllWithMultipleRelationships();

        $this->assertEquals(2, count($people));
        $this->assertEquals(4, $mapper->getCount());
    }

    /**
     * @since Method available since Release 0.3.0
     */
    function testFindOne()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');

        $this->assertNull($mapper->findOneForNameByName('NonExisting'));

        $mapper->addOrder('id', true);

        $this->assertEquals('Qux', $mapper->findOneForNameByName((object)array('name' => 'Qux')));
        $this->assertEquals(4, $mapper->findOneForCount());
    }

    /**
     * @since Method available since Release 0.4.0
     */
    function testGetCountShouldWorkWithFindAll()
    {
        $this->_configure('ManyToManyRelationships');
        $this->_setupManyToManyRelationships();

        $mapper = &Piece_ORM_Mapper_Factory::factory('Employee');

        $this->assertNull($mapper->getCount());

        $mapper->findAll();

        $this->assertEquals(4, $mapper->getCount());
    }

    /**
     * @since Method available since Release 0.4.1
     */
    function testPHPNULLShouldBeExtractedAsDatabaseNULL()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $mapper = &Piece_ORM_Mapper_Factory::factory('Service');
        $service = &$mapper->createObject();
        $service->name = 'foo';
        $this->_addMissingPropertyForInsert($service);
        $mapper->insert($service);

        $this->assertFalse(Piece_ORM_Error::hasErrors('exception'));

        Piece_ORM_Error::popCallback();
    }

    /**
     * @since Method available since Release 0.5.0
     */
    function testObjectsReturnByFindAllShouldBeCorrectWithNoPrimaryKeysInSQL()
    {
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $subject = &$mapper->createObject();
        $subject->firstName = 'Taro';
        $subject->lastName = 'ITEMAN';
        $subject->serviceId = 1;
        $this->_addMissingPropertyForInsert($subject);
        $mapper->insert($subject);
        $subject = &$mapper->createObject();
        $subject->firstName = 'Taro';
        $subject->lastName = 'ITEMAN';
        $subject->serviceId = 2;
        $this->_addMissingPropertyForInsert($subject);
        $mapper->insert($subject);
        $mapper->addOrder('id');
        $people = $mapper->findAllServiceIds();

        $this->assertEquals(2, count($people));
        $this->assertEquals(1, $people[0]->serviceId);
        $this->assertEquals(2, $people[1]->serviceId);
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    function _insert()
    {
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');
        $subject = &$mapper->createObject();
        $subject->firstName = 'Taro';
        $subject->lastName = 'ITEMAN';
        $subject->serviceId = 3;
        $this->_addMissingPropertyForInsert($subject);
        return $mapper->insert($subject);
    }

    function _addMissingPropertyForInsert($subject) {}

    function _setupManyToManyRelationships()
    {
        $skillMapper = &Piece_ORM_Mapper_Factory::factory('Skill');

        $skill1 = &$skillMapper->createObject();
        $skill1->name = 'PHP';
        $skillMapper->insert($skill1);

        $skill2 = &$skillMapper->createObject();
        $skill2->name = 'OOP';
        $skillMapper->insert($skill2);

        $departmentMapper = &Piece_ORM_Mapper_Factory::factory('Department');

        $department1 = &$departmentMapper->createObject();
        $department1->name = 'The Accounting Department';
        $departmentMapper->insert($department1);

        $department2 = &$departmentMapper->createObject();
        $department2->name = 'The Export Department';
        $departmentMapper->insert($department2);

        $department3 = &$departmentMapper->createObject();
        $department3->name = 'The Personnel Department';
        $departmentMapper->insert($department3);

        $department4 = &$departmentMapper->createObject();
        $department4->name = 'The Production Department';
        $departmentMapper->insert($department4);

        $employeeMapper = &Piece_ORM_Mapper_Factory::factory('Employee');

        $employee1 = &$employeeMapper->createObject();
        $employee1->name = 'Foo';
        $employee1->departments = array();
        $employee1->departments[] = &$department1;
        $employeeMapper->insert($employee1);

        $employee2 = &$employeeMapper->createObject();
        $employee2->name = 'Bar';
        $employee2->skills = array();
        $employee2->skills[] = &$skill1;
        $employee2->departments = array();
        $employee2->departments[] = &$department2;
        $employeeMapper->insert($employee2);

        $employee3 = &$employeeMapper->createObject();
        $employee3->name = 'Baz';
        $employee3->skills = array();
        $employee3->skills[] = &$skill2;
        $employee3->departments = array();
        $employee3->departments[] = &$department3;
        $employeeMapper->insert($employee3);

        $employee4 = &$employeeMapper->createObject();
        $employee4->name = 'Qux';
        $employee4->skills = array();
        $employee4->skills[] = &$skill1;
        $employee4->skills[] = &$skill2;
        $employee4->departments = array();
        $employee4->departments[] = &$department4;
        $employeeMapper->insert($employee4);
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
        $artistMapper = &Piece_ORM_Mapper_Factory::factory('Artist');

        $artist1 = &$artistMapper->createObject();
        $artist1->name = 'Foo';
        $artistMapper->insert($artist1);

        $artist2 = &$artistMapper->createObject();
        $artist2->name = 'Bar';

        $albumMapper = &Piece_ORM_Mapper_Factory::factory('Album');

        $album1 = &$albumMapper->createObject();
        $album1->name = 'The first album of the artist2';

        $artist2->albums = array();
        $artist2->albums[] = &$album1;
        $artistMapper->insert($artist2);

        $artist3 = &$artistMapper->createObject();
        $artist3->name = 'Baz';

        $album2 = &$albumMapper->createObject();
        $album2->name = 'The first album of the artist3';

        $album3 = &$albumMapper->createObject();
        $album3->name = 'The second album of the artist3';

        $artist3->albums = array();
        $artist3->albums[] = &$album2;
        $artist3->albums[] = &$album3;
        $artistMapper->insert($artist3);
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
        $placeMapper = &Piece_ORM_Mapper_Factory::factory('Place');

        $place1 = &$placeMapper->createObject();
        $place1->name = 'Foo';
        $placeMapper->insert($place1);

        $restaurantMapper = &Piece_ORM_Mapper_Factory::factory('Restaurant');

        $restaurant1 = &$restaurantMapper->createObject();
        $restaurant1->name = 'The restaurant on the place Bar.';
        $place2 = &$placeMapper->createObject();
        $place2->name = 'Bar';
        $place2->restaurant = &$restaurant1;
        $placeMapper->insert($place2);

        $restaurant2 = &$restaurantMapper->createObject();
        $restaurant2->name = 'The restaurant on the place Baz.';
        $place3 = &$placeMapper->createObject();
        $place3->name = 'Baz';
        $place3->restaurant = &$restaurant2;
        $placeMapper->insert($place3);
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
