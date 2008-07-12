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

require_once 'Piece/ORM/Mapper/QueryBuilder.php';
require_once 'Piece/ORM/Error.php';
require_once 'MDB2.php';
require_once 'PEAR.php';

// {{{ Piece_ORM_Mapper_QueryExecutor

/**
 * The query executor which executes a query based on a query source and criteria.
 *
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.1.0
 */
class Piece_ORM_Mapper_QueryExecutor
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
    var $_isManip;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Sets whether a query is for data manipulation.
     *
     * @param Piece_ORM_Mapper_Common &$mapper
     * @param boolean                 $isManip
     */
    function Piece_ORM_Mapper_QueryExecutor(&$mapper, $isManip)
    {
        $this->_mapper = &$mapper;
        $this->_isManip = $isManip;
    }

    // }}}
    // {{{ execute()

    /**
     * Executes a query.
     *
     * @param string                $query
     * @param MDB2_Statement_Common $sth
     * @return MDB2_Result_Common|integer
     * @throws PIECE_ORM_ERROR_CANNOT_INVOKE
     * @throws PIECE_ORM_ERROR_UNEXPECTED_VALUE
     * @throws PIECE_ORM_ERROR_CONSTRAINT
     */
    function &execute($query, $sth)
    {
        $dbh = &$this->_mapper->getConnection();
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        if (!$this->_isManip) {
            $result = &$dbh->query($query);
        } else {
            if (is_null($sth)) {
                $result = $dbh->exec($query);
            } else {
                if (!is_subclass_of($sth, 'MDB2_Statement_Common')) {
                    PEAR::staticPopErrorHandling();
                    Piece_ORM_Error::push(PIECE_ORM_ERROR_UNEXPECTED_VALUE,
                                          'An unexpected value detected. executeQuery() with a prepared statement can only receive a MDB2_Statement_Common object.'
                                          );
                    $return = null;
                    return $return;
                }

                $result = $sth->execute();
            }
        }
        PEAR::staticPopErrorHandling();

        $this->_mapper->setLastQuery($dbh->last_query);

        if (MDB2::isError($result)) {
            if ($result->getCode() == MDB2_ERROR_CONSTRAINT) {
                $code = PIECE_ORM_ERROR_CONSTRAINT;
            } else {
                $code = PIECE_ORM_ERROR_CANNOT_INVOKE;
            }
            Piece_ORM_Error::pushPEARError($result,
                                           $code,
                                           "Failed to invoke MDB2_Driver_{$dbh->phptype}::query() for any reasons."
                                           );
            $return = null;
            return $return;
        }

        return $result;
    }

    // }}}
    // {{{ executeWithCriteria()

    /**
     * Executes a query with the given criteria.
     *
     * @param string   $methodName
     * @param stdClass $criteria
     * @return MDB2_Result_Common|integer
     */
    function &executeWithCriteria($methodName, $criteria)
    {
        $queryBuilder = &new Piece_ORM_Mapper_QueryBuilder($this->_mapper,
                                                           $methodName,
                                                           $criteria,
                                                           $this->_isManip
                                                           );
        list($query, $sth) = $queryBuilder->build();
        if (Piece_ORM_Error::hasErrors()) {
            $return = null;
            return $return;
        }

        $result = &$this->execute($query, $sth);
        if (Piece_ORM_Error::hasErrors()) {
            $return = null;
            return $return;
        }

        return $result;
    }

    /**#@-*/

    /**#@+
     * @access private
     */

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
