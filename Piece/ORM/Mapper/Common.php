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
require_once 'Piece/ORM/Mapper/ObjectLoader.php';
require_once 'PEAR.php';

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
            $id = $this->_dbh->lastInsertID($this->_metadata->getTableName(),
                                            $this->_metadata->getPrimaryKey()
                                            );
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

            if ($primaryKey = $this->_metadata->getPrimaryKey()) {
                $propertyName = Piece_ORM_Inflector::camelize($primaryKey, true);
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

        foreach ($this->_metadata->getPrimaryKeys() as $primaryKey) {
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

        foreach ($this->_metadata->getPrimaryKeys() as $primaryKey) {
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

    // }}}
    // {{{ findAllWithQuery()

    /**
     * Finds all objects with a query.
     *
     * @param string $query
     * @return array
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function findAllWithQuery($query)
    {
        $result = &$this->_executeQuery($query);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $objects = $this->_loadObjects($result);
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
            $criteria = &$this->_createCriteria($methodName, $criteria);
            if (Piece_ORM_Error::hasErrors('exception')) {
                return;
            }
        }

        $result = &$this->_executeQueryWithCriteria($methodName, $criteria);
        if (Piece_ORM_Error::hasErrors('exception')) {
            $return = null;
            return $return;
        }

        $objects = $this->_loadObjects($result, $this->{'__relationship__' . strtolower($methodName)});
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
            $criteria->$key = $this->quote($value, Piece_ORM_Inflector::underscore($key));
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
    // {{{ _executeQueryWithCriteria()

    /**
     * Executes a query with the given criteria.
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

        $result = &$this->_executeQuery($query, $isManip);
        return $result;
    }

    // }}}
    // {{{ _loadObjects()

    /**
     * Loads all objects with a result object.
     *
     * @param MDB2_Result &$result
     * @param array       $relationship
     * @return array
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _loadObjects(&$result, $relationship = array())
    {
        $loader = &new Piece_ORM_Mapper_ObjectLoader($this, $result, $relationship);
        return $loader->loadObjects();
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

            $criteria = &$this->_createCriteria($methodName, $criteria);
            if (Piece_ORM_Error::hasErrors('exception')) {
                return;
            }
        }

        $result = &$this->_executeQueryWithCriteria($methodName, $criteria);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        $objects = $this->_loadObjects($result, $this->{'__relationship__' . strtolower($methodName)});
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        return $objects;
    }

    // }}}
    // {{{ _executeQuery()

    /**
     * Executes a query.
     *
     * @param string  $query
     * @param boolean $isManip
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
                                           'Failed to invoke MDB2_Driver_' . $this->getDriverName() . '::query() for any reasons.'
                                           );
            $return = null;
            return $return;
        }

        return $result;
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
        if (preg_match('/By(.+)$/i', $methodName, $matches)) {
            $propertyName = Piece_ORM_Inflector::lowercaseFirstLetter($matches[1]);
            if (version_compare(phpversion(), '5.0.0', '<')) {
                $propertyName = Piece_ORM_Inflector::camelize($this->_metadata->getFieldNameWithAlias($propertyName), true);
            }

            $criteria = &new stdClass();
            $criteria->$propertyName = $criterion;
            return $criteria;
        } else {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                  "An unexpected value detected. $method() can only receive object or null."
                                  );
            $return = null;
            return $return;
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
