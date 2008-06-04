<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 1.1.0
 */

require_once 'Piece/ORM/Mapper/QueryType.php';
require_once 'Piece/ORM/Inflector.php';
require_once 'Piece/ORM/Error.php';

// {{{ Piece_ORM_Mapper_QueryBuilder

/**
 * The query builder which builds a query based on a query source and criteria.
 *
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.1.0
 */
class Piece_ORM_Mapper_QueryBuilder
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_mapper;
    var $_methodName;
    var $_criteria;
    var $_errorsInEval = array();
    var $_metadata;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Initializes properties with the given values.
     *
     * @param string   $methodName
     * @param stdClass $criteria
     */
    function Piece_ORM_Mapper_QueryBuilder(&$mapper, $methodName, $criteria)
    {
        $this->_metadata = &$mapper->getMetadata();
        $this->_mapper = &$mapper;
        $this->_methodName = $methodName;

        if (version_compare(phpversion(), '5.0.0', '>=')) {
            $this->_criteria = clone($criteria);
        } else {
            $this->_criteria = $criteria;
        }

        $this->_quoteCriteria();
        $this->_setCurrentTimestampToCriteria();
        $this->_setTableNameToCriteria();
    }

    // }}}
    // {{{ build()

    /**
     * Builds a query based on a query source and criteria.
     *
     * @return string
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function build()
    {
        extract((array)$this->_criteria);
        $query = $this->_mapper->getQuery($this->_methodName);

        set_error_handler(array(&$this, 'handleErrorInEval'));
        eval("\$query = \"$query\";");
        restore_error_handler();
        if (count($this->_errorsInEval)) {
            $message = implode("\n", $this->_errorsInEval);
            $this->_errorsInEval = array();
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVOCATION_FAILED,
                                  "Failed to build a query for the method [ {$this->_methodName} ] for any reasons. See below for more details.
 $message");
            return;
        }

        return $query;
    }

    // }}}
    // {{{ handleErrorInEval()

    /**
     * Collects error messages raised in eval().
     *
     * @param integer $errno
     * @param string  $errstr
     */
    function handleErrorInEval($errno, $errstr)
    {
        $this->_errorsInEval[] = $errstr;
    }

    // }}}
    // {{{ isQuotableValue()

    /**
     * Checks whether a value is quotable or not.
     *
     * @param string $value
     * @return boolean
     */
    function isQuotableValue($value)
    {
        return is_scalar($value) || is_null($value);
    }

    // }}}
    // {{{ buildPreparedStatement()

    /**
     * Builds a prepared statement for LOB on insert()/update().
     *
     * @param stdClass $criteria
     * @param string   $query
     * @param array    $placeHolderFields
     * @return MDB2_Statement_Common
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function buildPreparedStatement($criteria, $query, $placeHolderFields)
    {
        $types = array();
        foreach ($placeHolderFields as $placeHolderField) {
            $types[$placeHolderField] =
                $this->_metadata->getDatatype($placeHolderField);
        }

        $dbh = &$this->_mapper->getConnection();
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $sth = $dbh->prepare($query, $types, MDB2_PREPARE_MANIP);
        PEAR::staticPopErrorHandling();
        if (MDB2::isError($sth)) {
            Piece_ORM_Error::pushPEARError($sth,
                                           PIECE_ORM_ERROR_INVOCATION_FAILED,
                                           "Failed to invoke MDB2_Driver_{$this->_dbh->phptype}::prepare() for any reasons."
                                           );
            $return = null;
            return $return;
        }

        foreach ($types as $placeHolderField => $type) {
            do {
                $placeHolderProperty =
                    Piece_ORM_Inflector::camelize($placeHolderField, true);
                if (!array_key_exists($placeHolderProperty, $criteria)) {
                    $value = null;
                    break;
                }

                if (is_null($criteria->$placeHolderProperty)) {
                    $value = null;
                    break;
                }

                if ($type != 'blob' && $type != 'clob') {
                    $value = $criteria->$placeHolderProperty;
                    break;
                }

                if (!is_object($criteria->$placeHolderProperty)) {
                    $value = null;
                    break;
                }

                if (strtolower(get_class($criteria->$placeHolderProperty)) != strtolower('Piece_ORM_Mapper_LOB')) {
                    $value = null;
                    break;
                }

                $value = $criteria->$placeHolderProperty->getSource();
                if (is_null($value)) {
                    $value = $criteria->$placeHolderProperty->load();
                }
            } while (false);

            $sth->bindValue(":$placeHolderField", $value, $type);
        }

        return $sth;
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _quoteCriteria()

    /**
     * Quotes the criteria.
     */
    function _quoteCriteria()
    {
        foreach ($this->_criteria as $key => $value) {
            if ($this->isQuotableValue($value)) {
                $this->_criteria->$key = $this->_mapper->quote($value);
            } elseif (is_array($value)) {
                $this->_criteria->$key =
                    implode(', ',
                            array_map(array(&$this->_mapper, 'quote'),
                                      array_filter($value, array(&$this, 'isQuotableValue')))
                            );
            } else {
                unset($this->_criteria->$key);
            }
        }
    }

    // }}}
    // {{{ _setCurrentTimestampToCriteria()

    /**
     * Sets the current timestmap to the createdAt/updatedAt property in the criteria.
     */
    function _setCurrentTimestampToCriteria()
    {
        if (Piece_ORM_Mapper_QueryType::isInsert($this->_methodName)
            && $this->_metadata->getDatatype('created_at') == 'timestamp'
            ) {
            $createdAtProperty = Piece_ORM_Inflector::camelize('created_at', true);
            if (array_key_exists($createdAtProperty, $this->_criteria)) {
                $this->_criteria->$createdAtProperty = 'CURRENT_TIMESTAMP';
            }
        }

        if (Piece_ORM_Mapper_QueryType::isUpdate($this->_methodName)
            && $this->_metadata->getDatatype('updated_at') == 'timestamp'
            ) {
            $updatedAtProperty = Piece_ORM_Inflector::camelize('updated_at', true);
            if (array_key_exists($updatedAtProperty, $this->_criteria)) {
                $this->_criteria->$updatedAtProperty = 'CURRENT_TIMESTAMP';
            }
        }
    }

    // }}}
    // {{{ _setTableNameToCriteria()

    /**
     * Sets an appropriate table name as a built-in variable $__table to the criteria.
     */
    function _setTableNameToCriteria()
    {
        $this->_criteria->__table = $this->_metadata->getTableName();
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
