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

namespace Piece::ORM;

use Piece::ORM::Inflector;
use Piece::ORM::Context::Registry;

// {{{ Piece::ORM::Metadata

/**
 * The metadata interface.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Metadata
{

    // {{{ properties

    /**#@+
     * @access public
     */

    public $tableID;

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    private $_tableName;
    private $_tableInfo = array();
    private $_aliases = array();
    private $_hasID = false;
    private $_primaryKey = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ __construct()

    /**
     * Imports information for a table.
     *
     * @param array  $tableInfo
     * @param string $tableID
     */
    public function __construct(array $tableInfo, $tableID)
    {
        $this->_tableName = $tableInfo[0]['table'];
        foreach ($tableInfo as $fieldInfo) {
            $this->_tableInfo[ $fieldInfo['name'] ] = $fieldInfo;
            $this->_aliases[ strtolower(Piece::ORM::Inflector::camelize($fieldInfo['name'])) ] = $fieldInfo['name'];

            if (strpos($fieldInfo['flags'], 'primary_key') !== false) {
                $this->_primaryKey[] = $fieldInfo['name'];
            }
        }

        if (count($this->_primaryKey) == 1) {
            if ($this->isAutoIncrement($this->_primaryKey[0])) {
                $this->_hasID = true;
            }
        }

        $this->tableID = $tableID;
    }

    // }}}
    // {{{ getDatatype()

    /**
     * Gets the datatype for a given field name.
     *
     * @param string $fieldName
     * @return string
     */
    public function getDatatype($fieldName)
    {
        if (!$this->hasField($fieldName)) {
            return;
        }

        return $this->_tableInfo[$fieldName]['mdb2type'];
    }

    // }}}
    // {{{ getFieldNames()

    /**
     * Gets the field names of a table as an array.
     *
     * @return array
     */
    public function getFieldNames()
    {
        return array_keys($this->_tableInfo);
    }

    // }}}
    // {{{ getTableName()

    /**
     * Gets the table name.
     *
     * @param boolean $notQuoteIdentifier
     * @return string
     */
    public function getTableName($notQuoteIdentifier = false)
    {
        $context = Registry::getContext();
        if (!$context->getUseMapperNameAsTableName() || $notQuoteIdentifier) {
            return $this->_tableName;
        } else {
            $dbh = $context->getConnection();
            return $dbh->quoteIdentifier($this->_tableName);
        }
    }

    // }}}
    // {{{ getFieldNameWithAlias()

    /**
     * Gets the field name of a table by a given alias.
     *
     * @return string
     * @deprecated Method deprecated in Release 1.2.0
     */
    public function getFieldNameWithAlias($alias)
    {
        return $this->getFieldNameByAlias($alias);
    }

    // }}}
    // {{{ hasID()

    /**
     * Returns whether a table has an ID field or not.
     *
     * @return boolean
     */
    public function hasID()
    {
        return $this->_hasID;
    }

    // }}}
    // {{{ isAutoIncrement()

    /**
     * Returns whether a field is an auto increment field or not.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function isAutoIncrement($fieldName)
    {
        if (!$this->hasField($fieldName)) {
            return false;
        }

        return array_key_exists('autoincrement', $this->_tableInfo[$fieldName]);
    }

    // }}}
    // {{{ hasPrimaryKey()

    /**
     * Returns whether a table has the primary key.
     *
     * @return boolean
     */
    public function hasPrimaryKey()
    {
        return (boolean)count($this->_primaryKey);
    }

    // }}}
    // {{{ getPrimaryKeys()

    /**
     * Gets the primary key for a table as an array.
     *
     * @return array
     */
    public function getPrimaryKeys()
    {
        if ($this->hasPrimaryKey()) {
            return $this->_primaryKey;
        }
    }

    // }}}
    // {{{ isPartOfPrimaryKey()

    /**
     * Returns whether a field is a part of the primary key or not.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function isPartOfPrimaryKey($fieldName)
    {
        if (!$this->hasField($fieldName)) {
            return false;
        }

        if ($this->hasPrimaryKey()) {
            return in_array($fieldName, $this->_primaryKey);
        } else {
            return false;
        }
    }

    // }}}
    // {{{ hasField()

    /**
     * Returns whether a table has a given field.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function hasField($fieldName)
    {
        return array_key_exists($fieldName, $this->_tableInfo);
    }

    // }}}
    // {{{ getPrimaryKey()

    /**
     * Gets the primary key for a table if the table has the single primary
     * key.
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        if ($this->hasPrimaryKey() && !$this->_hasCompositePrimaryKey()) {
            $primaryKeys = $this->_primaryKey;
            return $primaryKeys[0];
        }
    }

    // }}}
    // {{{ hasDefault()

    /**
     * Returns whether a given field has the default value or not.
     *
     * @param string $fieldName
     * @return boolean
     * @since Method available since Release 0.8.1
     */
    public function hasDefault($fieldName)
    {
        if (!$this->hasField($fieldName)) {
            return false;
        }

        return array_key_exists('default', $this->_tableInfo[$fieldName])
            && !is_null($this->_tableInfo[$fieldName]['default'])
            && strlen($this->_tableInfo[$fieldName]['default']);
    }

    // }}}
    // {{{ isLOB()

    /**
     * Returns whether a field is a LOB field or not.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function isLOB($fieldName)
    {
        $datatype = $this->getDatatype($fieldName);
        if (is_null($datatype)) {
            return false;
        }

        return $datatype == 'blob' || $datatype == 'clob';
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
        if (!$this->hasDefault($fieldName)) {
            return;
        }

        return $this->_tableInfo[$fieldName]['default'];
    }

    // }}}
    // {{{ getFieldNameByAlias()

    /**
     * Gets the field name of a table by a given alias.
     *
     * @return string
     * @since Method available since Release 1.2.0
     */
    public function getFieldNameByAlias($alias)
    {
        return $this->_aliases[$alias];
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
    // {{{ _hasCompositePrimaryKey()

    /**
     * Returns whether a table has the composite primary key.
     *
     * @return boolean
     */
    private function _hasCompositePrimaryKey()
    {
        if (!$this->hasPrimaryKey()) {
            return false;
        }

        return (boolean)(count($this->_primaryKey) > 1);
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
