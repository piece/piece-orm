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
use Piece::ORM::Mapper::Association::LinkTable;

// {{{ Piece::ORM::Mapper::Association

/**
 * @package    Piece_ORM
 * @copyright  2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0dev1
 */
class Association
{

    // {{{ constants

    const ASSOCIATIONTYPE_MANYTOMANY = 'manyToMany';
    const ASSOCIATIONTYPE_ONETOMANY = 'oneToMany';
    const ASSOCIATIONTYPE_MANYTOONE = 'manyToOne';
    const ASSOCIATIONTYPE_ONETOONE = 'oneToOne';

    // }}}
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

    private static $_associationTypes = array(self::ASSOCIATIONTYPE_MANYTOMANY,
                                              self::ASSOCIATIONTYPE_ONETOMANY,
                                              self::ASSOCIATIONTYPE_MANYTOONE,
                                              self::ASSOCIATIONTYPE_ONETOONE
                                              );
    private $_table;
    private $_associationType;
    private $_property;
    private $_column;
    private $_referencedColumn;
    private $_orderBy;
    private $_linkTable;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ setTable()

    /**
     * @param string $table
     */
    public function setTable($table)
    {
        $this->_table = $table;
    }

    // }}}
    // {{{ setAssociationType()

    /**
     * @param integer $associationType
     * @throw Piece::ORM::Exception
     */
    public function setAssociationType($associationType)
    {
        if (!in_array($associationType, self::$_associationTypes)) {
            throw new Exception('An association type must be one of ' .
                                implode(', ', self::$_associationTypes) .
                                ". [ $associationType ] is given."
                                );
        }

        $this->_associationType = $associationType;
    }

    // }}}
    // {{{ setProperty()

    /**
     * @param string $property
     */
    public function setProperty($property)
    {
        $this->_property = $property;
    }

    // }}}
    // {{{ setColumn()

    /**
     * @param string $column
     */
    public function setColumn($column)
    {
        $this->_column = $column;
    }

    // }}}
    // {{{ setReferencedColumn()

    /**
     * @param string $referencedColumn
     */
    public function setReferencedColumn($referencedColumn)
    {
        $this->_referencedColumn = $referencedColumn;
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
    // {{{ setLinkTable()

    /**
     * @param Piece::ORM::Mapper::Association::LinkTable $linkTable
     */
    public function setLinkTable(LinkTable $linkTable)
    {
        $this->_linkTable = $linkTable;
    }

    // }}}
    // {{{ getAssociationTypes()

    /**
     * Gets the association types.
     *
     * @return array
     */
    public static function getAssociationTypes()
    {
        return self::$_associationTypes;
    }

    // }}}
    // {{{ getTable()

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->_table;
    }

    // }}}
    // {{{ getAssociationType()

    /**
     * @return integer
     */
    public function getAssociationType()
    {
        return $this->_associationType;
    }

    // }}}
    // {{{ getProperty()

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->_property;
    }

    // }}}
    // {{{ getColumn()

    /**
     * @return string
     */
    public function getColumn()
    {
        return $this->_column;
    }

    // }}}
    // {{{ getReferencedColumn()

    /**
     * @return string
     */
    public function getReferencedColumn()
    {
        return $this->_referencedColumn;
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
    // {{{ getLinkTable()

    /**
     * @return Piece::ORM::Mapper::Association::LinkTable
     */
    public function getLinkTable()
    {
        return $this->_linkTable;
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

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
