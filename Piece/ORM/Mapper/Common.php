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

// {{{ Piece_ORM_Mapper_Common

/**
 * The base class for mappers.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Mapper_Common
{

    // {{{ properties

    /**#@+
     * @access public
     */

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

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Sets the Piece_ORM_Metadata object for this mapper.
     *
     * @param Piece_ORM_Metadata $metadata
     */
    public function __construct(Piece_ORM_Metadata $metadata)
    {
        $this->_metadata = $metadata;
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
        if (Piece_ORM_Error::hasErrors()) {
            return;
        }

        $objects = $this->_loadAllObjects($result);
        if (Piece_ORM_Error::hasErrors()) {
            return;
        }

        return $objects;
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
     * @throws PIECE_ORM_ERROR_CANNOT_INVOKE
     */
    public function setLimit($limit, $offset = null)
    {
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $result = $this->_dbh->setLimit($limit, $offset);
        PEAR::staticPopErrorHandling();
        if (MDB2::isError($result)) {
            Piece_ORM_Error::pushPEARError($result,
                                           PIECE_ORM_ERROR_CANNOT_INVOKE,
                                           "Failed to invoke MDB2_Driver_{$this->_dbh->phptype}::setLimit() for any reasons."
                                           );
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
     * @return Piece_ORM_Metadata
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
            $object->{ Piece_ORM_Inflector::camelize($fieldName, true) } = null;
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
     * @param MDB2_Statement_Common $sth
     * @return MDB2_Result_Common|integer
     */
    public function executeQuery($query, $isManip = false, MDB2_Statement_Common $sth = null)
    {
        $queryExecutor = new Piece_ORM_Mapper_QueryExecutor($this, $isManip);
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
        if (Piece_ORM_Error::hasErrors()) {
            return;
        }

        if (count($objects)) {
            return $objects[0];
        } else {
            $return = null;
            return $return;
        }
    }

    // }}}
    // {{{ executeQueryWithCriteria()

    /**
     * Executes a query with a given criteria.
     *
     * @param string   $methodName
     * @param stdClass $criteria
     * @param boolean  $isManip
     * @return MDB2_Result_Common|integer
     */
    public function executeQueryWithCriteria($methodName, $criteria, $isManip = false)
    {
        $queryExecutor = new Piece_ORM_Mapper_QueryExecutor($this, $isManip);
        return $queryExecutor->executeWithCriteria($methodName, $criteria);
    }

    // }}}
    // {{{ setConnection()

    /**
     * Sets the database handle for this mapper.
     *
     * @param MDB2_Driver_Common $dbh
     */
    public function setConnection(MDB2_Driver_Common $dbh)
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
        if (Piece_ORM_Error::hasErrors()) {
            return;
        }

        return $this->_loadValue($result);
    }

    // }}}
    // {{{ createLOB()

    /**
     * Creates a LOB object.
     *
     * @param string $source
     * @return Piece_ORM_Mapper_LOB
     */
    public function createLOB($source = null)
    {
        $lob = new Piece_ORM_Mapper_LOB($this->_dbh, $this->_metadata, $source);
        return $lob;
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
        return $this->{ Piece_ORM_Mapper_Generator::getQueryProperty($methodName) };
    }

    // }}}
    // {{{ getConnection()

    /**
     * Gets the database handle for this mapper.
     *
     * @return MDB2_Driver_Common
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
     * @since Method available since Release 1.1.0
     */
    public function getOrderBy($methodName)
    {
        if (count($this->_orders)) {
            return ' ORDER BY ' . implode(', ', $this->_orders);
        }

        if (!is_null($this->{ Piece_ORM_Mapper_Generator::getOrderByProperty($methodName) })) {
            return ' ORDER BY ' . $this->{ Piece_ORM_Mapper_Generator::getOrderByProperty($methodName) };
        }
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

    /**#@-*/

    /**#@+
     * @access protected
     */

    // }}}
    // {{{ findObject()

    /**
     * Finds an object with an appropriate query generated by a given criteria.
     *
     * @param string $methodName
     * @param mixed  $criteria
     * @return stdClass
     */
    protected function findObject($methodName, $criteria)
    {
        $objects = $this->findObjects($methodName, $criteria);
        if (Piece_ORM_Error::hasErrors()) {
            $return = null;
            return $return;
        }

        if (count($objects)) {
            return $objects[0];
        } else {
            $return = null;
            return $return;
        }
    }

    // }}}
    // {{{ findObjects()

    /**
     * Finds all objects with an appropriate query generated by a given criteria.
     *
     * @param string   $methodName
     * @param stdClass $criteria
     * @return array
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     */
    protected function findObjects($methodName, $criteria)
    {
        if (is_null($criteria)) {
            $criteria = new stdClass();
        }

        if (!is_object($criteria)) {
            if ($methodName == 'findAll') {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An unexpected value detected. findAll() can only receive object or null.'
                                      );
                return;
            }

            $criteria = $this->_createCriteria($methodName, $criteria);
            if (Piece_ORM_Error::hasErrors()) {
                return;
            }
        }

        $result = $this->executeQueryWithCriteria($methodName, $criteria);
        if (Piece_ORM_Error::hasErrors()) {
            return;
        }

        $objects = $this->_loadAllObjects($result, $this->{ '__relationship__' . strtolower($methodName) });
        if (Piece_ORM_Error::hasErrors()) {
            return;
        }

        return $objects;
    }

    // }}}
    // {{{ findValue()

    /**
     * Finds the value from the first column of the first row of the result set with
     * an appropriate query generated by a given criteria.
     *
     * @param string   $methodName
     * @param stdClass $criteria
     * @return array
     * @since Method available since Release 0.3.0
     */
    protected function findValue($methodName, $criteria)
    {
        if (is_null($criteria)) {
            $criteria = new stdClass();
        }

        if (!is_object($criteria)) {
            $criteria = $this->_createCriteria($methodName, $criteria);
            if (Piece_ORM_Error::hasErrors()) {
                return;
            }
        }

        $result = $this->executeQueryWithCriteria($methodName, $criteria);
        if (Piece_ORM_Error::hasErrors()) {
            return;
        }

        return $this->_loadValue($result);
    }

    // }}}
    // {{{ insertObject()

    /**
     * Inserts an object to a table.
     *
     * @param string $methodName
     * @param mixed  $subject
     * @return integer
     */
    protected function insertObject($methodName, $subject)
    {
        $persister = new Piece_ORM_Mapper_ObjectPersister($this, $subject, $this->{ '__relationship__' . strtolower($methodName) });
        return $persister->insert($methodName);
    }

    // }}}
    // {{{ deleteObjects()

    /**
     * Removes objects from a table.
     *
     * @param string $methodName
     * @param mixed  $subject
     * @return integer
     */
    protected function deleteObjects($methodName, $subject)
    {
        $persister = new Piece_ORM_Mapper_ObjectPersister($this, $subject, $this->{ '__relationship__' . strtolower($methodName) });
        return $persister->delete($methodName);
    }

    // }}}
    // {{{ updateObjects()

    /**
     * Updates objects in a table.
     *
     * @param string $methodName
     * @param mixed  $subject
     * @return integer
     */
    protected function updateObjects($methodName, $subject)
    {
        $persister = new Piece_ORM_Mapper_ObjectPersister($this, $subject, $this->{ '__relationship__' . strtolower($methodName) });
        return $persister->update($methodName);
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _loadAllObjects()

    /**
     * Loads all objects with a result object.
     *
     * @param MDB2_Result $result
     * @param array       $relationships
     * @return array
     */
    private function _loadAllObjects(MDB2_Result $result, $relationships = array())
    {
        $loader = new Piece_ORM_Mapper_ObjectLoader($this, $result, $relationships);
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
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     */
    private function _createCriteria($methodName, $criterion)
    {
        if (preg_match('/By(.+)$/', $methodName, $matches)) {
            $criteria = new stdClass();
            $criteria->{ Piece_ORM_Inflector::lowercaseFirstLetter($matches[1]) } = $criterion;
            return $criteria;
        } else {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  "An unexpected value detected. $methodName() can only receive object or null. Or the method name does not contain the appropriate field name."
                                  );
            $return = null;
            return $return;
        }
    }

    /**
     * Loads a value with a result object.
     *
     * @param MDB2_Result $result
     * @return string
     * @throws PIECE_ORM_ERROR_CANNOT_INVOKE
     * @since Method available since Release 0.3.0
     */
    private function _loadValue(MDB2_Result $result)
    {
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $value = $result->fetchOne();
        PEAR::staticPopErrorHandling();
        if (MDB2::isError($value)) {
            Piece_ORM_Error::pushPEARError($value,
                                           PIECE_ORM_ERROR_CANNOT_INVOKE,
                                           "Failed to invoke MDB2_Driver_{$this->_dbh->phptype}::fetchOne() for any reasons."
                                           );
            return;
        }

        return $value;
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
