<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
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

namespace Piece::ORM::Mapper;

use Piece::ORM::Mapper;
use Piece::ORM::Exception;
use Piece::ORM::Mapper::QueryType;
use Piece::ORM::Inflector;
use Piece::ORM::Exception::PEARException;
use Piece::ORM::Mapper::LOB;

// {{{ Piece::ORM::Mapper::QueryBuilder

/**
 * The query builder which builds a query based on a query source and criteria.
 *
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.1.0
 */
class QueryBuilder
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

    private $_mapper;
    private $_methodName;
    private $_quotedCriteria;
    private $_metadata;
    private $_isManip;
    private $_criteria;
    private $_query;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Initializes properties with the given values.
     *
     * @param Piece::ORM::Mapper $mapper
     * @param string             $methodName
     * @param stdClass           $criteria
     * @param boolean            $isManip
     */
    public function __construct(Mapper $mapper, $methodName, $criteria, $isManip)
    {
        $this->_metadata = $mapper->getMetadata();
        $this->_mapper = $mapper;
        $this->_methodName = $methodName;
        $this->_isManip = $isManip;
        $this->_criteria = $criteria;
        $this->_quotedCriteria = new stdClass();

        $this->_quoteCriteria();
        $this->_setCurrentTimestampToCriteria();
        $this->_setTableNameToCriteria();
    }

    // }}}
    // {{{ build()

    /**
     * Builds a query based on a query source and criteria.
     *
     * @return array
     * @throws Piece::ORM::Exception
     */
    public function build()
    {
        extract((array)$this->_quotedCriteria);
        $this->_query = $this->_mapper->getQuery($this->_methodName);

        $oldLevel = error_reporting(0);
        ini_set('track_errors', true);
        $message = eval("\$builtQuery = \"{$this->_query}\"; return \$php_errormsg;");
        ini_restore('track_errors');
        error_reporting($oldLevel);
        if (!is_null($message)) {
            throw new Exception("Failed to build a query for the method [ {$this->_methodName} ] for any reasons. See below for more details.
 $message");
        }

        if (QueryType::isFindAll($this->_methodName)) {
            $this->_mapper->setLastQueryForGetCount($builtQuery);
        } else {
            $this->_mapper->setLastQueryForGetCount(null);
        }

        if (!$this->_isManip) {
            $builtQuery .= $this->_mapper->getOrderBy($this->_methodName);
            $this->_mapper->clearOrders();
        }

        return array($builtQuery, $this->_createPreparedStatement($builtQuery));
    }

    // }}}
    // {{{ isQuotableValue()

    /**
     * Checks whether a value is quotable or not.
     *
     * @param string $value
     * @return boolean
     */
    public function isQuotableValue($value)
    {
        return is_scalar($value) || is_null($value);
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
    // {{{ _quoteCriteria()

    /**
     * Quotes the criteria.
     */
    private function _quoteCriteria()
    {
        foreach ($this->_criteria as $key => $value) {
            if ($this->isQuotableValue($value)) {
                $this->_quotedCriteria->$key = $this->_mapper->quote($value);
                continue;
            }

            if (is_array($value)) {
                $this->_quotedCriteria->$key =
                    implode(', ',
                            array_map(array($this->_mapper, 'quote'),
                                      array_filter($value, array($this, 'isQuotableValue')))
                            );
            }
        }
    }

    // }}}
    // {{{ _setCurrentTimestampToCriteria()

    /**
     * Sets the current timestmap to the createdAt/updatedAt property in
     * the criteria.
     */
    private function _setCurrentTimestampToCriteria()
    {
        if (QueryType::isInsert($this->_methodName)
            && $this->_metadata->getDatatype('created_at') == 'timestamp'
            ) {
            $createdAtProperty = Inflector::camelize('created_at', true);
            if (property_exists($this->_criteria, $createdAtProperty)) {
                $this->_quotedCriteria->$createdAtProperty = 'CURRENT_TIMESTAMP';
            }
        }

        if ((QueryType::isInsert($this->_methodName)
             || QueryType::isUpdate($this->_methodName))
            && $this->_metadata->getDatatype('updated_at') == 'timestamp'
            ) {
            $updatedAtProperty = Inflector::camelize('updated_at', true);
            if (property_exists($this->_criteria, $updatedAtProperty)) {
                $this->_quotedCriteria->$updatedAtProperty = 'CURRENT_TIMESTAMP';
            }
        }
    }

    // }}}
    // {{{ _setTableNameToCriteria()

    /**
     * Sets an appropriate table name as a built-in variable $__table to
     * the criteria.
     */
    private function _setTableNameToCriteria()
    {
        $this->_quotedCriteria->__table = $this->_metadata->getTableName();
    }

    // }}}
    // {{{ _createPreparedStatement()

    /**
     * Creates a prepared statement for LOB on insert()/update().
     *
     * @param string $query
     * @return ::MDB2_Statement_Common
     */
    private function _createPreparedStatement($query)
    {
        if (!preg_match_all('/:(\w+)/',
                            $this->_mapper->getQuery($this->_methodName),
                            $allMatches,
                            PREG_SET_ORDER)
            ) {
            return;
        }

        $placeHolderFields = array();
        foreach ($allMatches as $matches) {
            $placeHolderFields[] = $matches[1];
        }

        return $this->_buildPreparedStatement($query, $placeHolderFields);
    }

    // }}}
    // {{{ _buildPreparedStatement()

    /**
     * Builds a prepared statement for LOB on insert()/update().
     *
     * @param string $query
     * @param array  $placeHolderFields
     * @return ::MDB2_Statement_Common
     * @throws Piece::ORM::Exception::PEARException
     */
    private function _buildPreparedStatement($query, $placeHolderFields)
    {
        $types = array();
        foreach ($placeHolderFields as $placeHolderField) {
            $types[$placeHolderField] =
                $this->_metadata->getDatatype($placeHolderField);
        }

        $dbh = $this->_mapper->getConnection();
        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $sth = $dbh->prepare($query, $types, MDB2_PREPARE_MANIP);
        ::PEAR::staticPopErrorHandling();
        if (::MDB2::isError($sth)) {
            throw new PEARException($sth);
        }

        foreach ($types as $placeHolderField => $type) {
            do {
                $placeHolderProperty =
                    Inflector::camelize($placeHolderField, true);
                if (!property_exists($this->_criteria, $placeHolderProperty)) {
                    $value = null;
                    break;
                }

                if (is_null($this->_criteria->$placeHolderProperty)) {
                    $value = null;
                    break;
                }

                if ($type != 'blob' && $type != 'clob') {
                    $value = $this->_criteria->$placeHolderProperty;
                    break;
                }

                if (!is_object($this->_criteria->$placeHolderProperty)) {
                    $value = null;
                    break;
                }

                if (!$this->_criteria->$placeHolderProperty instanceof LOB) {
                    $value = null;
                    break;
                }

                $value = $this->_criteria->$placeHolderProperty->getSource();
                if (is_null($value)) {
                    $value = $this->_criteria->$placeHolderProperty->load();
                }
            } while (false);

            $sth->bindValue(":$placeHolderField", $value, $type);
        }

        return $sth;
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
