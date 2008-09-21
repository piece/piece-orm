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

// {{{ Piece_ORM_Mapper_CompatibilityTests

/**
 * The base class for compatibility test. This class provides test cases to check
 * compatibility for various DB implementations.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
abstract class Piece_ORM_Mapper_CompatibilityTests extends PHPUnit_Framework_TestCase
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
    protected $dsn;
    protected $cacheDirectory;
    protected $tables = array('employees',
                              'skills',
                              'employees_skills',
                              'departments',
                              'computers',
                              'emails',
                              'employees_emails',
                              'nonprimarykeys',
                              'compositeprimarykey',
                              'unusualname12',
                              'unusualname1_2',
                              'unusualname1_2_unusualname_12',
                              'unusualname_12',
                              'files'
                              );

    /**#@-*/

    /**#@+
     * @access private
     */

    private $_initialized = false;

    /**#@-*/

    /**#@+
     * @access public
     */

    public function setUp()
    {
        $this->cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');

        $config = new Piece_ORM_Config();
        $config->setDSN('piece', $this->dsn);
        $config->setOptions('piece', array('debug' => 2, 'result_buffering' => false));
        $context = Piece_ORM_Context::singleton();
        $context->setConfiguration($config);
        $context->setDatabase('piece');
        Piece_ORM_Mapper_Factory::setConfigDirectory($this->cacheDirectory);
        Piece_ORM_Mapper_Factory::setCacheDirectory($this->cacheDirectory);
        Piece_ORM_Metadata_Factory::setCacheDirectory($this->cacheDirectory);
        if (!$this->_initialized) {
            $this->_clearTableRecords();
            $this->_initialized = true;
        }
    }

    public function tearDown()
    {
        $this->_clearTableRecords();
        $cache = new Cache_Lite(array('cacheDir' => "{$this->cacheDirectory}/",
                                      'automaticSerialization' => true,
                                      'errorHandlingAPIBreak' => true)
                                );
        $cache->clean();
        Piece_ORM_Metadata_Factory::restoreCacheDirectory();
        Piece_ORM_Metadata_Factory::clearInstances();
        Piece_ORM_Mapper_Factory::restoreCacheDirectory();
        Piece_ORM_Mapper_Factory::restoreConfigDirectory();
        Piece_ORM_Mapper_Factory::clearInstances();
        Piece_ORM_Context::clear();
        Piece_ORM_Error::clearErrors();
    }

    public function testShouldFindAnObject()
    {
        $id = $this->_insert();
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employee = $mapper->findById($id);

        $this->assertType('stdClass', $employee);
        $this->assertObjectHasAttribute('id', $employee);
        $this->assertObjectHasAttribute('firstName', $employee);
        $this->assertObjectHasAttribute('lastName', $employee);
        $this->assertObjectHasAttribute('note', $employee);
        $this->assertTrue(property_exists($employee, 'departmentsId'));
        $this->assertObjectHasAttribute('lockVersion', $employee);
        $this->assertObjectHasAttribute('createdAt', $employee);
        $this->assertObjectHasAttribute('updatedAt', $employee);
    }

    public function testShouldRaiseAnExceptionWhenAGivenCriteriaIsIncomplete()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        Piece_ORM_Error::disableCallback();
        $employee = $mapper->findById(null);
        Piece_ORM_Error::enableCallback();

        $this->assertTrue(Piece_ORM_Error::hasErrors());

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_CANNOT_INVOKE, $error['code']);
    }

    public function testShouldProvideBuiltinMethods()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');

        $this->assertTrue(method_exists($mapper, 'findById'));
        $this->assertTrue(method_exists($mapper, 'findByFirstName'));
        $this->assertTrue(method_exists($mapper, 'findByLastName'));
        $this->assertTrue(method_exists($mapper, 'findByNote'));
        $this->assertTrue(method_exists($mapper, 'findByDepartmentsId'));
        $this->assertTrue(method_exists($mapper, 'findByLockVersion'));
        $this->assertFalse(method_exists($mapper, 'findByCreatedAt'));
        $this->assertFalse(method_exists($mapper, 'findByUpdatedAt'));
        $this->assertTrue(method_exists($mapper, 'findAll'));
        $this->assertTrue(method_exists($mapper, 'findAllById'));
        $this->assertTrue(method_exists($mapper, 'findAllByFirstName'));
        $this->assertTrue(method_exists($mapper, 'findAllByLastName'));
        $this->assertTrue(method_exists($mapper, 'findAllByNote'));
        $this->assertTrue(method_exists($mapper, 'findAllByDepartmentsId'));
        $this->assertTrue(method_exists($mapper, 'findAllByLockVersion'));
        $this->assertFalse(method_exists($mapper, 'findAllByCreatedAt'));
        $this->assertFalse(method_exists($mapper, 'findAllByUpdatedAt'));
        $this->assertTrue(method_exists($mapper, 'insert'));
        $this->assertTrue(method_exists($mapper, 'delete'));
        $this->assertTrue(method_exists($mapper, 'update'));
    }

    public function testShouldFindAnObjectWithCriteria()
    {
        $id = $this->_insert();
        $expectedQuery = "SELECT * FROM employees WHERE id = $id";
        $criteria = new stdClass();
        $criteria->id = $id;
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employee = $mapper->findById($id);
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');

        $this->assertEquals($expectedQuery, $mapper->getLastQuery());

        $employeeWithCriteria = $mapper->findById($criteria);

        $this->assertEquals($expectedQuery, $mapper->getLastQuery());

        foreach ($employee as $key => $value)
        {
            $this->assertEquals($value, $employeeWithCriteria->$key);
        }
    }

    public function testShouldFindAnObjectWithUserDefineMethod()
    {
        $id = $this->_insert();
        $criteria1 = new stdClass();
        $criteria1->id = $id;
        $criteria1->note = 'Foo';
        $criteria2 = new stdClass();
        $criteria2->id = $id;
        $criteria2->note = 'Bar';
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');

        $this->assertNotNull($mapper->findByIdAndNote($criteria1));
        $this->assertNull($mapper->findByIdAndNote($criteria2));
    }

    public function testShouldBeAbleToOverwriteBuiltinMethods()
    {
        $this->_configure('Overwrite');
        $this->_insert();
        $criteria1 = new stdClass();
        $criteria1->firstName = 'Atsuhiro';
        $criteria1->note = 'Foo';
        $criteria2 = new stdClass();
        $criteria2->firstName = 'Atsuhiro';
        $criteria2->note = 'Bar';
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');

        $this->assertNull($mapper->findByFirstName($criteria1));
        $this->assertNotNull($mapper->findByFirstName($criteria2));
    }

    public function testShouldFindObjects()
    {
        $this->_insert();
        $this->_insert();
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employees = $mapper->findAll();

        $this->assertEquals('SELECT * FROM employees', $mapper->getLastQuery());
        $this->assertTrue(is_array($employees));
        $this->assertEquals(2, count($employees));

        foreach ($employees as $employee) {
            $this->assertType('stdClass', $employee);
            $this->assertObjectHasAttribute('id', $employee);
            $this->assertObjectHasAttribute('firstName', $employee);
            $this->assertObjectHasAttribute('lastName', $employee);
            $this->assertObjectHasAttribute('note', $employee);
            $this->assertTrue(property_exists($employee, 'departmentsId'));
            $this->assertObjectHasAttribute('lockVersion', $employee);
            $this->assertObjectHasAttribute('createdAt', $employee);
            $this->assertObjectHasAttribute('updatedAt', $employee);
        }
    }

    public function testShouldFindObjectsWithCriteria()
    {
        $this->_insert();
        $this->_insert();
        $expectedQuery = "SELECT * FROM employees WHERE note = 'Foo'";
        $criteria = new stdClass();
        $criteria->note = 'Foo';
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employees = $mapper->findAllByNote('Foo');

        $this->assertEquals($expectedQuery, $mapper->getLastQuery());

        $employeesWithCriteria = $mapper->findAllByNote($criteria);

        $this->assertEquals($expectedQuery, $mapper->getLastQuery());

        $this->assertTrue(is_array($employees));
        $this->assertEquals(2, count($employees));

        for ($i = 0, $count = count($employees); $i < $count; ++$i) {
            foreach ($employees[$i] as $key => $value)
            {
                $this->assertEquals($value, $employeesWithCriteria[$i]->$key);
            }
        }
    }

    public function testShouldUpdateObjects()
    {
        $id = $this->_insert();
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employee1 = $mapper->findById($id);
        $employee1->firstName = 'Seven';
        $affectedRows = $mapper->update($employee1);

        $this->assertEquals(1, $affectedRows);

        $employee2 = $mapper->findById($id);

        $this->assertEquals('Seven', $employee2->firstName);

        $employee1->foo = 'bar';

        $this->assertObjectHasAttribute('foo', $employee1);
        $this->assertEquals('bar', $employee1->foo);
        $this->assertObjectNotHasAttribute('foo', $employee2);
    }

    public function testShouldRaiseAnExceptionWhenAGivenCriteriaIsNull()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = null;
        Piece_ORM_Error::disableCallback();
        $mapper->delete($subject);
        Piece_ORM_Error::enableCallback();

        $this->assertTrue(Piece_ORM_Error::hasErrors());

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_CANNOT_INVOKE, $error['code']);
    }

    public function testShouldRaiseAnExceptionWhenAGivenCriteriaIsAnEmptyString()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = '';
        Piece_ORM_Error::disableCallback();
        $mapper->delete($subject);
        Piece_ORM_Error::enableCallback();

        $this->assertTrue(Piece_ORM_Error::hasErrors());

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);
    }

    public function testShouldRaiseAnExceptionWhenAGivenCriteriaIsAResource()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = fopen(__FILE__, 'r');
        Piece_ORM_Error::disableCallback();
        $mapper->delete($subject);
        Piece_ORM_Error::enableCallback();

        $this->assertTrue(Piece_ORM_Error::hasErrors());

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);
    }

    public function testShouldRaiseAnExceptionWhenDeletingObjectsByInappropriatePrimaryKey()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = $mapper->createObject();
        Piece_ORM_Error::disableCallback();
        $mapper->delete($subject);
        Piece_ORM_Error::enableCallback();

        $this->assertTrue(Piece_ORM_Error::hasErrors());

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        $subject = $mapper->createObject();
        $subject->id = null;
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        Piece_ORM_Error::disableCallback();
        $mapper->delete($subject);
        Piece_ORM_Error::enableCallback();

        $this->assertTrue(Piece_ORM_Error::hasErrors());

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);
    }

    public function testShouldRaiseAnExceptionWhenUpdatingObjectsByInappropriatePrimaryKey()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = $mapper->createObject();
        Piece_ORM_Error::disableCallback();
        $mapper->update($subject);
        Piece_ORM_Error::enableCallback();

        $this->assertTrue(Piece_ORM_Error::hasErrors());

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);

        $subject = $mapper->createObject();
        $subject->id = null;
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        Piece_ORM_Error::disableCallback();
        $mapper->update($subject);
        Piece_ORM_Error::enableCallback();

        $this->assertTrue(Piece_ORM_Error::hasErrors());

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_UNEXPECTED_VALUE, $error['code']);
    }

    public function testShouldBeAbleToOverwriteTheBuiltinInsertQuery()
    {
        $this->_configure('Overwrite');
        $id = $this->_insert();

        $this->assertNotNull($id);

        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employee = $mapper->findById($id);

        $this->assertNotNull($employee);
        $this->assertEquals('Atsuhiro', $employee->firstName);
        $this->assertEquals('Kubo', $employee->lastName);
        $this->assertEquals('Bar', $employee->note);

        $mapper->delete($employee);

        $this->assertNull($mapper->findById($id));
    }

    public function testShouldBeAbleToOverwriteTheBuiltinUpdateQuery()
    {
        $this->_configure('Overwrite');
        $id = $this->_insert();
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employee1 = $mapper->findById($id);
        $employee1->firstName = 'Seven';
        $affectedRows = $mapper->update($employee1);

        $this->assertEquals(1, $affectedRows);

        $employee2 = $mapper->findById($id);

        $this->assertNotNull($employee2);
        $this->assertEquals('Seven', $employee2->firstName);

        $mapper->delete($employee1);

        $this->assertNull($mapper->findById($id));
    }

    public function testShouldReplaceAnEmptyStringWithNull()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = $mapper->createObject();
        $subject->firstName = 'Foo';
        $subject->lastName = 'Bar';
        $subject->note = '';
        $id = $mapper->insert($subject);

        $employee = $mapper->findById($id);

        $this->assertNotNull($employee);
        $this->assertNull($employee->note);
    }

    public function testShouldRaiseAnExceptionWhenDetectingProblemWhileBuildingQuery()
    {
        $id = $this->_insert();
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employee = $mapper->findById($id);
        $employee->firstName = 'Seven';
        unset($employee->lastName);

        Piece_ORM_Error::disableCallback();
        $mapper->update($employee);
        Piece_ORM_Error::enableCallback();

        $this->assertTrue(Piece_ORM_Error::hasErrors());

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_CANNOT_INVOKE, $error['code']);
    }

    public function testShouldSupportManyToManyRelationships()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $employees = $mapper->findAllWithSkills2();

            $this->assertTrue(is_array($employees));
            $this->assertEquals(4, count($employees));

            foreach ($employees as $employee) {
                $this->assertTrue(is_array($employee->skills));

                switch ($employee->firstName) {
                case 'Foo':
                    $this->assertEquals(0, count($employee->skills));
                    break;
                case 'Bar':
                    $this->assertEquals(1, count($employee->skills));
                    if (count($employee->skills) == 1) {
                        $this->assertEquals('Foo', $employee->skills[0]->name);
                    } else {
                        $this->fail('Invalid skills count.');
                    }
                    break;
                case 'Baz':
                    if (count($employee->skills) == 1) {
                        $this->assertEquals('Bar', $employee->skills[0]->name);
                    } else {
                        $this->fail('Invalid skills count.');
                    }
                    break;
                case 'Qux':
                    if (count($employee->skills) == 2) {
                        $this->assertEquals('Foo', $employee->skills[0]->name);
                        $this->assertEquals('Bar', $employee->skills[1]->name);
                    } else {
                        $this->fail('Invalid skills count.');
                    }
                    break;
                default:
                    $this->fail('Unknown employee name.');
                }
            }

            $this->assertEquals($employees, $mapper->findAllWithSkills1());

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldSupportManyToManyRelationshipsWithBuiltinMethod()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $employees = $mapper->findAllByFirstName('Qux');

            $this->assertTrue(is_array($employees));
            $this->assertEquals(1, count($employees));
            $this->assertEquals(2, count($employees[0]->skills));
            $this->assertEquals($employees, $mapper->findAllByFirstName((object)array('firstName' => 'Qux')));

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldSupportOneToManyRelationships()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Departments' : 'departments';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $departments = $mapper->findAllWithEmployees2();

            $this->assertTrue(is_array($departments));
            $this->assertEquals(2, count($departments));

            foreach ($departments as $department) {
                $this->assertTrue(is_array($department->employees));

                switch ($department->name) {
                case 'Foo':
                    if (count($department->employees) == 1) {
                        $this->assertEquals('Bar', $department->employees[0]->firstName);
                    } else {
                        $this->fail('Invalid employees count.');
                    }
                    break;
                case 'Bar':
                    if (count($department->employees) == 2) {
                        $this->assertEquals('Baz', $department->employees[0]->firstName);
                        $this->assertEquals('Qux', $department->employees[1]->firstName);
                    } else {
                        $this->fail('Invalid employees count.');
                    }
                    break;
                default:
                    $this->fail('Unknown department name.');
                }
            }

            $this->assertEquals($departments, $mapper->findAllWithEmployees1());

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldSupportManyToOneRelationships()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $employees = $mapper->findAllWithDepartment2();

            $this->assertTrue(is_array($employees));
            $this->assertEquals(4, count($employees));

            foreach ($employees as $employee) {
                $this->assertTrue(property_exists($employee, 'department'));

                switch ($employee->firstName) {
                case 'Foo':
                    $this->assertNull($employee->department);
                    break;
                case 'Bar':
                    if (!is_null($employee->department)) {
                        $this->assertEquals('Foo', $employee->department->name);
                    } else {
                        $this->fail('The department field is not found.');
                    }
                    break;
                case 'Baz':
                    if (!is_null($employee->department)) {
                        $this->assertEquals('Bar', $employee->department->name);
                    } else {
                        $this->fail('The department field is not found.');
                    }
                    break;
                case 'Qux':
                    if (!is_null($employee->department)) {
                        $this->assertEquals('Bar', $employee->department->name);
                    } else {
                        $this->fail('The department field is not found.');
                    }
                    break;
                default:
                    $this->fail('Unknown employee name.');
                }
            }

            $this->assertEquals($employees, $mapper->findAllWithDepartment1());

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldSupportOneToOneRelationships()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $employees = $mapper->findAllWithComputer2();

            $this->assertTrue(is_array($employees));
            $this->assertEquals(4, count($employees));

            foreach ($employees as $employee) {
                $this->assertTrue(property_exists($employee, 'computer'));

                switch ($employee->firstName) {
                case 'Foo':
                    $this->assertNull($employee->computer);
                    break;
                case 'Bar':
                    if (!is_null($employee->computer)) {
                        $this->assertEquals('Baz', $employee->computer->name);
                    } else {
                        $this->fail('The computer field is not found.');
                    }
                    break;
                case 'Baz':
                    if (!is_null($employee->computer)) {
                        $this->assertEquals('Bar', $employee->computer->name);
                    } else {
                        $this->fail('The computer field is not found.');
                    }
                    break;
                case 'Qux':
                    if (!is_null($employee->computer)) {
                        $this->assertEquals('Foo', $employee->computer->name);
                    } else {
                        $this->fail('The computer field is not found.');
                    }
                    break;
                default:
                    $this->fail('Unknown employee name.');
                }
            }

            $this->assertEquals($employees, $mapper->findAllWithComputer1());

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldSupportLimitByMethod()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $mapper->setLimit(2);
            $employees = $mapper->findAllWithSkills1();

            $this->assertTrue(is_array($employees));
            $this->assertEquals(2, count($employees));

            $employees = $mapper->findAllWithSkills1();

            $this->assertEquals(4, count($employees));

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldSupportOffsetByMethod()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $mapper->setLimit(2, 2);
            $employees = $mapper->findAllWithSkills1();

            $this->assertTrue(is_array($employees));
            $this->assertEquals(2, count($employees));
            $this->assertEquals('Baz', $employees[0]->firstName);
            $this->assertEquals('Qux', $employees[1]->firstName);

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldRaiseAnExceptionWhenAWrongLimitIsGiven()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            Piece_ORM_Error::disableCallback();
            $mapper->setLimit(-1);
            Piece_ORM_Error::enableCallback();

            $this->assertTrue(Piece_ORM_Error::hasErrors());

            $error = Piece_ORM_Error::pop();

            $this->assertEquals(PIECE_ORM_ERROR_CANNOT_INVOKE, $error['code']);

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldRaiseAnExceptionWhenAWrongOffsetIsGiven()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            Piece_ORM_Error::disableCallback();
            $mapper->setLimit(2, -1);
            Piece_ORM_Error::enableCallback();

            $this->assertTrue(Piece_ORM_Error::hasErrors());

            $error = Piece_ORM_Error::pop();

            $this->assertEquals(PIECE_ORM_ERROR_CANNOT_INVOKE, $error['code']);

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldSupportOrderByMethod()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $mapper->addOrder('first_name');
            $mapper->addOrder('id');
            $employees = $mapper->findAllWithSkills1();

            $this->assertTrue(is_array($employees));
            $this->assertEquals(4, count($employees));

            $this->assertEquals('Bar', $employees[0]->firstName);
            $this->assertEquals('Baz', $employees[1]->firstName);
            $this->assertEquals('Foo', $employees[2]->firstName);
            $this->assertEquals('Qux', $employees[3]->firstName);

            $mapper->addOrder('first_name', true);
            $mapper->addOrder('id');

            $employees = $mapper->findAllWithSkills1();

            $this->assertTrue(is_array($employees));
            $this->assertEquals(4, count($employees));

            $this->assertEquals('Bar', $employees[3]->firstName);
            $this->assertEquals('Baz', $employees[2]->firstName);
            $this->assertEquals('Foo', $employees[1]->firstName);
            $this->assertEquals('Qux', $employees[0]->firstName);

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldSupportOrderWithManyToManyRelationships()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $mapper->addOrder('id');
            $employees = $mapper->findAllWithOrderedSkills();

            $this->assertTrue(is_array($employees));
            $this->assertEquals(4, count($employees));

            $this->assertEquals('Bar', $employees[3]->skills[0]->name);
            $this->assertEquals('Foo', $employees[3]->skills[1]->name);

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldSupportOrderWithOneToManyRelationships()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Departments' : 'departments';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $mapper->addOrder('id');
            $departments = $mapper->findAllWithOrderedEmployees();

            $this->assertTrue(is_array($departments));
            $this->assertEquals(2, count($departments));
            $this->assertEquals('Qux', $departments[1]->employees[0]->firstName);
            $this->assertEquals('Baz', $departments[1]->employees[1]->firstName);

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldDeleteObjects()
    {
        $id = $this->_insert();
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employee1 = $mapper->findById($id);

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($employee1)));

        $mapper->delete($employee1);
        $employee2 = $mapper->findById($id);

        $this->assertNull($employee2);
    }

    public function testShouldCreateAnObject()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = $mapper->createObject();

        $this->assertType('stdClass', $subject);
        $this->assertEquals(8, count(get_object_vars($subject)));
        $this->assertTrue(property_exists($subject, 'id'));
        $this->assertTrue(property_exists($subject, 'firstName'));
        $this->assertTrue(property_exists($subject, 'lastName'));
        $this->assertTrue(property_exists($subject, 'note'));
        $this->assertTrue(property_exists($subject, 'departmentsId'));
        $this->assertTrue(property_exists($subject, 'lockVersion'));
        $this->assertTrue(property_exists($subject, 'createdAt'));
        $this->assertTrue(property_exists($subject, 'updatedAt'));
    }

    public function testShouldSupportCascadeUpdateWithManyToManyRelationships()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Skills' : 'skills';
            $skillsMapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $skills = $skillsMapper->findAll();
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $employeeMapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $employee1 = $employeeMapper->findWithSkillsByFirstName('Foo');

            $this->assertEquals(0, count($employee1->skills));

            $employee1->skills = $skills;
            $employeeMapper->update($employee1);
            $employee2 = $employeeMapper->findWithSkillsByFirstName('Foo');

            $this->assertEquals(2, count($employee2->skills));

            $employee1->foo = 'bar';

            $this->assertObjectHasAttribute('foo', $employee1);
            $this->assertEquals('bar', $employee1->foo);
            $this->assertObjectNotHasAttribute('foo', $employee2);

            unset($employee1->foo);
            unset($employee1->skills);
            unset($employee1->updatedAt);
            unset($employee1->lockVersion);
            unset($employee2->skills);
            unset($employee2->updatedAt);
            unset($employee2->lockVersion);

            $this->assertEquals($employee1, $employee2);

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldSupportCascadeUpdateWithOneToManyRelationships()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Departments' : 'departments';
            $departmentsMapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $department1 = $departmentsMapper->findWithEmployeesByName('Bar');

            $this->assertEquals(2, count($department1->employees));

            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $employeesMapper = Piece_ORM_Mapper_Factory::factory($mapperName);

            $subject1 = $employeesMapper->createObject();
            $subject1->firstName = 'Quux';
            $subject1->lastName = 'Quuux';
            $department1->employees[] = $subject1;
            array_shift($department1->employees);

            $this->assertEquals('Baz', $department1->employees[0]->firstName);

            $department1->employees[0]->firstName = 'Qux2';
            $department1->employees[0]->lastName = 'Quux2';
            $subject2 = $employeesMapper->createObject();
            $subject2->firstName = 'Quuux';
            $subject2->lastName = 'Quuuux';
            $department1->employees[] = $subject2;
            $departmentsMapper->update($department1);

            $department2 = $departmentsMapper->findWithEmployeesByName('Bar');

            $this->assertEquals(3, count($department2->employees));
            $this->assertEquals('Quuux', $department2->employees[0]->firstName);
            $this->assertEquals('Quux', $department2->employees[1]->firstName);
            $this->assertEquals('Qux2', $department2->employees[2]->firstName);

            $department1->foo = 'bar';

            $this->assertObjectHasAttribute('foo', $department1);
            $this->assertEquals('bar', $department1->foo);
            $this->assertObjectNotHasAttribute('foo', $department2);

            unset($department1->employees);
            unset($department1->foo);
            unset($department1->updatedAt);
            unset($department2->employees);
            unset($department2->updatedAt);

            $this->assertEquals($department1, $department2);

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldSupportCascadeUpdateWithOneToOneRelationships()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $employeesMapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $employee1 = $employeesMapper->findWithComputerByFirstName('Foo');

            $this->assertNull($employee1->computer);

            $mapperName = !$useMapperNameAsTableName ? 'Computers' : 'computers';
            $computersMapper = Piece_ORM_Mapper_Factory::factory($mapperName);

            $subject1 = $computersMapper->createObject();
            $subject1->name = 'Qux';
            $employee1->computer = $subject1;
            $employeesMapper->update($employee1);

            $employee2 = $employeesMapper->findWithComputerByFirstName('Foo');

            $this->assertNotNull($employee2->computer);
            $this->assertEquals('Qux', $employee2->computer->name);

            unset($employee1->computer);
            unset($employee1->updatedAt);
            unset($employee1->lockVersion);
            unset($employee2->computer);
            unset($employee2->updatedAt);
            unset($employee2->lockVersion);

            $this->assertEquals($employee1, $employee2);

            $employee1 = $employeesMapper->findWithComputerByFirstName('Foo');

            $this->assertNotNull($employee1->computer);

            $employee1->computer = null;
            $employeesMapper->update($employee1);

            $employee2 = $employeesMapper->findWithComputerByFirstName('Foo');

            $this->assertNull($employee2->computer);

            unset($employee1->computer);
            unset($employee1->updatedAt);
            unset($employee1->lockVersion);
            unset($employee2->computer);
            unset($employee2->updatedAt);
            unset($employee2->lockVersion);

            $this->assertEquals($employee1, $employee2);

            $employee1 = $employeesMapper->findWithComputerByFirstName('Bar');

            $this->assertNotNull($employee1->computer);
            $this->assertEquals('Baz', $employee1->computer->name);

            $employee1->computer->name = 'Baz2';
            $employeesMapper->update($employee1);

            $employee2 = $employeesMapper->findWithComputerByFirstName('Bar');

            $this->assertNotNull($employee2->computer);
            $this->assertEquals('Baz2', $employee2->computer->name);

            $employee1->foo = 'employee';

            $this->assertObjectHasAttribute('foo', $employee1);
            $this->assertEquals('employee', $employee1->foo);
            $this->assertObjectNotHasAttribute('foo', $employee2);

            unset($employee1->computer);
            unset($employee1->foo);
            unset($employee1->updatedAt);
            unset($employee1->lockVersion);
            unset($employee2->computer);
            unset($employee2->updatedAt);
            unset($employee2->lockVersion);

            $this->assertEquals($employee1, $employee2);

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldSupportCascadeDeleteWithManyToManyRelationships()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $employeesMapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $employee = $employeesMapper->findWithSkillsByFirstName('Qux');
            $mapperName = !$useMapperNameAsTableName ? 'EmployeesSkills' : 'employees_skills';
            $employeesSkillsMapper = Piece_ORM_Mapper_Factory::factory($mapperName);

            $this->assertEquals(2, count($employeesSkillsMapper->findAllByEmployeesId($employee->id)));

            $employeesMapper->delete($employee);

            $this->assertEquals(0, count($employeesSkillsMapper->findAllByEmployeesId($employee->id)));

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldSupportCascadeDeleteWithOneToManyRelationships()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Departments' : 'departments';
            $departmentsMapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $department = $departmentsMapper->findWithEmployeesByName('Bar');
            $departmentsId = $department->id;
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $employeesMapper = Piece_ORM_Mapper_Factory::factory($mapperName);

            $this->assertEquals(2, count($employeesMapper->findAllByDepartmentsId($department->id)));

            $departmentsMapper->delete($department);

            $this->assertEquals(0, count($employeesMapper->findAllByDepartmentsId($department->id)));

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    public function testShouldSupportCascadeDeleteWithOneToOneRelationships()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $employeesMapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $employee = $employeesMapper->findWithComputerByFirstName('Baz');
            $mapperName = !$useMapperNameAsTableName ? 'Computers' : 'computers';
            $computersMapper = Piece_ORM_Mapper_Factory::factory($mapperName);

            $this->assertNotNull($computersMapper->findByEmployeesId($employee->id));

            $employeesMapper->delete($employee);

            $this->assertNull($computersMapper->findByEmployeesId($employee->id));

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    /**
     * @since Method available since Release 0.3.0
     */
    public function testShouldProvideCountForTheLastQuery()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);

            $this->assertNull($mapper->getCount());

            $mapper->findAll();

            $this->assertEquals(4, $mapper->getCount());

            $mapper->setLimit(2);
            $employees = $mapper->findAll();

            $this->assertEquals(2, count($employees));
            $this->assertEquals(4, $mapper->getCount());

            $employees = $mapper->findAllByFirstName('Qux');

            $this->assertEquals(1, count($employees));
            $this->assertEquals(2, count($employees[0]->skills));
            $this->assertEquals(1, $mapper->getCount());

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    /**
     * @since Method available since Release 0.3.0
     */
    public function testShouldFindAValue()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);

            $this->assertNull($mapper->findOneForFirstNameByFirstName('NonExisting'));

            $mapper->addOrder('id', true);

            $this->assertEquals('Qux', $mapper->findOneForFirstNameByFirstName((object)array('firstName' => 'Qux')));
            $this->assertEquals(4, $mapper->findOneForCount());

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    /**
     * @since Method available since Release 0.4.0
     */
    public function testShouldProvideCountWithFindAll()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);

            $this->assertNull($mapper->getCount());

            $mapper->findAll();

            $this->assertEquals(4, $mapper->getCount());

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    /**
     * @since Method available since Release 0.4.1
     */
    public function testShouldExtractPhpNullAsDatabaseNull()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = $mapper->createObject();
        $subject->firstName = 'Atsuhiro';
        $subject->lastName = 'Kubo';

        $this->assertNull($subject->note);

        $employee = $mapper->findById($mapper->insert($subject));

        $this->assertNull($employee->note);
    }

    /**
     * @since Method available since Release 0.5.0
     */
    public function testShouldFindObjectsWithoutThePrimaryKey()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = $mapper->createObject();
        $subject->firstName = 'Taro';
        $subject->lastName = 'ITEMAN';
        $subject->note = 'Foo';
        $mapper->insert($subject);
        $subject = $mapper->createObject();
        $subject->firstName = 'Taro';
        $subject->lastName = 'ITEMAN';
        $subject->note = 'Bar';
        $mapper->insert($subject);
        $mapper->addOrder('id');
        $employees = $mapper->findAllNotes();

        $this->assertEquals(2, count($employees));
        $this->assertEquals('Foo', $employees[0]->note);
        $this->assertEquals('Bar', $employees[1]->note);
    }

    /**
     * @since Method available since Release 0.5.0
     */
    public function testShouldProvideCountEvenThoughOrderIsSet()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);

            $this->assertNull($mapper->getCount());

            $mapper->addOrder('created_at');
            $mapper->findAll();

            $this->assertEquals(4, $mapper->getCount());

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    /**
     * @since Method available since Release 0.5.0
     */
    public function testShouldBeAbleToDefineAnyInsertMethodsByConfiguration()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = $mapper->createObject();
        $subject->firstName = 'Taro';
        $subject->lastName = 'ITEMAN';
        $subject->note = 'Foo';

        $id = $mapper->insertUserDefined($subject);

        $this->assertNotNull($id);

        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employee = $mapper->findById($id);

        $this->assertNotNull($employee);
        $this->assertEquals('Taro', $employee->firstName);
        $this->assertEquals('ITEMAN', $employee->lastName);
        $this->assertEquals('Bar', $employee->note);

        $mapper->delete($employee);

        $this->assertNull($mapper->findById($id));
    }

    /**
     * @since Method available since Release 0.5.0
     */
    public function testShouldBeAbleToDefineAnyUpdateMethodsByConfiguration()
    {
        $id = $this->_insert();
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employee1 = $mapper->findById($id);
        $employee1->note = 'Baz';
        $affectedRows = $mapper->updateUserDefined($employee1);

        $this->assertEquals(1, $affectedRows);

        $employee2 = $mapper->findById($id);

        $this->assertNotNull($employee2);
        $this->assertEquals('Baz', $employee2->note);

        $mapper->deleteUserDefined($employee1);

        $this->assertNull($mapper->findById($id));
    }

    /**
     * @since Method available since Release 0.5.0
     */
    public function testShouldUpdateObjectsWithoutThePrimaryKey()
    {
        $id1 = $this->_insert();
        $id2 = $this->_insert();
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = new stdClass();
        $subject->note = 'Baz';
        $subject->oldNote = 'Foo';
        $subject->updatedAt = null;
        $affectedRows = $mapper->updateNoteByNote($subject);

        $this->assertEquals(2, $affectedRows);

        $employee1 = $mapper->findById($id1);

        $this->assertEquals('Baz', $employee1->note);

        $employee2 = $mapper->findById($id2);

        $this->assertEquals('Baz', $employee2->note);
    }

    /**
     * @since Method available since Release 0.5.0
     */
    public function testShouldDeleteObjectsWithoutThePrimaryKey()
    {
        $id1 = $this->_insert();
        $id2 = $this->_insert();
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = new stdClass();
        $subject->note = 'Foo';
        $affectedRows = $mapper->deleteByNote($subject);

        $this->assertEquals(2, $affectedRows);

        $people = $mapper->findAll();

        $this->assertEquals(0, count($people));
    }

    /**
     * @since Method available since Release 0.5.0
     */
    public function testShouldFindAnObjectWithAStaticQuery()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employee = $mapper->findWithStaticQuery();

        $this->assertEquals(1, $employee->one);
    }

    /**
     * @since Method available since Release 0.5.0
     */
    public function testShouldFindObjectsWithAStaticQuery()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employees = $mapper->findAllWithStaticQuery();

        $this->assertEquals(1, count($employees));
        $this->assertEquals(1, $employees[0]->one);
    }

    /**
     * @since Method available since Release 0.5.0
     */
    public function testShouldFindAValueWithAStaticQuery()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');

        $this->assertEquals(1, $mapper->findOneWithStaticQuery());
    }

    /**
     * @since Method available since Release 0.5.0
     */
    public function testShouldInsertAnObjectWithAStaticQuery()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $id = @$mapper->insertWithStaticQuery();

        $this->assertNotNull($id);
        $this->assertTrue(is_int($id));
    }

    /**
     * @since Method available since Release 0.5.0
     */
    public function testShouldUpdateObjectsWithAStaticQuery()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');

        $this->assertEquals(0, @$mapper->updateWithStaticQuery());
    }

    /**
     * @since Method available since Release 0.5.0
     */
    public function testShouldDeleteObjectsWithAStaticQuery()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');

        $this->assertEquals(0, @$mapper->deleteWithStaticQuery());
    }

    /**
     * @since Method available since Release 0.5.0
     */
    public function testShouldRaiseAnExceptionWhenUniqueConstraintErrorIsOccurred()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Emails');
        $subject = $mapper->createObject();
        $subject->email = 'foo@example.org';
        $mapper->insert($subject);
        Piece_ORM_Error::disableCallback();
        $mapper->insert($subject);
        Piece_ORM_Error::enableCallback();

        $this->assertTrue(Piece_ORM_Error::hasErrors());

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_CONSTRAINT, $error['code']);
    }

    /**
     * @since Method available since Release 0.6.0
     */
    public function testShouldGenerateTheDefaultQueryIfTheQueryForAInsertMethodIsNotGiven()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');

        $this->assertEquals('INSERT INTO $__table (first_name, last_name, note, departments_id) VALUES ($firstName, $lastName, $note, $departmentsId)', $mapper->__query__insertwithnoquery);
    }

    /**
     * @since Method available since Release 0.6.0
     */
    public function testShouldGenerateTheDefaultQueryIfTheQueryForAUpdateMethodIsNotGiven()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');

        $this->assertEquals('UPDATE $__table SET first_name = $firstName, last_name = $lastName, note = $note, departments_id = $departmentsId, created_at = $createdAt, updated_at = $updatedAt, lock_version = lock_version + 1 WHERE id = $id AND lock_version = $lockVersion', $mapper->__query__updatewithnoquery);
    }

    /**
     * @since Method available since Release 0.6.0
     */
    public function testShouldGenerateTheDefaultQueryIfTheQueryForADeleteMethodIsNotGiven()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');

        $this->assertEquals('DELETE FROM $__table WHERE id = $id', $mapper->__query__deletewithnoquery);
    }

    /**
     * @since Method available since Release 0.6.0
     */
    public function testShouldTreatManyToManyRelationshipsWithUnderscoreTheSeparatedPrimaryKey()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $mapperName = !$useMapperNameAsTableName ? 'Emails' : 'emails';
            $emailsMapper = Piece_ORM_Mapper_Factory::factory($mapperName);

            $subject1 = $emailsMapper->createObject();
            $subject1->email = 'foo@example.org';
            $emailsMapper->insert($subject1);

            $subject2 = $emailsMapper->createObject();
            $subject2->email = 'bar@example.org';
            $emailsMapper->insert($subject2);

            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $employeesMapper = Piece_ORM_Mapper_Factory::factory($mapperName);

            $subject = $employeesMapper->createObject();
            $subject->firstName = 'Foo';
            $subject->lastName = 'Bar';
            $subject->emails = array();
            $subject->emails[] = $subject1;
            $subject->emails[] = $subject2;
            $employeesMapper->insertWithEmails($subject);

            $employees = $employeesMapper->findAllWithEmails();

            $this->assertEquals(1, count($employees));
            $this->assertTrue(property_exists($employees[0], 'emails'));
            $this->assertTrue(is_array($employees[0]->emails));
            $this->assertEquals(2, count($employees[0]->emails));
            $this->assertEquals('foo@example.org', $employees[0]->emails[0]->email);
            $this->assertEquals('bar@example.org', $employees[0]->emails[1]->email);

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    /**
     * @since Method available since Release 0.6.0
     */
    public function testShouldDefineSortOrdersByConfiguration()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = $mapper->createObject();
        $subject->firstName = 'Bar';
        $subject->lastName = 'Foo';
        $mapper->insert($subject);
        $subject = $mapper->createObject();
        $subject->firstName = 'Baz';
        $subject->lastName = 'Bar';
        $mapper->insert($subject);

        $employees = $mapper->findAllOrderByLastName();

        $this->assertEquals(2, count($employees));
        $this->assertEquals('Bar', $employees[0]->lastName);
        $this->assertEquals('Foo', $employees[1]->lastName);

        $employee = $mapper->findOrderByLastName();

        $this->assertNotNull($employee);
        $this->assertEquals('Bar', $employee->lastName);
        
        $lastName = $mapper->findOneOrderByLastName();

        $this->assertNotNull($lastName);
        $this->assertEquals('Bar', $lastName);
    }

    /**
     * @since Method available since Release 0.6.0
     */
    public function testShouldPreferDynamicSortOrdersToStaticSortOrders()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = $mapper->createObject();
        $subject->firstName = 'Bar';
        $subject->lastName = 'Foo';
        $mapper->insert($subject);
        $subject = $mapper->createObject();
        $subject->firstName = 'Baz';
        $subject->lastName = 'Bar';
        $mapper->insert($subject);
        $mapper->addOrder('id');
        $employees = $mapper->findAllOrderByLastName();

        $this->assertEquals(2, count($employees));
        $this->assertEquals('Foo', $employees[0]->lastName);
        $this->assertEquals('Bar', $employees[1]->lastName);
    }

    /**
     * @since Method available since Release 0.7.0
     */
    public function testShouldSetCharsetByDSN() {}

    /**
     * @since Method available since Release 0.8.0
     */
    public function testShouldUpdateObjectsOfATableWhichDoesNotHaveThePrimaryKey()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Nonprimarykeys');
        $subject1 = $mapper->createObject();
        $subject1->memberId = 1;
        $subject1->serviceId = 1;
        $mapper->insert($subject1);
        $subject2 = $mapper->findByMemberIdAndServiceId($subject1);
        $subject2->point += 50;
        Piece_ORM_Error::disableCallback();
        $affectedRows = $mapper->updateByMemberIdAndServiceId($subject2);
        Piece_ORM_Error::enableCallback();

        $this->assertFalse(Piece_ORM_Error::hasErrors());
        $this->assertEquals(1, $affectedRows);
    }

    /**
     * @since Method available since Release 0.8.0
     */
    public function testShouldDeleteFromATableWhichDoesNotHaveThePrimaryKey()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Nonprimarykeys');
        $subject1 = $mapper->createObject();
        $subject1->memberId = 1;
        $subject1->serviceId = 1;
        $mapper->insert($subject1);
        $subject2 = $mapper->findByMemberIdAndServiceId($subject1);
        Piece_ORM_Error::disableCallback();
        $affectedRows = $mapper->deleteByMemberIdAndServiceId($subject2);
        Piece_ORM_Error::enableCallback();

        $this->assertFalse(Piece_ORM_Error::hasErrors());
        $this->assertEquals(1, $affectedRows);
    }

    /**
     * @since Method available since Release 0.8.1
     */
    public function testShouldWorkWithATableWhichHasTheCompositePrimaryKey()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Compositeprimarykey');
        $subject = $mapper->createObject();
        $subject->album = 'On Stage';
        $subject->artist = 'Rainbow';
        $subject->track = 1;
        $subject->song = 'Kill the King';
        $mapper->insert($subject);
        $subjects1 = $mapper->findAll();

        $this->assertEquals(1, count($subjects1));

        if (!count($subjects1)) {
            return;
        }

        $this->assertEquals('On Stage', $subjects1[0]->album);
        $this->assertEquals('Rainbow', $subjects1[0]->artist);
        $this->assertEquals(1, $subjects1[0]->track);
        $this->assertEquals('Kill the King', $subjects1[0]->song);

        $subjects1[0]->song = 'Intro: Over The Rainbow / Kill The King';
        $affectedRows = $mapper->update($subjects1[0]);

        $this->assertEquals(1, $affectedRows);

        $subjects2 = $mapper->findAllBySong('Intro: Over The Rainbow / Kill The King');

        $this->assertEquals(1, count($subjects2));

        if (!count($subjects2)) {
            return;
        }

        $affectedRows = $mapper->delete($subjects2[0]);

        $this->assertEquals(1, $affectedRows);

        $subjects3 = $mapper->findAllBySong('Intro: Over The Rainbow / Kill The King');

        $this->assertEquals(0, count($subjects3));
    }

    /**
     * @since Method available since Release 0.8.1
     */
    public function testShouldWorkWithATableWhichHasTheUnusualName()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $mapperName = !$useMapperNameAsTableName ? 'Unusualname_12' : 'unusualname_12';
            $inverseMapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $inverseSubject = $inverseMapper->createObject();
            $inverseSubject->name = 'foo';
            $inverseMapper->insert($inverseSubject);

            $this->assertEquals(1, count($inverseMapper->findAll()));

            $mapperName = !$useMapperNameAsTableName ? 'Unusualname1_2' : 'unusualname1_2';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $subject = $mapper->createObject();
            $subject->name = 'bar';
            $subject->baz = array();
            $subject->baz[] = $inverseSubject;
            $mapper->insert($subject);

            $objects = $mapper->findAll();

            $this->assertEquals(1, count($objects));
            $this->assertEquals('bar', $objects[0]->name);

            $this->assertEquals(1, count($objects[0]->baz));
            $this->assertEquals('foo', $objects[0]->baz[0]->name);

            $mapperName = !$useMapperNameAsTableName ? 'Unusualname1_2_unusualname_12' : 'unusualname1_2_unusualname_12';
            $throughMapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $objects = $throughMapper->findAll();

            $this->assertEquals(1, count($objects));
            $this->assertEquals(3, count(array_keys((array)$objects[0])));
            $this->assertObjectHasAttribute('id', $objects[0]);
            $this->assertObjectHasAttribute('unusualname1_2_id', $objects[0]);
            $this->assertObjectHasAttribute('unusualname_12_id', $objects[0]);

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    /**
     * @since Method available since Release 0.8.1
     */
    public function testShouldWorkWithAnyFinderMethodCallsForAMapperWhichHasAlreadyUsedInRelationships()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
            $employeesMapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $employeesMapper->findAllWithSkills2();
            $mapperName = !$useMapperNameAsTableName ? 'Skills' : 'skills';
            $skillsMapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $skills = $skillsMapper->findAll();

            $this->assertEquals(2, $skillsMapper->getCount());
            $this->assertEquals(2, count($skills));

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    /**
     * @since Method available since Release 0.8.1
     */
    public function testShouldTreatMethodNamesAsCaseInsensitive()
    {
        $this->_configure('Overwrite');
        $id = $this->_insert();
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');

        $this->assertNotNull($mapper->findByLastName((object)array('lastName' => 'Kubo')));
    }

    /**
     * @since Method available since Release 1.0.0
     */
    public function testShouldExpandValuesWithACommaIfAPropertyIsAnArray()
    {
        $ids = array();
        $ids[] = $this->_insert();
        $ids[] = $this->_insert();
        $this->_insert();
        $employeesMapper = Piece_ORM_Mapper_Factory::factory('Employees');

        $this->assertEquals(3, count($employeesMapper->findAll()));
        $this->assertEquals(2, count($employeesMapper->findAllByIds((object)array('ids' => $ids))));
        $this->assertRegexp('/IN \(\d+, \d+\)/', $employeesMapper->getLastQuery());
    }

    /**
     * @since Method available since Release 1.0.0
     */
    public function testShouldUseAMapperNameAsATableNameIfEnabled()
    {
        $config = new Piece_ORM_Config();
        $config->setDSN('caseSensitive', $this->dsn);
        $config->setOptions('caseSensitive', array('debug' => 2, 'result_buffering' => false));
        $config->setUseMapperNameAsTableName('caseSensitive', true);
        $context = Piece_ORM_Context::singleton();
        $context->setConfiguration($config);
        $context->setDatabase('caseSensitive');
        Piece_ORM_Mapper_Factory::setConfigDirectory($this->cacheDirectory);
        Piece_ORM_Mapper_Factory::setCacheDirectory($this->cacheDirectory);
        Piece_ORM_Metadata_Factory::setCacheDirectory($this->cacheDirectory);
        Piece_ORM_Error::disableCallback();
        $mapper = Piece_ORM_Mapper_Factory::factory('Case_Sensitive');
        Piece_ORM_Error::enableCallback();

        $this->assertFalse(Piece_ORM_Error::hasErrors());

        Piece_ORM_Error::disableCallback();
        $mapper->findAllByFirstName((object)array('firstName' => 'foo'));
        Piece_ORM_Error::enableCallback();

        $this->assertFalse(Piece_ORM_Error::hasErrors());

        $dbh = $mapper->getConnection();

        $this->assertRegexp('/FROM ' . preg_quote($dbh->quoteIdentifier('Case_Sensitive'), '/') . '/', $mapper->getLastQuery());
    }

    /**
     * @since Method available since Release 1.0.0
     */
    public function testShouldWorkAfterInsertUsingAnObjectReturnedFromFind()
    {
        $id1 = $this->_insert();
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employee = $mapper->findById($id1);
        $id2 = $mapper->insert($employee);
        $mapper->addOrder('id');
        $employees = $mapper->findAll();

        $this->assertEquals(2, count($employees));
        $this->assertEquals($id1, $employees[0]->id);
        $this->assertEquals($id2, $employees[1]->id);
    }

    /**
     * @since Method available since Release 1.0.0
     */
    public function testShouldSupportLob()
    {
        $jpegPath = "{$this->cacheDirectory}/picture.jpg";
        $pngPath = "{$this->cacheDirectory}/picture.png";
        $mapper = Piece_ORM_Mapper_Factory::factory('Files');
        $subject = $mapper->createObject();
        $subject->picture = $mapper->createLOB("file://$jpegPath");
        $id = $mapper->insert($subject);
        $file1 = $mapper->findById($id);

        $this->assertType('Piece_ORM_Mapper_LOB', $file1->picture);
        $this->assertStringEqualsFile($jpegPath, $file1->picture->load());

        $file1->picture->setSource("file://$pngPath");
        $mapper->update($file1);
        $file2 = $mapper->findById($id);

        $this->assertStringNotEqualsFile($jpegPath, $file2->picture->load());
        $this->assertStringEqualsFile($pngPath, $file2->picture->load());
    }

    /**
     * @since Method available since Release 1.0.0
     */
    public function testShouldSetAFunctionToGetTheCurrentTimestampToTheCreatedatFieldWhenExecutingInsert()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = $mapper->createObject();
        $subject->firstName = 'Foo';
        $subject->lastName = 'Bar';
        $mapper->insert($subject);

        $this->assertRegexp('/CURRENT_TIMESTAMP/', $mapper->getLastQuery());
    }

    /**
     * @since Method available since Release 1.0.0
     */
    public function testShouldSetAFunctionToGetTheCurrentTimestampToTheUpdatedatFieldWhenExecutingUpdate()
    {
        $id = $this->_insert();
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employee = $mapper->findById($id);
        $mapper->update($employee);

        $this->assertRegexp('/CURRENT_TIMESTAMP/', $mapper->getLastQuery());
    }

    /**
     * @since Method available since Release 1.0.0
     */
    public function testShouldSupportOptimisticLockingByTheLockversionFieldOnlyInDefaultQueries()
    {
        $id = $this->_insert();
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $employee1 = $mapper->findById($id);
        $employee2 = $mapper->findById($id);

        $this->assertEquals(0, $employee1->lockVersion);
        $this->assertEquals(0, $employee2->lockVersion);

        $affectedRows = $mapper->update($employee1);

        $this->assertEquals(1, $affectedRows);

        $employee1 = $mapper->findById($id);

        $this->assertEquals(1, $employee1->lockVersion);

        $affectedRows = $mapper->update($employee2);

        $this->assertEquals(0, $affectedRows);

        $employee2 = $mapper->findById($id);

        $this->assertEquals(1, $employee2->lockVersion);
    }

    /**
     * @since Method available since Release 1.1.0
     */
    public function testShouldWorkWithNullLobFields()
    {
        $jpegPath = "{$this->cacheDirectory}/picture.jpg";
        $mapper = Piece_ORM_Mapper_Factory::factory('Files');
        $subject = $mapper->createObject();
        $id = $mapper->insert($subject);
        $file = $mapper->findById($id);

        $this->assertNull($file->picture);
    }

    /**
     * @since Method available since Release 1.1.0
     */
    public function testShouldKeepALobFieldValueAfterInvokingUpdateIfTheValueIsNotChanged()
    {
        $jpegPath = "{$this->cacheDirectory}/picture.jpg";
        $mapper = Piece_ORM_Mapper_Factory::factory('Files');
        $subject = $mapper->createObject();
        $subject->picture = $mapper->createLOB("file://$jpegPath");
        $id = $mapper->insert($subject);
        $file1 = $mapper->findById($id);
        $mapper->update($file1);
        $file2 = $mapper->findById($id);

        $this->assertEquals($file1->picture->load(), $file2->picture->load());
    }

    /**
     * @since Method available since Release 1.1.0
     */
    public function testShouldTreatIndividualBlobs()
    {
        $jpegPath = "{$this->cacheDirectory}/picture.jpg";
        $pngPath = "{$this->cacheDirectory}/picture.png";
        $mapper = Piece_ORM_Mapper_Factory::factory('Files');
        $subject = $mapper->createObject();
        $subject->picture = $mapper->createLOB("file://$jpegPath");
        $subject->largePicture = $mapper->createLOB("file://$pngPath");
        $id = $mapper->insert($subject);
        $file = $mapper->findById($id);

        $this->assertTrue(file_get_contents($jpegPath) != file_get_contents($pngPath));
        $this->assertEquals(file_get_contents($jpegPath), $file->picture->load());
        $this->assertEquals(file_get_contents($pngPath), $file->largePicture->load());
    }

    /**
     * @since Method available since Release 1.2.0
     */
    abstract public function testShouldProvideTheDefaultValueOfAGivenField();

    /**
     * @since Method available since Release 1.2.0
     */
    public function testShouldTreatInverseThroughTablesOnManyToManyRelationships()
    {
        foreach (array(false, true) as $useMapperNameAsTableName) {
            if ($useMapperNameAsTableName) {
                $this->_prepareCaseSensitiveContext();
            }

            $this->_prepareTableRecords($useMapperNameAsTableName);
            $mapperName = !$useMapperNameAsTableName ? 'Skills' : 'skills';
            $mapper = Piece_ORM_Mapper_Factory::factory($mapperName);
            $skills = $mapper->findAllWithEmployees();

            $this->assertTrue(is_array($skills));
            $this->assertEquals(2, count($skills));

            foreach ($skills as $skill) {
                $this->assertTrue(is_array($skill->employees));

                switch ($skill->name) {
                case 'Foo':
                    $this->assertEquals(2, count($skill->employees));
                    if (count($skill->employees) == 2) {
                        $this->assertEquals('Bar', $skill->employees[0]->firstName);
                        $this->assertEquals('Qux', $skill->employees[1]->firstName);
                    } else {
                        $this->fail('Invalid skills count.');
                    }
                    break;
                case 'Bar':
                    $this->assertEquals(2, count($skill->employees));
                    if (count($skill->employees) == 2) {
                        $this->assertEquals('Baz', $skill->employees[0]->firstName);
                        $this->assertEquals('Qux', $skill->employees[1]->firstName);
                    } else {
                        $this->fail('Invalid skills count.');
                    }
                    break;
                default:
                    $this->fail('Unknown skill name.');
                }
            }

            $this->_clearTableRecords();
            if ($useMapperNameAsTableName) {
                $this->_clearCaseSensitiveContext();
            }
        }
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    public function _insert()
    {
        $mapper = Piece_ORM_Mapper_Factory::factory('Employees');
        $subject = $mapper->createObject();
        $subject->firstName = 'Atsuhiro';
        $subject->lastName = 'Kubo';
        $subject->note = 'Foo';
        return $mapper->insert($subject);
    }

    public function _prepareTableRecords($useMapperNameAsTableName = false)
    {
        $mapperName = !$useMapperNameAsTableName ? 'Skills' : 'skills';
        $skillsMapper = Piece_ORM_Mapper_Factory::factory($mapperName);

        $skill1 = $skillsMapper->createObject();
        $skill1->name = 'Foo';
        $skillsMapper->insert($skill1);

        $skill2 = $skillsMapper->createObject();
        $skill2->name = 'Bar';
        $skillsMapper->insert($skill2);

        $mapperName = !$useMapperNameAsTableName ? 'Departments' : 'departments';
        $departmentsMapper = Piece_ORM_Mapper_Factory::factory($mapperName);

        $department1 = $departmentsMapper->createObject();
        $department1->name = 'Foo';
        $departmentsMapper->insert($department1);

        $department2 = $departmentsMapper->createObject();
        $department2->name = 'Bar';
        $departmentsMapper->insert($department2);

        $mapperName = !$useMapperNameAsTableName ? 'Computers' : 'computers';
        $computersMapper = Piece_ORM_Mapper_Factory::factory($mapperName);

        $computer1 = $computersMapper->createObject();
        $computer1->name = 'Foo';

        $computer2 = $computersMapper->createObject();
        $computer2->name = 'Bar';

        $computer3 = $computersMapper->createObject();
        $computer3->name = 'Baz';

        $mapperName = !$useMapperNameAsTableName ? 'Employees' : 'employees';
        $employeesMapper = Piece_ORM_Mapper_Factory::factory($mapperName);

        $employee1 = $employeesMapper->createObject();
        $employee1->firstName = 'Foo';
        $employee1->lastName = 'Bar';
        $employeesMapper->insert($employee1);

        $employee2 = $employeesMapper->createObject();
        $employee2->firstName = 'Bar';
        $employee2->lastName = 'Baz';
        $employee2->skills = array();
        $employee2->skills[] = $skill1;
        $employee2->departmentsId = $department1->id;
        $employee2->computer = $computer3;
        $employeesMapper->insert($employee2);

        $employee3 = $employeesMapper->createObject();
        $employee3->firstName = 'Baz';
        $employee3->lastName = 'Qux';
        $employee3->skills = array();
        $employee3->skills[] = $skill2;
        $employee3->departmentsId = $department2->id;
        $employee3->computer = $computer2;
        $employeesMapper->insert($employee3);

        $employee4 = $employeesMapper->createObject();
        $employee4->firstName = 'Qux';
        $employee4->lastName = 'Quux';
        $employee4->skills = array();
        $employee4->skills[] = $skill1;
        $employee4->skills[] = $skill2;
        $employee4->departmentsId = $department2->id;
        $employee4->computer = $computer1;
        $employeesMapper->insert($employee4);
    }

    public function _configure($cacheDirectory)
    {
        $this->cacheDirectory = "{$this->cacheDirectory}/$cacheDirectory";
        Piece_ORM_Mapper_Factory::setConfigDirectory($this->cacheDirectory);
        Piece_ORM_Mapper_Factory::setCacheDirectory($this->cacheDirectory);
        Piece_ORM_Metadata_Factory::setCacheDirectory($this->cacheDirectory);
    }

    public function _clearTableRecords()
    {
        $context = Piece_ORM_Context::singleton();
        $dbh = $context->getConnection();
        foreach ($this->tables as $table) {
            $dbh->exec("TRUNCATE TABLE $table");
        }
    }

    public function _prepareCaseSensitiveContext()
    {
        $config = new Piece_ORM_Config();
        $config->setDSN('caseSensitive', $this->dsn);
        $config->setUseMapperNameAsTableName('caseSensitive', true);
        $context = Piece_ORM_Context::singleton();
        $context->setConfiguration($config);
        $context->setDatabase('caseSensitive');
        Piece_ORM_Mapper_Factory::setConfigDirectory("{$this->cacheDirectory}/CaseSensitive");
        Piece_ORM_Mapper_Factory::setCacheDirectory("{$this->cacheDirectory}/CaseSensitive");
        Piece_ORM_Metadata_Factory::setCacheDirectory("{$this->cacheDirectory}/CaseSensitive");
    }

    public function _clearCaseSensitiveContext()
    {
        $cache = new Cache_Lite(array('cacheDir' => "{$this->cacheDirectory}/CaseSensitive/",
                                      'automaticSerialization' => true,
                                      'errorHandlingAPIBreak' => true)
                                );
        $cache->clean();
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
