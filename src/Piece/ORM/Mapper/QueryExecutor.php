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

use Piece::ORM::Mapper::AbstractMapper;
use Piece::ORM::Exception;
use Piece::ORM::Mapper::QueryExecutor::ConstraintException;
use Piece::ORM::Exception::PEARException;
use Piece::ORM::Mapper::QueryBuilder;

// {{{ Piece::ORM::Mapper::QueryExecutor

/**
 * The query executor which executes a query based on a query source and criteria.
 *
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.1.0
 */
class QueryExecutor
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
    private $_isManip;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Sets whether a query is for data manipulation.
     *
     * @param Piece::ORM::Mapper::AbstractMapper $mapper
     * @param boolean                    $isManip
     */
    public function __construct(AbstractMapper $mapper, $isManip)
    {
        $this->_mapper = $mapper;
        $this->_isManip = $isManip;
    }

    // }}}
    // {{{ execute()

    /**
     * Executes a query.
     *
     * @param string                  $query
     * @param ::MDB2_Statement_Common $sth
     * @return ::MDB2_Result_Common|integer
     * @throws Piece::ORM::Exception
     * @throws Piece::ORM::Mapper::QueryExecutor::ConstraintException
     * @throws Piece::ORM::Exception::PEARException
     */
    public function execute($query, $sth)
    {
        $dbh = $this->_mapper->getConnection();
        ::PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        if (!$this->_isManip) {
            $result = $dbh->query($query);
        } else {
            if (is_null($sth)) {
                $result = $dbh->exec($query);
            } else {
                if (!$sth instanceof ::MDB2_Statement_Common) {
                    ::PEAR::staticPopErrorHandling();
                    throw new Exception('An unexpected value detected. executeQuery() with a prepared statement can only receive a ::MDB2_Statement_Common object.');
                }

                $result = $sth->execute();
            }
        }
        ::PEAR::staticPopErrorHandling();

        $this->_mapper->setLastQuery($dbh->last_query);

        if (::MDB2::isError($result)) {
            if ($result->getCode() == MDB2_ERROR_CONSTRAINT) {
                throw new ConstraintException($result);
            }

            throw new PEARException($result);
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
     * @return ::MDB2_Result_Common|integer
     */
    public function executeWithCriteria($methodName, $criteria)
    {
        $queryBuilder = new QueryBuilder($this->_mapper,
                                         $methodName,
                                         $criteria,
                                         $this->_isManip
                                         );
        list($query, $sth) = $queryBuilder->build();
        return $this->execute($query, $sth);
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
