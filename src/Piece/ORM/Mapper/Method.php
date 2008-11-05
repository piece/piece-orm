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
 * @since      File available since Release 2.0.0dev1
 */

namespace Piece::ORM::Mapper;

use Piece::ORM::Exception;
use Piece::ORM::Mapper::Association;
use Piece::ORM::Mapper::QueryType;

// {{{ Piece::ORM::Mapper::Method

/**
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0dev1
 */
class Method
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

    private $_name;
    private $_query;
    private $_orderBy;
    private $_associations = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * @param string $name
     * @throws Piece::ORM::Exception
     */
    public function __construct($name)
    {
        if (!$this->_validateName($name)) {
            throw new Exception("Cannot use the method [ $name ] since it is a reserved for internal use only");
        }

        $this->_name = $name;
    }

    // }}}
    // {{{ getName()

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    // }}}
    // {{{ getQuery()

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->_query;
    }

    // }}}
    // {{{ getOrderBy()

    /**
     * @return string
     */
    public function getOrderBy()
    {
        return $this->_orderBy;
    }

    // }}}
    // {{{ getAssociations()

    /**
     * @return array
     */
    public function getAssociations()
    {
        return $this->_associations;
    }

    // }}}
    // {{{ setQuery()

    /**
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->_query = $query;
    }

    // }}}
    // {{{ setOrderBy()

    /**
     * @param string $orderBy
     */
    public function setOrderBy($orderBy)
    {
        $this->_orderBy = $orderBy;
    }

    // }}}
    // {{{ addAssociation()

    /**
     * @param Piece::ORM::Mapper::Association $association
     */
    public function addAssociation(Association $association)
    {
        $this->_associations[] = $association;
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
    // {{{ _validateName()

    /**
     * @param string $name
     * @return boolean
     */
    private function _validateName($name)
    {
        if (method_exists('Piece::ORM::Mapper', $name)) {
            return false;
        }

        return QueryType::isFindAll($name)
            || QueryType::isFindOne($name)
            || QueryType::isFind($name)
            || QueryType::isInsert($name)
            || QueryType::isUpdate($name)
            || QueryType::isDelete($name);
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
