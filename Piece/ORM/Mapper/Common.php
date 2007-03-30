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
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/ORM/Inflector.php';
require_once 'Piece/ORM/Error.php';
require_once 'MDB2.php';

// {{{ Piece_ORM_Mapper_Common

/**
 * The base class for mappers.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
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

    var $_context;
    var $_metadata;
    var $_dbh;
    var $_lastQuery;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Sets the Piece_ORM_Context object and a Piece_ORM_Metadata object as
     * property.
     *
     * @param Piece_ORM_Context  &$context
     * @param Piece_ORM_Metadata &$metadata
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function Piece_ORM_Mapper_Common(&$context, &$metadata)
    {
        $this->_context = &$context;
        $this->_metadata = &$metadata;
        $this->_dbh = &$this->_context->getConnection();
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
    // {{{ insert()

    /**
     * Inserts an object to a table.
     *
     * @param mixed $subject
     * @return integer
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function insert($subject)
    {
        if (is_null($subject)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  "An unexpected value detected. insert() cannot receive null."
                                  );
            return;
        }

        if (!is_object($subject)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  "An unexpected value detected. insert() can only receive object."
                                  );
            return;
        }

        $this->_executeQueryWithCriteria(__FUNCTION__, $subject, true);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        if ($this->_metadata->hasID()) {
            PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
            $primaryKey = $this->_metadata->getPrimaryKey();
            $id = $this->_dbh->lastInsertID($this->_metadata->getTableName(), $primaryKey[0]);
            PEAR::staticPopErrorHandling();
            if (MDB2::isError($id)) {
                Piece_ORM_Error::pushPEARError($id,
                                               PIECE_ORM_ERROR_INVOCATION_FAILED,
                                               'Failed to invoke MDB2_Driver_' . $this->_getDriverName() . '::lastInsertID() for any reasons.'
                                               );
                return;
            }

            return $id;
        }
    }

    // }}}
    // {{{ delete()

    /**
     * Removes an object from a table.
     *
     * @param mixed $criteria
     * @return integer
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function delete($criteria)
    {
        if (!$this->_metadata->hasPrimaryKey()) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_OPERATION,
                                  'The primary key required to invoke update().'
                                  );
            return;
        }

        if (is_null($criteria)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  'An unexpected value detected. delete() cannot receive null.'
                                  );
            return;
        }

        if (!is_object($criteria)) {
            if (!is_scalar($criteria)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An unexpected value detected. delete() cannot receive non-scalar.'
                                      );
                return;
            }

            if (!strlen($criteria)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An unexpected value detected. delete() cannot receive empty string.'
                                      );
                return;
            }

            if (!$this->_metadata->hasComplexPrimaryKey()) {
                $primaryKey = $this->_metadata->getPrimaryKey();
                $propertyName = Piece_ORM_Inflector::camelize($primaryKey[0], true);
                $criterion = $criteria;
                $criteria = &new stdClass();
                $criteria->$propertyName = $criterion;
            } else {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An unexpected value detected. delete() can receive non-object only if a table has a single primary key and has not a complex primary key.'
                                      );
                return;
            }
        }

        foreach ($this->_metadata->getPrimaryKey() as $primaryKey) {
            $propertyName = Piece_ORM_Inflector::camelize($primaryKey, true);
            if (!array_key_exists($propertyName, $criteria)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'The primary key not found in the given value.'
                                      );
                return;
            }

            if (!is_scalar($criteria->$propertyName)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An inappropriate value for the primary key detected.'
                                      );
                return;
            }

            if (!strlen($criteria->$propertyName)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An inappropriate value for the primary key detected.'
                                      );
                return;
            }
        }

        $affectedRows = $this->_executeQueryWithCriteria(__FUNCTION__, $criteria, true);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        return $affectedRows;
    }

    // }}}
    // {{{ update()

    /**
     * Updates an object in a table.
     *
     * @param mixed $subject
     * @return integer
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function update($subject)
    {
        if (!$this->_metadata->hasPrimaryKey()) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_OPERATION,
                                  'The primary key required to invoke update().'
                                  );
            return;
        }

        if (is_null($subject)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  'An unexpected value detected. update() cannot receive null.'
                                  );
            return;
        }

        if (!is_object($subject)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  'An unexpected value detected. update() cannot receive non-object.'
                                  );
            return;
        }

        foreach ($this->_metadata->getPrimaryKey() as $primaryKey) {
            $propertyName = Piece_ORM_Inflector::camelize($primaryKey, true);
            if (!array_key_exists($propertyName, $subject)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'The primary key not found in the given value.'
                                      );
                return;
            }

            if (!is_scalar($subject->$propertyName)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An inappropriate value for the primary key detected.'
                                      );
                return;
            }

            if (!strlen($subject->$propertyName)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An inappropriate value for the primary key detected.'
                                      );
                return;
            }
        }

        $affectedRows = $this->_executeQueryWithCriteria(__FUNCTION__, $subject, true);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        return $affectedRows;
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _find()

    /**
     * Finds an object with an appropriate SQL query.
     *
     * @param string $methodName
     * @param mixed  $criteria
     * @return stdClass
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
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

        if (!is_object($criteria)) {
            $propertyName = Piece_ORM_Inflector::lowercaseFirstLetter(substr($methodName, 6));
            if (version_compare(phpversion(), '5.0.0', '<')) {
                $propertyName = Piece_ORM_Inflector::camelize($this->_metadata->getFieldNameWithAlias($propertyName), true);
            }

            $criterion = $criteria;
            $criteria = &new stdClass();
            $criteria->$propertyName = $criterion;
        }

        $result = &$this->_executeQueryWithCriteria($methodName, $criteria);
        if (Piece_ORM_Error::hasErrors('exception')) {
            $return = null;
            return $return;
        }

        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $row = &$result->fetchRow();
        PEAR::staticPopErrorHandling();
        if (MDB2::isError($row)) {
            Piece_ORM_Error::pushPEARError($row,
                                           PIECE_ORM_ERROR_INVOCATION_FAILED,
                                           'Failed to invoke MDB2_Driver_' . $this->_getDriverName() . '::fetchRow() for any reasons.'
                                           );
            $return = null;
            return $return;
        }

        $object = &$this->_load($row);
        return $object;
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
        foreach ($criteria as $key => $value) {
            $criteria->$key = $this->_dbh->quote($value, $this->_metadata->getDatatype(Piece_ORM_Inflector::underscore($key)));
        }

        extract((array)$criteria);
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
    // {{{ _load()

    /**
     * Loads an object with a row.
     *
     * @param array $row
     * @return stdClass
     */
    function &_load($row)
    {
        if (is_null($row)) {
            return $row;
        }

        $object = &new stdClass();
        foreach ($row as $key => $value) {
            $propertyName = Piece_ORM_Inflector::camelize($key, true);
            $object->$propertyName = $value;
        }

        return $object;
    }

    // }}}
    // {{{ _executeQueryWithCriteria()

    /**
     * Executes a query.
     *
     * @param string   $methodName
     * @param stdClass $criteria
     * @param boolean  $isManip
     * @return mixed
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function &_executeQueryWithCriteria($methodName, $criteria, $isManip = false)
    {
        if (version_compare(phpversion(), '5.0.0', '>=')) {
            $criteria = clone($criteria);
        }

        $query = $this->_buildQuery($methodName, $criteria);
        if (Piece_ORM_Error::hasErrors('exception')) {
            $return = null;
            return $return;
        }

        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        if (!$isManip) {
            $result = &$this->_dbh->query($query);
        } else {
            $result = $this->_dbh->exec($query);
        }
        PEAR::staticPopErrorHandling();
        $this->_lastQuery = $this->_dbh->last_query;
        if (MDB2::isError($result)) {
            Piece_ORM_Error::pushPEARError($result,
                                           PIECE_ORM_ERROR_INVOCATION_FAILED,
                                           'Failed to invoke MDB2_Driver_' . $this->_getDriverName() . '::query() for any reasons.'
                                           );
            $return = null;
            return $return;
        }

        return $result;
    }

    // }}}
    // {{{ _getDriverName()

    /**
     * Gets the driver name of the database handle for this mapper.
     *
     * @return string
     */
    function _getDriverName()
    {
        return substr(strrchr(get_class($this->_dbh), '_'), 1);
    }

    // }}}
    // {{{ _loadAll()

    /**
     * Loads all objects with a result object.
     *
     * @param MDB2_Result &$result
     * @param array       $relationship
     * @return array
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _loadAll(&$result, $relationship = array())
    {
        $numberOfRelationships = count($relationship);
        $relationshipKeys = array();
        $objects = array();
        $objectsIndexes = array();
        $error = null;
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        for ($i = 0; $row = &$result->fetchRow(); ++$i) {
            if (MDB2::isError($row)) {
                $error = &$row;
                break;
            }

            $objects[] = &$this->_load($row);

            for ($k = 0; $k < $numberOfRelationships; ++$k) {
                $objects[$i]->{$relationship[$k]['mappedBy']} = array();
                $relationshipKeys[$k][] = $row[ $relationship[$k]['through']['referencedColumn'] ];
                $objectsIndexes[$k][ $row[ $relationship[$k]['through']['referencedColumn'] ] ]= $i;
            }
        }
        PEAR::staticPopErrorHandling();

        if (MDB2::isError($error)) {
            Piece_ORM_Error::pushPEARError($error,
                                           PIECE_ORM_ERROR_INVOCATION_FAILED,
                                           'Failed to invoke MDB2_Driver_' . $this->_getDriverName() . '::fetchRow() for any reasons.'
                                           );
            return;
        }

        for ($i = 0; $i < $numberOfRelationships; ++$i) {
            $relationshipKeyFieldName = "{$relationship[$i]['through']['table']}_{$relationship[$i]['through']['column']}";
            $query = "SELECT {$relationship[$i]['through']['table']}.{$relationship[$i]['through']['column']} AS $relationshipKeyFieldName, {$relationship[$i]['table']}.* FROM {$relationship[$i]['table']}, {$relationship[$i]['through']['table']} WHERE {$relationship[$i]['through']['table']}.{$relationship[$i]['through']['column']} IN (" . implode(',', $relationshipKeys[$i]) . ") AND {$relationship[$i]['table']}.{$relationship[$i]['column']} = {$relationship[$i]['through']['table']}.{$relationship[$i]['through']['inverseColumn']}";
            $associatedObjects = $this->_findAllWithQuery($query);
            if (Piece_ORM_Error::hasErrors('exception')) {
                return;
            }

            $numberOfAssociatedObjects = count($associatedObjects);
            $relationshipKeyPropertyName = Piece_ORM_Inflector::camelize($relationshipKeyFieldName, true);
            for ($k = 0; $k < $numberOfAssociatedObjects; ++$k) {
                $objects[ $objectsIndexes[$i][ $associatedObjects[$k]->$relationshipKeyPropertyName ] ]->{$relationship[$i]['mappedBy']}[] = &$associatedObjects[$k];
                unset($associatedObjects[$k]->$relationshipKeyPropertyName);
            }
        }

        return $objects;
    }

    // }}}
    // {{{ _findAll()

    /**
     * Finds all objects with an appropriate SQL query.
     *
     * @param string   $methodName
     * @param stdClass $criteria
     * @return array
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _findAll($methodName, $criteria)
    {
        if (is_null($criteria)) {
            $criteria = &new stdClass();
        }

        if (!is_object($criteria)) {
            if (strtolower($methodName) == strtolower('findAll')) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                      'An unexpected value detected. findAll() can only receive object or null.'
                                      );
                return;
            }

            $propertyName = Piece_ORM_Inflector::lowercaseFirstLetter(substr($methodName, 9));
            if (version_compare(phpversion(), '5.0.0', '<')) {
                $propertyName = Piece_ORM_Inflector::camelize($this->_metadata->getFieldNameWithAlias($propertyName), true);
            }

            $criterion = $criteria;
            $criteria = &new stdClass();
            $criteria->$propertyName = $criterion;
        }

        $result = &$this->_executeQueryWithCriteria($methodName, $criteria);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $objects = $this->_loadAll($result, $this->{'__relationship__' . strtolower($methodName)});
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        return $objects;
    }

    // }}}
    // {{{ _findAllWithQuery()

    /**
     * @param string $query
     * @return array
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _findAllWithQuery($query)
    {
        $result = &$this->_executeQuery($query);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $objects = $this->_loadAll($result);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        return $objects;
    }

    // }}}
    // {{{ _executeQuery()

    /**
     * @param string   $query
     * @param boolean  $isManip
     * @return mixed
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function &_executeQuery($query, $isManip = false)
    {
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        if (!$isManip) {
            $result = &$this->_dbh->query($query);
        } else {
            $result = $this->_dbh->exec($query);
        }
        PEAR::staticPopErrorHandling();
        $this->_lastQuery = $this->_dbh->last_query;
        if (MDB2::isError($result)) {
            Piece_ORM_Error::pushPEARError($result,
                                           PIECE_ORM_ERROR_INVOCATION_FAILED,
                                           'Failed to invoke MDB2_Driver_' . $this->_getDriverName() . '::query() for any reasons.'
                                           );
            $return = null;
            return $return;
        }

        return $result;
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
