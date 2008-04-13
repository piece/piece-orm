<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
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

require_once 'Piece/ORM/Context.php';
require_once 'Piece/ORM/Inflector.php';

// {{{ Piece_ORM_Metadata

/**
 * The metadata interface.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Metadata
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_tableName;
    var $_tableInfo = array();
    var $_aliases = array();
    var $_hasID = false;
    var $_primaryKey = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Imports information for a table.
     *
     * @param array $tableInfo
     */
    function Piece_ORM_Metadata($tableInfo)
    {
        $this->_tableName = $tableInfo[0]['table'];
        foreach ($tableInfo as $fieldInfo) {
            $this->_tableInfo[ $fieldInfo['name'] ] = $fieldInfo;
            $this->_aliases[ strtolower(Piece_ORM_Inflector::camelize($fieldInfo['name'])) ] = $fieldInfo['name'];

            if (strpos($fieldInfo['flags'], 'primary_key') !== false) {
                $this->_primaryKey[] = $fieldInfo['name'];
            }
        }

        if (count($this->_primaryKey) == 1) {
            if ($this->isAutoIncrement($this->_primaryKey[0])) {
                $this->_hasID = true;
            }
        }
    }

    // }}}
    // {{{ getDatatype()

    /**
     * Gets the datatype for a given field name.
     *
     * @param string $fieldName
     * @return string
     */
    function getDatatype($fieldName)
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
    function getFieldNames()
    {
        return array_keys($this->_tableInfo);
    }

    // }}}
    // {{{ getTableName()

    /**
     * Gets the table name.
     *
     * @return string
     */
    function getTableName()
    {
        $context = &Piece_ORM_Context::singleton();
        if (!$context->getUseMapperNameAsTableName()) {
            return $this->_tableName;
        } else {
            $dbh = &$context->getConnection();
            return $dbh->quoteIdentifier($this->_tableName);
        }
    }

    // }}}
    // {{{ getFieldNameWithAlias()

    /**
     * Gets the field name of a table with a given alias.
     *
     * @return string
     */
    function getFieldNameWithAlias($alias)
    {
        return $this->_aliases[$alias];
    }

    // }}}
    // {{{ hasID()

    /**
     * Returns whether a table has an ID field or not.
     *
     * @return boolean
     */
    function hasID()
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
    function isAutoIncrement($fieldName)
    {
        if (!$this->hasField($fieldName)) {
            return;
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
    function hasPrimaryKey()
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
    function getPrimaryKeys()
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
    function isPartOfPrimaryKey($fieldName)
    {
        if (!$this->hasField($fieldName)) {
            return;
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
     * Returns whether a table has the given field.
     *
     * @param string $fieldName
     * @return boolean
     */
    function hasField($fieldName)
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
    function getPrimaryKey()
    {
        if ($this->hasPrimaryKey() && !$this->_hasCompositePrimaryKey()) {
            $primaryKeys = $this->_primaryKey;
            return $primaryKeys[0];
        }
    }

    // }}}
    // {{{ hasDefault()

    /**
     * Returns whether the given field has the default value or not.
     *
     * @param string $fieldName
     * @return boolean
     * @since Method available since Release 0.8.1
     */
    function hasDefault($fieldName)
    {
        if (!$this->hasField($fieldName)) {
            return;
        }

        return array_key_exists('default', $this->_tableInfo[$fieldName])
            && !is_null($this->_tableInfo[$fieldName]['default'])
            && strlen($this->_tableInfo[$fieldName]['default']);
    }

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
    function _hasCompositePrimaryKey()
    {
        if ($this->hasPrimaryKey()) {
            return (boolean)(count($this->_primaryKey) > 1);
        } else {
            return false;
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
