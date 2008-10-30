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

namespace Piece::ORM;

use Piece::ORM::Metadata;
use Piece::ORM::Exception::PEARException;
use Piece::ORM::Inflector;
use Piece::ORM::Mapper::QueryExecutor;
use Piece::ORM::Mapper::ObjectPersister;
use Piece::ORM::Mapper::Generator;
use Piece::ORM::Mapper::LOB;
use Piece::ORM::Exception;
use Piece::ORM::Mapper::ObjectLoader;
use Piece::ORM::Mapper::Method;
use Piece::ORM::Mapper::QueryType;

// {{{ Piece::ORM::Mapper

/**
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Mapper
{

    // {{{ properties

    /**#@+
     * @access public
     */

    public $mapperID;

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    private $_metadata;
    private $_dbh;
    private $_lastQuery;
    private $_orders = array();
    private $_preloadCallback;
    private $_preloadCallbackArgs;
    private $_lastQueryForGetCount;
    private $_methods = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __call()

    /**
     * @param string $methodName
     * @param array  $arguments
     * @throw Piece::ORM::Exception
     * @since Method available since Release 2.0.0dev1
     */
    public function __call($methodName, $arguments)
    {
        if (!$this->hasMethod($methodName)) {
            throw new Exception("The method [ $methodName ] was not defined");
        }

        $criteria = count($arguments) ? $arguments[0] : null;

        if (QueryType::isFindAll($methodName)) {
            return $this->_findObjects($methodName, $criteria);
        }

        if (QueryType::isFind($methodName)) {
            return $this->_findObject($methodName, $criteria);
        }

        if (QueryType::isInsert($methodName)) {
            return $this->_insertObject($methodName, $criteria);
        }

        if (QueryType::isUpdate($methodName)) {
            return $this->_updateObjects($methodName, $criteria);
        }
    }

    // }}}
    // {{{ __construct()

    /**
     * Sets the ID of the mapper.
     *
     * @param string $mapperID
     */
    public function __construct($mapperID)
    {
        $this->mapperID = $mapperID;
    }

    // }}}
    // {{{ getLastQuery()

    /**
     * Gets the last query of this mapper.
     *
     * @return string
     */
    public function getLastQuery()
    {
        return $this->_lastQuery;
    }

    // }}}
    // {{{ findAllWithQuery()

    /**
     * Finds all objects with a given query.
     *
     * @param string $query
     * @return array
     */
    public function findAllWithQuery($query)
    {
        $result = $this->executeQuery($query);
        return $this->_loadAllObjects($result);
    }

    // }}}
    // {{{ quote()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value
     * @param string $fieldName
     * @return string
     */
    public function quote($value, $fieldName = null)
    {
        if (is_null($fieldName)) {
            return $this->_dbh->quote($value);
        } else {
            return $this->_dbh->quote($value, $this->_metadata->getDatatype($fieldName));
        }
    }

    // }}}
    // {{{ setLimit()

    /**
     * Sets the range of the next query.
     *
     * @param integer $limit
     * @param integer $offset
     * @throws Piece::ORM::Exception::PEARException
     */
    public function setLimit($limit, $offset = null)
    {
        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $result = $this->_dbh->setLimit($limit, $offset);
        ::PEAR::staticPopErrorHandling();
        if (::MDB2::isError($result)) {
            throw new PEARException($result);
        }
    }

    // }}}
    // {{{ addOrder()

    /**
     * Adds an expression as a part of the sort order of the next query.
     *
     * @param string  $expression
     * @param boolean $useDescendingOrder
     */
    public function addOrder($expression, $useDescendingOrder = false)
    {
        $this->_orders[] = "$expression " . (!$useDescendingOrder ? 'ASC' : 'DESC');
    }

    // }}}
    // {{{ setPreloadCallback()

    /**
     * Sets a callback as a preload callback.
     *
     * @param callback $callback
     */
    public function setPreloadCallback($callback)
    {
        $this->_preloadCallback = $callback;
    }

    // }}}
    // {{{ getPreloadCallback()

    /**
     * Gets a preload callback.
     *
     * @return callback
     */
    public function getPreloadCallback()
    {
        return $this->_preloadCallback;
    }

    // }}}
    // {{{ setPreloadCallbackArgs()

    /**
     * Sets arguments which will be pass to a preload callback.
     *
     * @param array $args
     */
    public function setPreloadCallbackArgs($args)
    {
        $this->_preloadCallbackArgs = $args;
    }

    // }}}
    // {{{ getPreloadCallbackArgs()

    /**
     * Sets arguments which will be pass to a preload callback.
     *
     * @param array $args
     */
    public function getPreloadCallbackArgs()
    {
        return $this->_preloadCallbackArgs;
    }

    // }}}
    // {{{ getMetadata()

    /**
     * Gets the metadata object for this mapper.
     *
     * @return Piece::ORM::Metadata
     */
    public function getMetadata()
    {
        return $this->_metadata;
    }

    // }}}
    // {{{ createObject()

    /**
     * Creates an object from the metadata.
     *
     * @return stdClass
     */
    public function createObject()
    {
        $object = new stdClass();
        foreach ($this->_metadata->getFieldNames() as $fieldName) {
            $object->{ Inflector::camelize($fieldName, true) } = null;
        }

        return $object;
    }

    // }}}
    // {{{ executeQuery()

    /**
     * Executes a query.
     *
     * @param string                $query
     * @param boolean               $isManip
     * @param ::MDB2_Statement_Common $sth
     * @return ::MDB2_Result_Common|integer
     */
    public function executeQuery($query, $isManip = false, $sth = null)
    {
        $queryExecutor = new QueryExecutor($this, $isManip);
        return $queryExecutor->execute($query, $sth);
    }

    // }}}
    // {{{ findWithQuery()

    /**
     * Finds an object with a given query.
     *
     * @param string $query
     * @return stdClass
     */
    public function findWithQuery($query)
    {
        $objects = $this->findAllWithQuery($query);
        if (!count($objects)) {
            return;
        }

        return $objects[0];
    }

    // }}}
    // {{{ executeQueryWithCriteria()

    /**
     * Executes a query with a given criteria.
     *
     * @param string   $methodName
     * @param stdClass $criteria
     * @param boolean  $isManip
     * @return ::MDB2_Result_Common|integer
     * @throws Piece::ORM::Exception
     */
    public function executeQueryWithCriteria($methodName, $criteria, $isManip = false)
    {
        if (!$this->hasMethod($methodName)) {
            throw new Exception("The method [ $methodName ] was not defined");
        }

        $queryExecutor = new QueryExecutor($this, $isManip);
        return $queryExecutor->executeWithCriteria($methodName, $criteria);
    }

    // }}}
    // {{{ setConnection()

    /**
     * Sets the database handle for this mapper.
     *
     * @param ::MDB2_Driver_Common $dbh
     */
    public function setConnection(::MDB2_Driver_Common $dbh)
    {
        $this->_dbh = $dbh;
    }

    // }}}
    // {{{ getCount()

    /**
     * Gets the number of rows a query would have returned without a LIMIT clause in
     * the latest findAll method execution.
     *
     * @return integer
     * @since Method available since Release 0.3.0
     */
    public function getCount()
    {
        if (!is_null($this->_lastQueryForGetCount)) {
            return $this->findOneWithQuery(preg_replace('/^\s*SELECT\s+.+?\s+FROM\s+(.+)\s*$/is',
                                                        'SELECT COUNT(*) FROM $1',
                                                        $this->_lastQueryForGetCount)
                                           );
        }
    }

    // }}}
    // {{{ findOneWithQuery()

    /**
     * Finds the value from the first column of the first row of the result set with
     * a given query.
     *
     * @param string $query
     * @return array
     * @since Method available since Release 0.3.0
     */
    public function findOneWithQuery($query)
    {
        $result = $this->executeQuery($query);
        return $this->_loadValue($result);
    }

    // }}}
    // {{{ createLOB()

    /**
     * Creates a LOB object.
     *
     * @param string $source
     * @return Piece::ORM::Mapper::LOB
     */
    public function createLOB($source = null)
    {
        return new LOB($this->_dbh, $this->_metadata, $source);
    }

    // }}}
    // {{{ getQuery()

    /**
     * Gets the query for a given method name.
     *
     * @param string $methodName
     * @return string
     * @since Method available since Release 1.1.0
     */
    public function getQuery($methodName)
    {
        if (!$this->hasMethod($methodName)) {
            throw new Exception("The method [ $methodName ] was not defined");
        }

        return $this->_methods[$methodName]->getQuery();
    }

    // }}}
    // {{{ getConnection()

    /**
     * Gets the database handle for this mapper.
     *
     * @return ::MDB2_Driver_Common
     * @since Method available since Release 1.1.0
     */
    public function getConnection()
    {
        return $this->_dbh;
    }

    // }}}
    // {{{ setLastQueryForGetCount()

    /**
     * Gets the last query for the next getCount() call.
     *
     * @param string $lastQueryForGetCount
     * @since Method available since Release 1.1.0
     */
    public function setLastQueryForGetCount($lastQueryForGetCount)
    {
        $this->_lastQueryForGetCount = $lastQueryForGetCount;
    }

    // }}}
    // {{{ getOrderBy()

    /**
     * Gets the order by clause for the next query.
     *
     * @param string $methodName
     * @throws Piece::ORM::Exception
     * @since Method available since Release 1.1.0
     */
    public function getOrderBy($methodName)
    {
        if (!$this->hasMethod($methodName)) {
            throw new Exception("The method [ $methodName ] was not defined");
        }

        if (count($this->_orders)) {
            return ' ORDER BY ' . implode(', ', $this->_orders);
        }

        $orderBy = $this->_methods[$methodName]->getOrderBy();
        if (is_null($orderBy)) {
            return;
        }

        return " ORDER BY $orderBy";
    }

    // }}}
    // {{{ clearOrders()

    /**
     * Clears the sort order of the next query.
     *
     * @since Method available since Release 1.1.0
     */
    public function clearOrders()
    {
        $this->_orders = array();
    }

    // }}}
    // {{{ setLastQuery()

    /**
     * Sets the last query of this mapper.
     *
     * @param string $lastQuery
     * @since Method available since Release 1.1.0
     */
    public function setLastQuery($lastQuery)
    {
        $this->_lastQuery = $lastQuery;
    }

    // }}}
    // {{{ getDefault()

    /**
     * Gets the default value of a given field.
     *
     * @param string $fieldName
     * @return mixed
     * @since Method available since Release 1.2.0
     */
    public function getDefault($fieldName)
    {
        return $this->_metadata->getDefault($this->_metadata->getFieldNameByAlias(strtolower($fieldName)));
    }

    // }}}
    // {{{ setMetadata()

    /**
     * Sets the Piece::ORM::Metadata object for this mapper.
     *
     * @param Piece::ORM::Metadata $metadata
     * @since Method available since Release 2.0.0dev1
     */
    public function setMetadata(Metadata $metadata)
    {
        $this->_metadata = $metadata;
    }

    // }}}
    // {{{ addMethod()

    /**
     * @param Piece::ORM::Mapper::Method $method
     * @since Method available since Release 2.0.0dev1
     */
    public function addMethod(Method $method)
    {
        $this->_methods[ $method->getName() ] = $method;
    }

    // }}}
    // {{{ hasMethod()

    /**
     * @param string $methodName
     * @return boolean
     * @since Method available since Release 2.0.0dev1
     */
    public function hasMethod($methodName)
    {
        return array_key_exists($methodName, $this->_methods);
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _loadAllObjects()

    /**
     * Loads all objects with a result object.
     *
     * @param ::MDB2_Result $result
     * @param array         $associations
     * @return array
     */
    private function _loadAllObjects(::MDB2_Result $result, $associations = array())
    {
        $loader = new ObjectLoader($this, $result, $associations);
        $objects = $loader->loadAll();
        $this->_loadCallback = null;
        return $objects;
    }

    // }}}
    // {{{ _createCriteria()

    /**
     * Creates a criteria object from a method name and a value as a criterion.
     *
     * @param string $methodName
     * @param mixed  $criterion
     * @return stdClass
     * @throws Piece::ORM::Exception
     */
    private function _createCriteria($methodName, $criterion)
    {
        if (!preg_match('/By(.+)$/', $methodName, $matches)) {
            throw new Exception("An unexpected value detected. $methodName() can only receive object or null. Or the method name does not contain the appropriate field name.");
        }

        $criteria = new stdClass();
        $criteria->{ Inflector::lowercaseFirstLetter($matches[1]) } = $criterion;
        return $criteria;
    }

    /**
     * Loads a value with a result object.
     *
     * @param ::MDB2_Result $result
     * @return string
     * @throws Piece::ORM::Exception::PEARException
     * @since Method available since Release 0.3.0
     */
    private function _loadValue(::MDB2_Result $result)
    {
        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $value = $result->fetchOne();
        ::PEAR::staticPopErrorHandling();
        if (::MDB2::isError($value)) {
            throw new PEARException($value);
        }

        return $value;
    }

    // }}}
    // {{{ _findObject()

    /**
     * Finds an object with an appropriate query generated by a given criteria.
     *
     * @param string $methodName
     * @param mixed  $criteria
     * @return stdClass
     */
    private function _findObject($methodName, $criteria)
    {
        $objects = $this->_findObjects($methodName, $criteria);
        if (!count($objects)) {
            return;
        }

        return $objects[0];
    }

    // }}}
    // {{{ _findObjects()

    /**
     * Finds all objects with an appropriate query generated by a given criteria.
     *
     * @param string   $methodName
     * @param stdClass $criteria
     * @return array
     * @throws Piece::ORM::Exception
     */
    private function _findObjects($methodName, $criteria)
    {
        if (is_null($criteria)) {
            $criteria = new stdClass();
        }

        if (!is_object($criteria)) {
            if ($methodName == 'findAll') {
                throw new Exception('An unexpected value detected. findAll() can only receive object or null.');
            }

            $criteria = $this->_createCriteria($methodName, $criteria);
        }

        $result = $this->executeQueryWithCriteria($methodName, $criteria);
        return $this->_loadAllObjects($result,
                                      $this->_methods[$methodName]->getAssociations()
                                      );
    }

    // }}}
    // {{{ _findValue()

    /**
     * Finds the value from the first column of the first row of the result set with
     * an appropriate query generated by a given criteria.
     *
     * @param string   $methodName
     * @param stdClass $criteria
     * @return array
     * @since Method available since Release 0.3.0
     */
    private function _findValue($methodName, $criteria)
    {
        if (is_null($criteria)) {
            $criteria = new stdClass();
        }

        if (!is_object($criteria)) {
            $criteria = $this->_createCriteria($methodName, $criteria);
        }

        $result = $this->executeQueryWithCriteria($methodName, $criteria);
        return $this->_loadValue($result);
    }

    // }}}
    // {{{ _insertObject()

    /**
     * Inserts an object to a table.
     *
     * @param string $methodName
     * @param mixed  $subject
     * @return integer
     */
    private function _insertObject($methodName, $subject)
    {
        $persister = new ObjectPersister($this,
                                         $subject,
                                         $this->_methods[$methodName]->getAssociations()
                                         );
        return $persister->insert($methodName);
    }

    // }}}
    // {{{ _deleteObjects()

    /**
     * Removes objects from a table.
     *
     * @param string $methodName
     * @param mixed  $subject
     * @return integer
     */
    private function _deleteObjects($methodName, $subject)
    {
        $persister = new ObjectPersister($this,
                                         $subject,
                                         $this->_methods[$methodName]->getAssociations()
                                         );
        return $persister->delete($methodName);
    }

    // }}}
    // {{{ _updateObjects()

    /**
     * Updates objects in a table.
     *
     * @param string $methodName
     * @param mixed  $subject
     * @return integer
     */
    private function _updateObjects($methodName, $subject)
    {
        $persister = new ObjectPersister($this,
                                         $subject,
                                         $this->_methods[$methodName]->getAssociations()
                                         );
        return $persister->update($methodName);
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
