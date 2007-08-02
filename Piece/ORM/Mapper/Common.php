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

require_once 'Piece/ORM/Inflector.php';
require_once 'Piece/ORM/Error.php';
require_once 'MDB2.php';
require_once 'Piece/ORM/Mapper/ObjectLoader.php';
require_once 'PEAR.php';
require_once 'Piece/ORM/Mapper/ObjectPersister.php';

// {{{ Piece_ORM_Mapper_Common

/**
 * The base class for mappers.
 *
 * @package    Piece_ORM
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
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
     * @access private
     */

    var $_metadata;
    var $_dbh;
    var $_lastQuery;
    var $_orders = array();
    var $_loadedObjects = array();
    var $_preloadCallback;
    var $_preloadCallbackArgs;
    var $_useIdentityMap = true;
    var $_lastQueryForGetCount;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Sets the Piece_ORM_Metadata object for this mapper.
     *
     * @param Piece_ORM_Metadata &$metadata
     */
    function Piece_ORM_Mapper_Common(&$metadata)
    {
        $this->_metadata = &$metadata;
    }

    // }}}
    // {{{ getLastQuery()

    /**
     * Gets the last query of this mapper.
     *
     * @return string
     */
    function getLastQuery()
    {
        return $this->_lastQuery;
    }

    // }}}
    // {{{ findAllWithQuery()

    /**
     * Finds all objects with the given query.
     *
     * @param string $query
     * @return array
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function findAllWithQuery($query)
    {
        $result = &$this->executeQuery($query);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $objects = $this->_loadAllObjects($result);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        return $objects;
    }

    // }}}
    // {{{ getDriverName()

    /**
     * Gets the driver name of the database handle for this mapper.
     *
     * @return string
     */
    function getDriverName()
    {
        return substr(strrchr(get_class($this->_dbh), '_'), 1);
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
    function quote($value, $fieldName)
    {
        return $this->_dbh->quote($value, $this->_metadata->getDatatype($fieldName));
    }

    // }}}
    // {{{ setLimit()

    /**
     * Sets the range of the next query.
     *
     * @param integer $limit
     * @param integer $offset
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function setLimit($limit, $offset = null)
    {
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $result = $this->_dbh->setLimit($limit, $offset);
        PEAR::staticPopErrorHandling();
        if (MDB2::isError($result)) {
            Piece_ORM_Error::pushPEARError($result,
                                           PIECE_ORM_ERROR_INVOCATION_FAILED,
                                           'Failed to invoke MDB2_Driver_' . $this->getDriverName() . '::setLimit() for any reasons.'
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
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function addOrder($expression, $useDescendingOrder = false)
    {
        $this->_orders[] = "$expression " . (!$useDescendingOrder ? 'ASC' : 'DESC');
    }

    // }}}
    // {{{ getLoadedObject()

    /**
     * Gets a loaded object corresponding to the given primary key value.
     *
     * @param mixed $primaryKeyValue
     * @return stdClass
     */
    function &getLoadedObject($primaryKeyValue)
    {
        return $this->_loadedObjects[$primaryKeyValue];
    }

    // }}}
    // {{{ addLoadedObject()

    /**
     * Adds an object to the list of the loaded objects.
     *
     * @param string   $primaryKeyValue
     * @param stdClass &$object
     */
    function addLoadedObject($primaryKeyValue, &$object)
    {
        $this->_loadedObjects[$primaryKeyValue] = &$object;
    }

    // }}}
    // {{{ setPreloadCallback()

    /**
     * Sets a callback as a preload callback.
     *
     * @param callback $callback
     */
    function setPreloadCallback($callback)
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
    function getPreloadCallback()
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
    function setPreloadCallbackArgs($args)
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
    function getPreloadCallbackArgs()
    {
        return $this->_preloadCallbackArgs;
    }

    // }}}
    // {{{ getPreloadCallbackArgs()

    /**
     * Gets the metadata object for this mapper.
     *
     * @return Piece_ORM_Metadata
     */
    function &getMetadata()
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
    function &createObject()
    {
        $object = &new stdClass();
        foreach ($this->_metadata->getFieldNames() as $fieldName) {
            $object->{ Piece_ORM_Inflector::camelize($fieldName, true) } = null;
        }

        return $object;
    }

    // }}}
    // {{{ setUseIdentityMap()

    /**
     * Sets whether use identity map or not for the next query.
     *
     * @param boolean $useIdentityMap
     */
    function setUseIdentityMap($useIdentityMap)
    {
        $this->_useIdentityMap = $useIdentityMap;
    }

    // }}}
    // {{{ executeQuery()

    /**
     * Executes a query.
     *
     * @param string  $query
     * @param boolean $isManip
     * @return mixed
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function &executeQuery($query, $isManip = false)
    {
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        if (!$isManip) {
            $result = &$this->_dbh->query($query);
        } else {
            $result = $this->_dbh->exec($query);
        }
        PEAR::staticPopErrorHandling();

        $this->_lastQuery = $this->_dbh->last_query;
        $this->_lastQueryForGetCount = null;

        if (MDB2::isError($result)) {
            Piece_ORM_Error::pushPEARError($result,
                                           PIECE_ORM_ERROR_INVOCATION_FAILED,
                                           'Failed to invoke MDB2_Driver_' . $this->getDriverName() . '::query() for any reasons.'
                                           );
            $return = null;
            return $return;
        }

        return $result;
    }

    // }}}
    // {{{ findWithQuery()

    /**
     * Finds an object with the given query.
     *
     * @param string $query
     * @return stdClass
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function &findWithQuery($query)
    {
        $objects = $this->findAllWithQuery($query);
        if (Piece_ORM_Error::hasErrors('exception')) {
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
     * Executes a query with the given criteria.
     *
     * @param string   $methodName
     * @param stdClass $criteria
     * @param boolean  $isManip
     * @return mixed
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function &executeQueryWithCriteria($methodName, $criteria, $isManip = false)
    {
        $query = $this->_buildQuery($methodName, $criteria);
        if (Piece_ORM_Error::hasErrors('exception')) {
            $return = null;
            return $return;
        }

        $queryForGetCount = $query;

        if (!$isManip && count($this->_orders)) {
            $query .= ' ORDER BY ' . implode(', ', $this->_orders);
            $this->_orders = array();
        }

        $result = &$this->executeQuery($query, $isManip);
        if (preg_match('/^findAll.*$/', $methodName)) {
            $this->_lastQueryForGetCount = $queryForGetCount;
        }

        if (Piece_ORM_Error::hasErrors('exception')) {
            $return = null;
            return $return;
        }

        return $result;
    }

    // }}}
    // {{{ getLastInsertID()

    /**
     * Returns the value of an ID field if a table has an ID field.
     *
     * @return integer
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function getLastInsertID()
    {
        if ($this->_metadata->hasID()) {
            PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
            $id = $this->_dbh->lastInsertID($this->_metadata->getTableName(), $this->_metadata->getPrimaryKey());
            PEAR::staticPopErrorHandling();
            if (MDB2::isError($id)) {
                Piece_ORM_Error::pushPEARError($id,
                                               PIECE_ORM_ERROR_INVOCATION_FAILED,
                                               'Failed to invoke MDB2_Driver_' . $this->getDriverName() . '::lastInsertID() for any reasons.'
                                               );
                return;
            }

            return $id;
        }
    }

    // }}}
    // {{{ removeLoadedObject()

    /**
     * Removes an object from the list of the loaded objects.
     *
     * @param string $primaryKeyValue
     */
    function removeLoadedObject($primaryKeyValue)
    {
        unset($this->_loadedObjects[$primaryKeyValue]);
    }

    // }}}
    // {{{ setConnection()

    /**
     * Sets the database handle for this mapper.
     *
     * @param mixed &$dbh
     */
    function setConnection(&$dbh)
    {
        $this->_dbh = &$dbh;
    }

    // }}}
    // {{{ getCount()

    /**
     * Gets the number of rows a query would have returned without a LIMIT
     * clause in the latest findAll method execution.
     *
     * @return integer
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @since Method available since Release 0.3.0
     */
    function getCount()
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
     * Finds the value from the first column of the first row of the result
     * set with the given query.
     *
     * @param string $query
     * @return array
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @since Method available since Release 0.3.0
     */
    function findOneWithQuery($query)
    {
        $result = &$this->executeQuery($query);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        return $this->_loadValue($result);
    }

    // }}}
    // {{{ useIdentityMap()

    /**
     * Returns whether use identity map or not for the next query.
     *
     * @return boolean
     */
    function useIdentityMap()
    {
        return $this->_useIdentityMap;
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _find()

    /**
     * Finds an object with an appropriate query generated by the given
     * criteria.
     *
     * @param string $methodName
     * @param mixed  $criteria
     * @return stdClass
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function &_find($methodName, $criteria)
    {
        if (is_null($criteria)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  "An unexpected value detected. $methodName() cannot receive null."
                                  );
            $return = null;
            return $return;
        }

        $objects = $this->_findAll($methodName, $criteria);
        if (Piece_ORM_Error::hasErrors('exception')) {
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
    // {{{ _buildQuery()

    /**
     * Builds a query based on a query source and criteria.
     *
     * @param string   $methodName
     * @param stdClass $criteria
     * @return string
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _buildQuery($methodName, $criteria)
    {
        if (version_compare(phpversion(), '5.0.0', '>=')) {
            $criteria = clone($criteria);
        }

        foreach ($criteria as $key => $value) {
            if (is_scalar($value) || is_null($value)) {
                $criteria->$key = $this->_dbh->quote($value);
            } else {
                unset($criteria->$key);
            }
        }

        extract((array)$criteria);

        foreach ($criteria as $key => $value) {
            if ($value == 'NULL') {
                $criteria->$key = null;
            }
        }

        $query = '__query__' . strtolower($methodName);

        ob_start();
        eval("\$query = \"{$this->$query}\";");
        $contents = ob_get_contents();
        ob_end_clean();
        if (strlen($contents)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVOCATION_FAILED,
                                  "Failed to build a query for any reasons. See below for more details.
 $contents");
            return;
        }

        return $query;
    }

    // }}}
    // {{{ _loadAllObjects()

    /**
     * Loads all objects with a result object.
     *
     * @param MDB2_Result &$result
     * @param array       $relationships
     * @return array
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _loadAllObjects(&$result, $relationships = array())
    {
        $loader = &new Piece_ORM_Mapper_ObjectLoader($this, $result, $relationships, $this->_useIdentityMap);
        $objects = $loader->loadAll();
        $this->_loadCallback = null;
        return $objects;
    }

    // }}}
    // {{{ _findAll()

    /**
     * Finds all objects with an appropriate query generated by the given
     * criteria.
     *
     * @param string   $methodName
     * @param stdClass $criteria
     * @return array
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _findAll($methodName, $criteria)
    {
        if (is_null($criteria)) {
            $criteria = &new stdClass();
        }

        if (!is_object($criteria)) {
            if ($methodName == 'findAll') {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An unexpected value detected. findAll() can only receive object or null.'
                                      );
                return;
            }

            $criteria = &$this->_createCriteria($methodName, $criteria);
            if (Piece_ORM_Error::hasErrors('exception')) {
                return;
            }
        }

        $result = &$this->executeQueryWithCriteria($methodName, $criteria);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $objects = $this->_loadAllObjects($result, $this->{ '__relationship__' . strtolower($methodName) });
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        return $objects;
    }

    // }}}
    // {{{ _createCriteria()

    /**
     * Creates a criteria object from a method name and a value as
     * a criterion.
     *
     * @param string $methodName
     * @param mixed  $criterion
     * @return stdClass
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     */
    function &_createCriteria($methodName, $criterion)
    {
        if (preg_match('/By(.+)$/', $methodName, $matches)) {
            $criteria = &new stdClass();
            $criteria->{ Piece_ORM_Inflector::lowercaseFirstLetter($matches[1]) } = $criterion;
            return $criteria;
        } else {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  "An unexpected value detected. $method() can only receive object or null."
                                  );
            $return = null;
            return $return;
        }
    }

    // }}}
    // {{{ _findOne()

    /**
     * Finds the value from the first column of the first row of the result
     * set with an appropriate query generated by the given criteria.
     *
     * @param string   $methodName
     * @param stdClass $criteria
     * @return array
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @since Method available since Release 0.3.0
     */
    function _findOne($methodName, $criteria)
    {
        if (is_null($criteria)) {
            $criteria = &new stdClass();
        }

        if (!is_object($criteria)) {
            $criteria = &$this->_createCriteria($methodName, $criteria);
            if (Piece_ORM_Error::hasErrors('exception')) {
                return;
            }
        }

        $result = &$this->executeQueryWithCriteria($methodName, $criteria);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        return $this->_loadValue($result);
    }

    /**
     * Loads a value with a result object.
     *
     * @param MDB2_Result &$result
     * @return string
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @since Method available since Release 0.3.0
     */
    function _loadValue(&$result)
    {
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $value = $result->fetchOne();
        PEAR::staticPopErrorHandling();
        if (MDB2::isError($value)) {
            Piece_ORM_Error::pushPEARError($value,
                                           PIECE_ORM_ERROR_INVOCATION_FAILED,
                                           'Failed to invoke MDB2_Driver_' . $this->getDriverName() . '::fetchOne() for any reasons.'
                                           );
            return;
        }

        return $value;
    }

    // }}}
    // {{{ _insert()

    /**
     * Inserts an object to a table.
     *
     * @param string $methodName
     * @param mixed &$subject
     * @return mixed
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     */
    function _insert($methodName, &$subject)
    {
        $persister = &new Piece_ORM_Mapper_ObjectPersister($this, $subject, $this->{ '__relationship__' . strtolower($methodName) });
        return $persister->insert($methodName);
    }

    // }}}
    // {{{ _delete()

    /**
     * Removes an object from a table.
     *
     * @param string $methodName
     * @param mixed &$subject
     * @return integer
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     */
    function _delete($methodName, &$subject)
    {
        $persister = &new Piece_ORM_Mapper_ObjectPersister($this, $subject, $this->{ '__relationship__' . strtolower($methodName) });
        return $persister->delete($methodName);
    }

    // }}}
    // {{{ _update()

    /**
     * Updates an object in a table.
     *
     * @param string $methodName
     * @param mixed &$subject
     * @return integer
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     */
    function _update($methodName, &$subject)
    {
        $persister = &new Piece_ORM_Mapper_ObjectPersister($this, $subject, $this->{ '__relationship__' . strtolower($methodName) });
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
?>
